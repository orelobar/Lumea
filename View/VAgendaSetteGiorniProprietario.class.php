<?php
require_once __DIR__ . '/VBase.class.php';

class VAgendaSetteGiorniProprietario extends VBase
{
    public function mostraAgenda(array $dati): void
    {
        foreach ($dati as $nome => $valore) {
            $this->setDataIntoTemplate($nome, $valore);
        }

        $this->setTemplate('agenda_sette_giorni_proprietario.tpl');
    }
}
