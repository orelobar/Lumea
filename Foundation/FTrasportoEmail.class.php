<?php

/** Trasporto SMTP autenticato, circoscritto alle notifiche del salone. Nessun fallback a mail(). */
class FTrasportoEmail
{
    public static function configurazione(): array
    {
        $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        $documentRoot = $documentRoot ?: dirname(__DIR__, 2);
        $pathEsplicito = getenv('LUMEA_EMAIL_CONFIG');
        $path = $pathEsplicito ?: dirname($documentRoot) . '/lumea-private/email.json';
        $cartellaHosting = dirname(__DIR__) . '/config';
        $fileHosting = $cartellaHosting . '/email.infinityfree.php';
        if (@is_file($path)) {
            $real = realpath($path);
            $root = realpath($documentRoot);
            if (!$real || !is_readable($real)
                || ($root && ($real === $root || str_starts_with($real, $root . DIRECTORY_SEPARATOR)))) {
                throw new RuntimeException('La configurazione email deve essere fuori dal DocumentRoot.');
            }
            if ((fileperms($real) & 0007) !== 0) {
                throw new RuntimeException('Permessi della configurazione email non sicuri.');
            }
            try {
                $config = json_decode(file_get_contents($real), true, 16, JSON_THROW_ON_ERROR);
            } catch (Throwable $e) {
                throw new RuntimeException('Configurazione email non valida.');
            }
        } elseif (!$pathEsplicito && is_file($fileHosting)) {
            if (is_link($cartellaHosting) || is_link($fileHosting)
                || realpath($fileHosting) !== realpath($cartellaHosting) . '/email.infinityfree.php'
                || !is_readable($fileHosting) || !is_file($cartellaHosting . '/.htaccess')) {
                throw new RuntimeException('Configurazione email privata non accessibile o non sicura.');
            }
            try {
                $caricamentoConfigurazioneEmailLumea = true;
                $config = require $fileHosting;
            } catch (Throwable $e) {
                throw new RuntimeException('Configurazione email non valida.');
            }
        } else {
            return ['mode' => 'disabled'];
        }
        if (!is_array($config)) {
            throw new RuntimeException('Configurazione email non valida.');
        }
        $config['mode'] = getenv('LUMEA_EMAIL_MODE') ?: ($config['mode'] ?? 'disabled');
        if (!in_array($config['mode'], ['disabled', 'log', 'smtp_test', 'smtp'], true)) {
            throw new RuntimeException('Modalità email non valida.');
        }
        return $config;
    }

    public static function messaggio(string $destinatario, string $oggetto, array $contenuto, array $config): string
    {
        self::indirizzo($destinatario);
        self::indirizzo($config['sender'] ?? '');
        $boundary = 'lumea_' . bin2hex(random_bytes(18));
        $subject = mb_encode_mimeheader($oggetto, 'UTF-8', 'B', "\r\n");
        $prefix = $config['mode'] === 'smtp_test' ? '[TEST LUMÈA] ' : '';
        if ($prefix !== '') {
            $subject = mb_encode_mimeheader($prefix . $oggetto, 'UTF-8', 'B', "\r\n");
        }
        $headers = [
            'From: ' . mb_encode_mimeheader('Lumèa', 'UTF-8', 'B', "\r\n") . ' <' . $config['sender'] . '>',
            'To: ' . $destinatario,
            'Subject: ' . $subject,
            'Date: ' . date('r'),
            'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . substr(strrchr($config['sender'], '@'), 1) . '>',
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];
        $body = '';
        foreach (['testo' => 'text/plain', 'html' => 'text/html'] as $key => $type) {
            $text = preg_replace('/\r\n|\r|\n/', "\r\n", $contenuto[$key]);
            $body .= '--' . $boundary . "\r\nContent-Type: " . $type . "; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
                . quoted_printable_encode($text) . "\r\n";
        }
        return implode("\r\n", $headers) . "\r\n\r\n" . $body . '--' . $boundary . "--\r\n";
    }

