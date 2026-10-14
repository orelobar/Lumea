<?php
require_once __DIR__ . '/VBase.class.php';

class VStatisticheProprietario extends VBase
{
    public function mostraStatistiche(array $dati): void
    {
        foreach ($dati as $nome => $valore) {
            $this->setDataIntoTemplate($nome, $valore);
        }
        $this->setTemplate('statistiche_proprietario.tpl');
    }
}
