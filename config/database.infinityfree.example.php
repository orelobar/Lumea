<?php

// Copiare come database.infinityfree.php SOLO dopo la verifica HTTP della cartella.
if (($caricamentoConfigurazioneDatabaseLumea ?? false) !== true) {
    http_response_code(403);
    exit;
}

return [
    'environment' => 'production',
    'host' => 'DB_HOST',
    'port' => 3306,
    'name' => 'DB_NAME',
    'user' => 'DB_USER',
    // Inserire la password del database nel solo file privato sul server.
    'password' => 'DB_PASSWORD'
];
