<?php

class FConnectionDB
{
    public static function connect(): PDO
    {
        static $connection = null;

        if ($connection === null) {
            $host = "localhost";
            $dbName = "hairbooking";
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
