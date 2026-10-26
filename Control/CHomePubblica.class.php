<?php

require_once __DIR__ . '/../Foundation/FSessione.class.php';
require_once __DIR__ . '/../View/VHomePubblica.class.php';

class CHomePubblica
{
    public function mostraHome(array $request = []): void
    {
        if (FSessione::isAutenticato()) {
            VBase::redirect('index.php?pagina=dashboard');
            exit;
        }

        (new VHomePubblica())->mostraHome();
    }
}
