<?php

require_once __DIR__ . '/VBase.class.php';

class VHomePubblica extends VBase
{
    public function mostraHome(): void
    {
        $this->setTemplate('home_pubblica.tpl');
    }
}
