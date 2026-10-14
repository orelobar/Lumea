<?php

require_once __DIR__ . '/VBase.class.php';

class VAppuntamentiCliente extends VBase
{
    public function mostraAppuntamenti(
        $appuntamentoAttivo,
        array $storico,
        bool $puoAnnullare,
        $richiestaAttesa = null,
        ?string $messaggio = null,
        ?string $errore = null,
        array $ripetibili = [],
        ?array $appuntamentoAttivoDettaglio = null,
        array $storicoDettagli = [],
        bool $prenotazioniOnlineAbilitate = true
    ) {
        $this->setDataIntoTemplate('prenotazioniOnlineAbilitate', $prenotazioniOnlineAbilitate);
        $this->setDataIntoTemplate('ripetibili', $ripetibili);
        $this->setDataIntoTemplate('appuntamentoAttivoDettaglio', $appuntamentoAttivoDettaglio);
        $this->setDataIntoTemplate('storicoDettagli', $storicoDettagli);
        $this->setDataIntoTemplate(
            'appuntamentoAttivo',
            $appuntamentoAttivo
        );

        $this->setDataIntoTemplate(
            'storico',
            $storico
        );

        $this->setDataIntoTemplate(
            'puoAnnullare',
            $puoAnnullare
        );

        $this->setDataIntoTemplate(
            'richiestaAttesa',
            $richiestaAttesa
        );

        $this->setDataIntoTemplate(
            'messaggio',
            $messaggio
        );

        $this->setDataIntoTemplate(
            'errore',
            $errore
        );

        $this->setTemplate(
            'appuntamenti_cliente.tpl'
        );
    }
}
