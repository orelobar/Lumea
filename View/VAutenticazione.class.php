<?php

require_once __DIR__ . '/VBase.class.php';

class VAutenticazione extends VBase
{
    public function mostraLogin($errori = [], $valori = [])
    {
        $this->setDataIntoTemplate('errori', $errori);
        $this->setDataIntoTemplate('valori', $valori);

        $this->setTemplate('login.tpl');
    }
}
