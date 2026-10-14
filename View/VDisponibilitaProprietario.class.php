<?php
require_once __DIR__ . '/VBase.class.php';

class VDisponibilitaProprietario extends VBase
{
    public function mostraDisponibilita(array $dati): void
    {
        foreach ($dati as $nome => $valore) {
            $this->setDataIntoTemplate($nome, $valore);
        }
        $this->setTemplate('disponibilita_proprietario.tpl');
    }
}
