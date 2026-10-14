<?php

require_once __DIR__ . '/VBase.class.php';

class VSpeseCliente extends VBase
{
    public function mostraSpese(array $dati): void
    {
        $this->setDataIntoTemplate('versioneCSSCliente', $this->versioneRisorsa('css/cliente.css'));
        $this->setDataIntoTemplate('utente', $dati['utente']);
        $this->setDataIntoTemplate('periodi', $dati['periodi']);
        $this->setDataIntoTemplate('periodoSelezionato', $dati['periodo_selezionato']);
        $this->setDataIntoTemplate('titoloPeriodo', $dati['titolo_periodo']);
        $this->setDataIntoTemplate('righe', $dati['righe']);
        $this->setDataIntoTemplate('navigazione', $dati['navigazione']);
        $this->setDataIntoTemplate('totaleSelezionato', $dati['totale_selezionato']);
        $this->setTemplate('spese_cliente.tpl');
    }
}
