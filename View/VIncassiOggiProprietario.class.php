<?php
require_once __DIR__ . '/VBase.class.php';

class VIncassiOggiProprietario extends VBase
{
    public function mostraIncassi(array $dati): void
    {
        foreach ($dati as $nome => $valore) {
            $this->setDataIntoTemplate($nome, $valore);
        }
        $this->setTemplate('incassi_oggi_proprietario.tpl');
    }
}
