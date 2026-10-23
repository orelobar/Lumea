<?php

class FConnectionDB
{
    public static function bloccaCalendario(): void
    {
        $connection = self::connect();
        if (!$connection->inTransaction()) {
            throw new LogicException('Il calendario richiede una transazione attiva.');
        }
        $statement = $connection->query(
            "SELECT id FROM utenti WHERE ruolo = 'proprietario' ORDER BY id FOR UPDATE"
        );
        if (!$statement->fetchAll(PDO::FETCH_COLUMN)) {
            throw new RuntimeException('Proprietario del salone non configurato.');
        }
    }

    /** Configurazione privata, esterna al DocumentRoot; le variabili ambiente hanno precedenza. */
    private static function configurazione(): array
    {
        $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        $documentRoot = realpath($documentRoot ?: dirname(__DIR__, 2));
        $path = getenv('LUMEA_DB_CONFIG') ?: dirname($documentRoot) . '/lumea-private/database.json';
        $config = [];
        if (is_file($path)) {
            $real = realpath($path);
            if (!$real || $real === $documentRoot || str_starts_with($real, $documentRoot . DIRECTORY_SEPARATOR)
                || (fileperms($real) & 0007) !== 0 || !is_readable($real)) {
                throw new RuntimeException('Configurazione database privata non accessibile o non sicura.');
            }
            try {
                $config = json_decode(file_get_contents($real), true, 16, JSON_THROW_ON_ERROR);
            } catch (Throwable $e) {
                throw new RuntimeException('Configurazione database non valida.');
            }
            if (!is_array($config)) {
                throw new RuntimeException('Configurazione database non valida.');
            }
        }
        foreach (['host', 'name', 'user', 'password', 'port', 'socket', 'environment'] as $key) {
            $value = getenv($key === 'environment' ? 'LUMEA_ENV' : 'LUMEA_DB_' . strtoupper($key));
            if ($value !== false) {
                $config[$key] = $value;
            }
        }
        foreach (['host', 'name', 'user', 'password'] as $key) {
            if (!isset($config[$key]) || !is_string($config[$key])) {
                throw new RuntimeException('Configurazione database incompleta.');
            }
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/D', $config['name'])
            || $config['host'] === '' || preg_match('/[;\x00-\x20]/', $config['host'])
            || $config['user'] === '' || str_contains($config['user'], "\0")) {
            throw new RuntimeException('Parametri database non validi.');
        }
        $environment = $config['environment'] ?? 'production';
        if (!in_array($environment, ['local', 'test', 'production'], true)
            || ($environment === 'production' && (strtolower($config['user']) === 'root' || $config['password'] === ''))) {
            throw new RuntimeException('La produzione richiede un utente database dedicato e una password.');
        }
        $config['port'] = $config['port'] ?? 3306;
        if (filter_var($config['port'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]) === false
            || (isset($config['socket']) && (!is_string($config['socket']) || preg_match('/[;\x00-\x1f]/', $config['socket'])))) {
            throw new RuntimeException('Porta o socket database non valido.');
        }
        return $config;
    }

    public static function connect(): PDO
    {
        static $connection = null;
        if ($connection === null) {
            $config = self::configurazione();
            $server = !empty($config['socket'])
                ? 'unix_socket=' . $config['socket']
                : 'host=' . $config['host'] . ';port=' . $config['port'];
            $dsn = 'mysql:' . $server . ';dbname=' . $config['name'] . ';charset=utf8mb4';
            try {
                $connection = new PDO($dsn, $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            } catch (PDOException $e) {
                // Non propagare DSN, credenziali o il messaggio del server nella risposta HTTP.
                throw new RuntimeException('Connessione al database non disponibile.');
            }
        }
        return $connection;
    }
}
