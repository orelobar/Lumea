<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/Foundation/FConnectionDB.class.php';

try {

    $connection = FConnectionDB::connect();

    echo "Connessione al database riuscita";
} catch (PDOException $e) {

    echo "Errore di connessione: "
        . htmlspecialchars($e->getMessage());
}
