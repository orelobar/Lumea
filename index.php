<?php
date_default_timezone_set('Europe/Rome');

require_once __DIR__ . '/Control/CFrontController.class.php';

try {
    $frontController = new CFrontController();
    $frontController->run();
} catch (Throwable $e) {
    // Non inviare dettagli tecnici o argomenti delle eccezioni al browser.
    error_log('Lumèa: errore applicativo non gestito (' . get_class($e) . ').');
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: no-store');
    echo 'Servizio temporaneamente non disponibile. Riprova più tardi.';
}
