<?php

// Copiare come database.infinityfree.php SOLO dopo la verifica HTTP della cartella.
if (($caricamentoConfigurazioneDatabaseLumea ?? false) !== true) {
    http_response_code(403);
    exit;
}

return [
    'environment' => 'production',
    'host' => 'sql311.infinityfree.com',
    'port' => 3306,
    'name' => 'if0_43278145_lumea',
    'user' => 'if0_43278145',
    // Inserire la password del database nel solo file privato sul server.
    'password' => ''
];
