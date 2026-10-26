<?php

require_once __DIR__ . '/FTrasportoEmail.class.php';

/** Servizio tecnico per registrare o trasmettere un contenuto email già presentato. */
class FEmail
{
    public static function invia(string $destinatario, string $oggetto, array $contenuto, array $config): bool
    {
        $mode = $config['mode'] ?? 'disabled';
        if ($mode === 'log') {
            $path = getenv('LUMEA_EMAIL_LOG') ?: ($config['log_path'] ?? sys_get_temp_dir() . '/lumea_email.log');
            $record = '[' . date('c') . "]\nA: " . $destinatario . "\nOggetto: " . $oggetto
                . "\n\n" . $contenuto['testo'] . "\n--- HTML ---\n" . $contenuto['html'] . "\n---\n";
            if (@file_put_contents($path, $record, FILE_APPEND | LOCK_EX) === false) {
                throw new RuntimeException('Scrittura log email QA non riuscita.');
            }
            return true;
        }
        if (!in_array($mode, ['smtp_test', 'smtp'], true)) {
            throw new RuntimeException('Trasporto email non abilitato o configurato.');
        }
        return FTrasportoEmail::invia($destinatario, $oggetto, $contenuto, $config);
    }
}
