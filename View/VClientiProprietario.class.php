<?php
require_once __DIR__ . '/VBase.class.php';

class VClientiProprietario extends VBase
{
    public function mostraClienti(array $dati): void
    {
        foreach ($dati as $nome => $valore) {
            $this->setDataIntoTemplate($nome, $valore);
        }
        $this->setTemplate('clienti_proprietario.tpl');
    }
}
