<?php

require_once __DIR__ . '/VBase.class.php';

class VRegistrazione extends VBase
{
    public function mostraRegistrazione($errori = [], $valori = [])
    {
        $this->setDataIntoTemplate('errori', $errori);
        $this->setDataIntoTemplate('valori', $valori);

        $this->setTemplate('registrazione.tpl');
    }
}
