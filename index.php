<?php
date_default_timezone_set('Europe/Rome');

require_once __DIR__ . '/Control/CFrontController.class.php';

$frontController =
    new CFrontController();

$frontController->run();
