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


    public static function connect(): PDO
    {
        static $connection = null;

        if ($connection === null) {
            $host = "localhost";
            // Configurazione dell'ambiente: il valore predefinito resta il DB locale.
            $dbName = getenv('LUMEA_DB_NAME') ?: 'lumea';
            if (!preg_match('/^[a-zA-Z0-9_]+$/D', $dbName)) {
                throw new RuntimeException('Nome database non valido.');
            }
            $username = "root";
            $password = "";

            $dsn =
                "mysql:host=" . $host .
                ";dbname=" . $dbName .
                ";charset=utf8mb4";

            $connection = new PDO(
                $dsn,
                $username,
                $password
            );

            $connection->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );
        }

        return $connection;
    }
}
