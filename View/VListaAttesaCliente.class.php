<?php

require_once __DIR__ . '/VBase.class.php';

class VListaAttesaCliente extends VBase
{
    public function mostraListaAttesa(array $dati): void
    {
        $this->setDataIntoTemplate('versioneCSSCliente', $this->versioneRisorsa('css/cliente.css'));
        $this->setDataIntoTemplate('richieste', $dati['richieste']);
        $this->setDataIntoTemplate('messaggio', $dati['messaggio']);
        $this->setDataIntoTemplate('errore', $dati['errore']);
        $gruppi = [
            'attiva' => ['titolo' => 'In attesa', 'richieste' => []],
            'soddisfatta' => ['titolo' => 'Orari assegnati', 'richieste' => []],
            'scaduta' => ['titolo' => 'Richieste scadute', 'richieste' => []],
            'annullata' => ['titolo' => 'Richieste annullate', 'richieste' => []]
        ];
        foreach ($dati['richieste'] as $richiesta) {
            $stato = $richiesta['stato'];
            if (!isset($gruppi[$stato])) $gruppi[$stato] = ['titolo' => $stato, 'richieste' => []];
            $gruppi[$stato]['richieste'][] = $richiesta;
        }
        $this->setDataIntoTemplate('gruppiRichieste', $gruppi);
        $this->setDataIntoTemplate('puoCercare', $dati['puo_cercare']);
        $this->setDataIntoTemplate('urlRicerca', $dati['url_ricerca']);
        $this->setDataIntoTemplate('prenotazioniAbilitate', $dati['prenotazioni_abilitate']);
        $this->setTemplate('lista_attesa_cliente.tpl');
    }
}
