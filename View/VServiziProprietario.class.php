<?php
require_once __DIR__ . '/VBase.class.php';

class VServiziProprietario extends VBase
{
    public function mostraServizi(array $dati): void
    {
        foreach ($dati as $nome => $valore) {
            $this->setDataIntoTemplate($nome, $valore);
        }
        $this->setTemplate('servizi_proprietario.tpl');
    }
}
