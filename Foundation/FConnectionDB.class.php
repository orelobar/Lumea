<?php

class FConnectionDB
{
    private static string $host = "localhost";
    private static string $dbName = "hairbooking";
    private static string $username = "root";
    private static string $password = "";

    public static function connect(): PDO
    {
        $dsn =
            "mysql:host=" . self::$host .
            ";dbname=" . self::$dbName .
            ";charset=utf8mb4";

        $connection = new PDO(
            $dsn,
            self::$username,
            self::$password
        );

        $connection->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        return $connection;
    }
}
