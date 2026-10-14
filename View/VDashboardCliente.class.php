<?php

require_once __DIR__ . '/VBase.class.php';

class VDashboardCliente extends VBase
{
    public function mostraDashboard(array $dati): void
    {
        $this->setDataIntoTemplate('utente', $dati['utente']);
        $this->setDataIntoTemplate('appuntamentoDaChiudere', $dati['appuntamento_da_chiudere']);
        $this->setDataIntoTemplate(
            'prossimoAppuntamento',
            $dati['prossimo_appuntamento']
        );
        $this->setDataIntoTemplate(
            'prossimoAppuntamentoDettaglio',
            $dati['prossimo_appuntamento_dettaglio']
        );
        $this->setDataIntoTemplate(
            'ultimaPrenotazioneRipetibile',
            $dati['ultima_prenotazione_ripetibile']
        );
        $this->setDataIntoTemplate(
            'prenotazioniOnlineAbilitate',
            $dati['prenotazioni_online_abilitate']
        );
        $this->setDataIntoTemplate(
            'riepilogoCliente',
            $dati['riepilogo_cliente']
        );
        $this->setTemplate('dashboard_cliente.tpl');
    }
}