    public static function invia(string $destinatario, string $oggetto, array $contenuto, array $config, bool $collaudoManuale = false): bool
    {
        self::indirizzo($destinatario);
        if ($config['mode'] === 'smtp_test') {
            if (($config['test_manual_only'] ?? false) === true && !$collaudoManuale) {
                throw new RuntimeException('Collaudo SMTP consentito solo dalla prova manuale.');
            }
            // Il controllo riguarda la connessione effettiva, non soltanto una variabile.
            require_once __DIR__ . '/FConnectionDB.class.php';
            $databaseTest = $config['test_database'] ?? 'lumea_revisione_20261012';
            if (!is_string($databaseTest) || !preg_match('/^[a-zA-Z0-9_]+$/D', $databaseTest)
                || FConnectionDB::connect()->query('SELECT DATABASE()')->fetchColumn() !== $databaseTest) {
                throw new RuntimeException('Database del collaudo SMTP non autorizzato.');
            }
            self::indirizzo($config['test_recipient'] ?? '');
            $destinatario = $config['test_recipient'];
        } elseif ($config['mode'] !== 'smtp' || ($config['production_enabled'] ?? false) !== true) {
            throw new RuntimeException('Invio ordinario non abilitato esplicitamente.');
        }
        // Gli indirizzi dimostrativi non devono mai essere destinatari SMTP effettivi.
        $dominio = strtolower(substr(strrchr($destinatario, '@'), 1));
        if ($dominio === 'test' || str_ends_with($dominio, '.test')) {
            throw new RuntimeException('Invio SMTP a indirizzi dimostrativi non consentito.');
        }
        $message = self::messaggio($destinatario, $oggetto, $contenuto, $config);
        $socket = self::connessioneAutenticata($config);
        try {
            self::comando($socket, 'MAIL FROM:<' . $config['sender'] . '>', [250]);
            self::comando($socket, 'RCPT TO:<' . $destinatario . '>', [250, 251]);
            self::comando($socket, 'DATA', [354]);
            self::scrivi($socket, preg_replace('/^\./m', '..', $message) . ".\r\n");
            self::risposta($socket, [250]);
            // Evidenza privata del solo collaudo QA; nessun dato AUTH viene registrato.
            $audit = getenv('LUMEA_EMAIL_AUDIT');
            if ($config['mode'] === 'smtp_test' && $audit) {
                $record = ['ora' => date('c'), 'destinatario' => $destinatario,
                    'mittente' => $config['sender'], 'oggetto' => '[TEST LUMÈA] ' . $oggetto,
                    'codice_smtp' => 250, 'mime_base64' => base64_encode($message)];
                if (@file_put_contents($audit, json_encode($record, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX) === false) {
                    error_log('Lumèa: messaggio SMTP accettato, scrittura evidenza QA non riuscita.');
                }
            }
            // Dopo DATA accettato, un errore QUIT non deve far sembrare fallita la consegna.
            try {
                self::comando($socket, 'QUIT', [221]);
            } catch (Throwable $e) {
            }
            return true;
        } finally {
            fclose($socket);
        }
    }

    /** Autenticazione e TLS soltanto: non esegue MAIL, RCPT o DATA. */
    public static function verificaAutenticazione(array $config): bool
    {
        $socket = self::connessioneAutenticata($config);
        try {
            self::comando($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }
        return true;
    }

    private static function connessioneAutenticata(array $config)
    {
        self::indirizzo($config['sender'] ?? '');
        self::indirizzo($config['username'] ?? '');
        $host = $config['host'] ?? '';
        $security = $config['security'] ?? 'starttls';
        $port = (int) ($config['port'] ?? 587);
        if (!preg_match('/^[a-z0-9.-]+$/iD', $host) || !in_array($security, ['starttls', 'tls'], true)
            || $port < 1 || $port > 65535 || empty($config['password'])) {
            throw new RuntimeException('Parametri SMTP incompleti o non sicuri.');
        }
        $context = stream_context_create(['ssl' => [
            'verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false,
            'peer_name' => $host, 'SNI_enabled' => true,
        ]]);
        $socket = @stream_socket_client(
            ($security === 'tls' ? 'tls' : 'tcp') . '://' . $host . ':' . $port,
            $errno,
            $errstr,
            10,
            STREAM_CLIENT_CONNECT,
            $context
        );
        if ($socket === false) {
            throw new RuntimeException('Connessione SMTP non disponibile.');
        }
        stream_set_timeout($socket, 10);
        try {
            self::risposta($socket, [220]);
            $ehlo = self::comando($socket, 'EHLO lumea.local', [250]);
            if ($security === 'starttls') {
                if (stripos($ehlo, 'STARTTLS') === false) {
                    throw new RuntimeException('STARTTLS non disponibile sul server.');
                }
                self::comando($socket, 'STARTTLS', [220]);
                if (@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) {
                    throw new RuntimeException('Verifica TLS SMTP non riuscita.');
                }
                $ehlo = self::comando($socket, 'EHLO lumea.local', [250]);
            }
            if (!preg_match('/AUTH[^\r\n]*\bLOGIN\b/i', $ehlo)) {
                throw new RuntimeException('Metodo SMTP AUTH LOGIN non disponibile.');
            }
            self::comando($socket, 'AUTH LOGIN', [334]);
            self::comando($socket, base64_encode($config['username']), [334]);
            self::comando($socket, base64_encode($config['password']), [235]);
            return $socket;
        } catch (Throwable $e) {
            fclose($socket);
            throw $e;
        }
    }

    private static function comando($socket, string $comando, array $codici): string
    {
        self::scrivi($socket, $comando . "\r\n");
        return self::risposta($socket, $codici);
    }

    private static function scrivi($socket, string $dati): void
    {
        while ($dati !== '') {
            $n = @fwrite($socket, $dati);
            if ($n === false || $n === 0) {
                throw new RuntimeException('Scrittura SMTP non riuscita.');
            }
            $dati = substr($dati, $n);
        }
    }

    private static function risposta($socket, array $codici): string
    {
        $risposta = '';
        for ($i = 0; $i < 100; $i++) {
            $line = @fgets($socket, 1024);
            if ($line === false || !preg_match('/^([0-9]{3})([ -])/', $line, $m)) {
                throw new RuntimeException('Risposta SMTP assente o non valida.');
            }
            $risposta .= $line;
            if ($m[2] === ' ') {
                if (!in_array((int) $m[1], $codici, true)) {
                    // Non registrare risposte, comandi AUTH o credenziali del server.
                    throw new RuntimeException('SMTP rifiutato, codice ' . $m[1] . '.');
                }
                return $risposta;
            }
        }
        throw new RuntimeException('Risposta SMTP eccessivamente lunga.');
    }

    private static function indirizzo(string $email): void
    {
        if (preg_match('/[\r\n]/', $email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('Indirizzo email non valido.');
        }
    }
}
