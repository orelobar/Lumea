<?php

require_once __DIR__ . '/VBase.class.php';

class VNuovaListaAttesaCliente extends VBase
{
    public function mostraNuovaRichiesta(array $dati): void
    {
        $this->setDataIntoTemplate('utente', $dati['utente']);
        $this->setDataIntoTemplate('servizio', $dati['servizio']);
        $this->setDataIntoTemplate('servizi', $dati['servizi']);
        $this->setDataIntoTemplate('appuntamentoAttuale', $dati['appuntamento_attuale']);
        $this->setDataIntoTemplate('slotAttesa', $dati['slot_attesa']);
        $this->setDataIntoTemplate('mesiAttesa', $this->raggruppaPerMese($dati['slot_attesa']));
        $this->setDataIntoTemplate('puoAttendere', $dati['puo_attendere']);
        $this->setDataIntoTemplate('richiestaAttiva', $dati['richiesta_attiva']);
        $this->setDataIntoTemplate('errore', $dati['errore']);
        $this->setTemplate('lista_attesa_nuova_cliente.tpl');
    }

    /** Organizza per la visualizzazione gli orari già validati dal Control. */
    private function raggruppaPerMese(array $slotAttesa): array
    {
        $nomiMesi = [1 => 'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio',
            'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];
        $nomiGiorni = ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];
        $mesi = [];

        foreach ($slotAttesa as $slot) {
            $dataIso = explode('|', $slot['valore'])[0];
            $data = new DateTimeImmutable($dataIso);
            $chiaveMese = $data->format('Y-m');

            if (!isset($mesi[$chiaveMese])) {
                $mesi[$chiaveMese] = [
                    'titolo' => $nomiMesi[(int) $data->format('n')] . ' ' . $data->format('Y'),
                    'totale_orari' => 0,
                    'giorni' => []
                ];
            }
            if (!isset($mesi[$chiaveMese]['giorni'][$dataIso])) {
                $mesi[$chiaveMese]['giorni'][$dataIso] = [
                    'data_iso' => $dataIso,
                    'titolo' => $nomiGiorni[(int) $data->format('w')] . ' '
                        . $data->format('j') . ' ' . strtolower($nomiMesi[(int) $data->format('n')]),
                    'slot' => []
                ];
            }
            $mesi[$chiaveMese]['giorni'][$dataIso]['slot'][] = $slot;
            $mesi[$chiaveMese]['totale_orari']++;
        }

        ksort($mesi);
        foreach ($mesi as &$mese) {
            ksort($mese['giorni']);
        }
        unset($mese);

        return $mesi;
    }
}
