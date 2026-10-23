<?php

/** Accesso DNS separato dal controller: non verifica la singola casella email. */
class FValidazioneEmail
{
    public static function erroreDominio(string $email): ?string
    {
        $dominio = strtolower(substr(strrchr($email, '@') ?: '', 1));
        if (filter_var($dominio, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false
            || strpos($dominio, '.') === false) {
            return 'Inserisci un indirizzo email con un dominio valido.';
        }

        // I domini sintetici restano utilizzabili soltanto nella demo locale/QA.
        $locale = in_array($_SERVER['SERVER_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
            || getenv('LUMEA_DB_NAME') === 'lumea_revisione_20261012';
        if ($locale && str_ends_with($dominio, '.test')) {
            return null;
        }

        $stato = static::verificaDominio($dominio);
        if ($stato === 'inesistente') {
            return 'Il dominio dell’indirizzo email non esiste. Controlla di averlo scritto correttamente.';
        }
        if ($stato === 'non_riceve') {
            return 'Il dominio indicato non è configurato per ricevere email. Usa un altro indirizzo.';
        }

        // Un controllo non disponibile o temporaneamente fallito non blocca l’utente.
        return null;
    }

    public static function verificaDominio(string $dominio): string
    {
        $scadenza = microtime(true) + 2.0;
        $mx = static::interroga($dominio, 15, $scadenza);
        if ($mx['stato'] !== 'risposta') {
            return $mx['stato'];
        }
        if (in_array('mx', $mx['record'], true)) {
            return 'valido';
        }
        if (in_array('null_mx', $mx['record'], true)) {
            return 'non_riceve';
        }

        // Senza MX, SMTP permette la consegna al dominio stesso (MX implicito).
        $a = static::interroga($dominio, 1, $scadenza);
        if (in_array('indirizzo', $a['record'], true)) {
            return 'valido';
        }
        $aaaa = static::interroga($dominio, 28, $scadenza);
        if (in_array('indirizzo', $aaaa['record'], true)) {
            return 'valido';
        }
        if ($a['stato'] === 'inesistente' || $aaaa['stato'] === 'inesistente') {
            return 'inesistente';
        }
        if ($mx['negativa'] && $a['negativa'] && $aaaa['negativa']) {
            return 'non_riceve';
        }
        return 'non_verificabile';
    }

    /** Il codice DNS distingue NXDOMAIN da timeout/SERVFAIL, diversamente da checkdnsrr(). */
    protected static function interroga(string $dominio, int $tipo, float $scadenza, int $salti = 0): array
    {
        $incerto = ['stato' => 'non_verificabile', 'record' => [], 'negativa' => false];
        if ($salti > 4 || microtime(true) >= $scadenza
            || filter_var($dominio, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false
            || !function_exists('stream_socket_client')) {
            return $incerto;
        }
        $server = getenv('LUMEA_DNS_SERVER') ?: '';
        if ($server === '' && @is_readable('/etc/resolv.conf')) {
            $config = @file_get_contents('/etc/resolv.conf');
            if (preg_match('/^nameserver\s+([^\s#]+)/m', $config ?: '', $match)) {
                $server = $match[1];
            }
        }
        if (filter_var($server, FILTER_VALIDATE_IP) === false) {
            return $incerto;
        }
        $indirizzo = strpos($server, ':') !== false ? '[' . $server . ']' : $server;
        $socket = @stream_socket_client('udp://' . $indirizzo . ':53', $errno, $errore, 0.3);
        if ($socket === false) {
            return $incerto;
        }
        try {
            $id = random_int(0, 65535);
            $nome = '';
            foreach (explode('.', $dominio) as $etichetta) {
                $nome .= chr(strlen($etichetta)) . $etichetta;
            }
            $domanda = $nome . "\0" . pack('nn', $tipo, 1);
            $pacchetto = pack('nnnnnn', $id, 0x0100, 1, 0, 0, 0) . $domanda;
            if (@fwrite($socket, $pacchetto) !== strlen($pacchetto)) {
                return $incerto;
            }
            $tempo = max(0.001, min(0.65, $scadenza - microtime(true)));
            stream_set_timeout($socket, 0, (int) ($tempo * 1000000));
            $risposta = @fread($socket, 4096);
            if ($risposta === false || strlen($risposta) < 12) {
                return $incerto;
            }
            $header = unpack('nid/nflag/ndomande/nrisposte/nautorita/naggiuntivi', substr($risposta, 0, 12));
            if ($header['id'] !== $id || ($header['flag'] & 0x8000) === 0
                || ($header['flag'] & 0x7A00) !== 0 || $header['domande'] !== 1) {
                return $incerto; // Anche le risposte troncate vengono trattate come incerte.
            }
            $posizione = 12;
            $nomeRisposta = self::leggiNome($risposta, $posizione);
            if (strtolower($nomeRisposta) !== strtolower($dominio)
                || substr($risposta, $posizione, 4) !== pack('nn', $tipo, 1)) {
                return $incerto;
            }
            $posizione += 4;
            $codice = $header['flag'] & 0x000F;
            if ($codice === 3) {
                return ['stato' => 'inesistente', 'record' => [], 'negativa' => true];
            }
            if ($codice !== 0) {
                return $incerto;
            }
            $record = [];
            $soa = false;
            $alias = null;
            for ($i = 0; $i < $header['risposte'] + $header['autorita']; $i++) {
                self::leggiNome($risposta, $posizione);
                if ($posizione + 10 > strlen($risposta)) {
                    return $incerto;
                }
                $rr = unpack('ntipo/nclasse/Nttl/nlunghezza', substr($risposta, $posizione, 10));
                $posizione += 10;
                $fine = $posizione + $rr['lunghezza'];
                if ($fine > strlen($risposta)) {
                    return $incerto;
                }
                if ($rr['classe'] === 1 && $i < $header['risposte']) {
                    if ($rr['tipo'] === $tipo && $tipo === 15 && $rr['lunghezza'] >= 3) {
                        $p = $posizione + 2;
                        $destinazione = self::leggiNome($risposta, $p);
                        if ($p > $fine) {
                            return $incerto;
                        }
                        $record[] = $destinazione === '' ? 'null_mx' : 'mx';
                    } elseif ($rr['tipo'] === $tipo
                        && (($tipo === 1 && $rr['lunghezza'] === 4) || ($tipo === 28 && $rr['lunghezza'] === 16))) {
                        $record[] = 'indirizzo';
                    } elseif ($rr['tipo'] === 5) {
                        $p = $posizione;
                        $alias = self::leggiNome($risposta, $p);
                        if ($p > $fine) {
                            return $incerto;
                        }
                    }
                } elseif ($rr['classe'] === 1 && $rr['tipo'] === 6) {
                    $soa = true;
                }
                $posizione = $fine;
            }
            if ($record === [] && $alias !== null) {
                return static::interroga($alias, $tipo, $scadenza, $salti + 1);
            }
            return ['stato' => 'risposta', 'record' => $record, 'negativa' => $soa && $record === []];
        } catch (Throwable $e) {
            return $incerto;
        } finally {
            fclose($socket);
        }
    }

    /** Legge i nomi compressi DNS imponendo limiti anche alle risposte malformate. */
    private static function leggiNome(string $pacchetto, int &$posizione): string
    {
        $etichette = [];
        $indice = $posizione;
        $ritorno = null;
        for ($i = 0; $i < 128; $i++) {
            if ($indice >= strlen($pacchetto)) {
                throw new RuntimeException('Risposta DNS incompleta.');
            }
            $lunghezza = ord($pacchetto[$indice++]);
            if (($lunghezza & 0xC0) === 0xC0) {
                if ($indice >= strlen($pacchetto)) {
                    throw new RuntimeException('Compressione DNS incompleta.');
                }
                $ritorno = $ritorno ?? $indice + 1;
                $indice = (($lunghezza & 0x3F) << 8) | ord($pacchetto[$indice]);
            } elseif ($lunghezza === 0) {
                $posizione = $ritorno ?? $indice;
                return implode('.', $etichette);
            } else {
                if ($lunghezza > 63 || $indice + $lunghezza > strlen($pacchetto)) {
                    throw new RuntimeException('Nome DNS non valido.');
                }
                $etichette[] = substr($pacchetto, $indice, $lunghezza);
                $indice += $lunghezza;
            }
        }
        throw new RuntimeException('Compressione DNS ciclica.');
    }
}
