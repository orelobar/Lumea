<?php

if (($caricamentoConfigurazioneEmailLumea ?? false) !== true) {
    http_response_code(403);
    exit;
}

return [
    // Lasciare disabled durante installazione e verifica dell'autenticazione.
    'mode' => 'disabled',
    'production_enabled' => false,
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'security' => 'starttls',
    'username' => '',
    'password' => '',
    'sender' => '',
    // Un solo indirizzo autorizzato, nessun elenco di destinatari.
    'test_recipient' => '',
    'test_database' => 'if0_43278145_lumea',
    'test_manual_only' => true,
    'test_tools_enabled' => false,
    'log_path' => __DIR__ . '/email-test.log'
];
