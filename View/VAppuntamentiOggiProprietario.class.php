<?php
require_once __DIR__ . '/VBase.class.php';

class VAppuntamentiOggiProprietario extends VBase
{
    public function mostraAppuntamenti(array $dati): void
    {
        foreach ($dati as $nome => $valore) {
            $this->setDataIntoTemplate($nome, $valore);
        }
        $this->setTemplate('appuntamenti_oggi_proprietario.tpl');
    }
}
