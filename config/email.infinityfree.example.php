<?php

if (($caricamentoConfigurazioneEmailLumea ?? false) !== true) {
    http_response_code(403);
    exit;
}

return [
    // Lasciare disabled durante installazione e verifica dell'autenticazione.
    'mode' => 'disabled',
    'production_enabled' => false,
    'host' => 'SMTP_HOST',
    'port' => 587,
    'security' => 'starttls',
    'username' => 'SMTP_USER',
    'password' => 'SMTP_PASSWORD',
    'sender' => 'SMTP_SENDER',
    // Un solo indirizzo autorizzato, nessun elenco di destinatari.
    'test_recipient' => 'DESTINATARIO_TEST_AUTORIZZATO',
    'test_database' => 'DB_NAME',
    'test_manual_only' => true,
    'test_tools_enabled' => false,
    'log_path' => __DIR__ . '/email-test.log'
];
