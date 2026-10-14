<?php

require_once __DIR__ . '/VBase.class.php';

class VPrenotazioneCliente extends VBase
{
    public function mostraPrenotazione(array $dati)
    {
        $this->setDataIntoTemplate('versioneCSSCliente', $this->versioneRisorsa('css/cliente.css'));
        $this->setDataIntoTemplate('versioneJSPrenotazione', $this->versioneRisorsa('js/prenotazione-cliente.js'));
        $this->setDataIntoTemplate('puoPrenotare', $dati['puo_prenotare']);
        $this->setDataIntoTemplate('puoAttendere', $dati['puo_attendere']);
        $this->setDataIntoTemplate('richiestaAttiva', $dati['richiesta_attiva']);
        $this->setDataIntoTemplate('slotAttesa', $dati['slot_attesa']);
        $this->setDataIntoTemplate(
            'utente',
            $dati['utente']
        );

        $this->setDataIntoTemplate(
            'step',
            $dati['step']
        );

        $this->setDataIntoTemplate(
            'categoria',
            $dati['categoria']
        );

        $this->setDataIntoTemplate(
            'servizi',
            $dati['servizi']
        );

        $this->setDataIntoTemplate(
            'servizioSelezionato',
            $dati['servizio_selezionato']
        );

        $this->setDataIntoTemplate(
            'disponibilita',
            $dati['disponibilita']
        );

        $this->setDataIntoTemplate(
            'dataSelezionata',
            $dati['data_selezionata']
        );

        $this->setDataIntoTemplate(
            'orariData',
            $dati['orari_data']
        );

        $this->setDataIntoTemplate(
            'oraSelezionata',
            $dati['ora_selezionata']
        );

        $this->setDataIntoTemplate(
            'errore',
            $dati['errore']
        );

        $this->setDataIntoTemplate(
            'appuntamentoAttivo',
            $dati['appuntamento_attivo']
        );

        $this->setDataIntoTemplate(
            'storicoPrenotazioni',
            $dati['storico_prenotazioni']
        );

        $this->setDataIntoTemplate(
            'dataMinimaAttesa',
            $dati['data_minima_attesa']
        );

        $calendario = $this->creaCalendario(
            $dati['disponibilita'],
            $dati['data_selezionata'],
            $dati['mese_calendario'] ?? null
        );
        $this->setDataIntoTemplate('calendario', $calendario);
        $this->setDataIntoTemplate('meseSelezionato', substr($dati['data_selezionata'] ?? '', 0, 7));
        $this->setDataIntoTemplate('dataRiepilogo', $this->dataEstesa($dati['data_selezionata']));
        $ora = $dati['ora_selezionata'];
        $inizio = is_string($ora) ? DateTimeImmutable::createFromFormat('!H:i', $ora) : false;
        $fine = $inizio && $dati['servizio_selezionato'] !== null
            ? $inizio->modify('+' . $dati['servizio_selezionato']->getDurata() . ' minutes')->format('H:i')
            : '';
        $this->setDataIntoTemplate('oraFineRiepilogo', $fine);

        $this->setTemplate(
            'prenotazione_cliente.tpl'
        );
    }

    /** Impagina le date già prenotabili; non calcola disponibilità o nuovi slot. */
    private function creaCalendario(array $disponibilita, ?string $selezionata, $meseRichiesto): array
    {
        $oggi = new DateTimeImmutable('today');
        $primo = $oggi->modify('first day of this month');
        $date = array_keys(array_filter($disponibilita));
        sort($date);
        $ultimo = $date ? new DateTimeImmutable(end($date)) : $oggi;
        $ultimo = $ultimo->modify('first day of this month');
        $mesi = [1 => 'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
            'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];
        $aperto = $selezionata === null ? $primo->format('Y-m') : substr($selezionata, 0, 7);
        if (is_string($meseRichiesto) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $meseRichiesto)
            && $meseRichiesto >= $primo->format('Y-m') && $meseRichiesto <= $ultimo->format('Y-m')) {
            $aperto = $meseRichiesto;
        }
        $risultato = [];
        for ($mese = $primo; $mese <= $ultimo; $mese = $mese->modify('+1 month')) {
            $giorni = array_fill(0, (int) $mese->format('N') - 1, null);
            $numeroDisponibili = 0;
            for ($numero = 1; $numero <= (int) $mese->format('t'); $numero++) {
                $data = $mese->setDate((int) $mese->format('Y'), (int) $mese->format('n'), $numero);
                $chiave = $data->format('Y-m-d');
                $disponibile = !empty($disponibilita[$chiave]);
                $numeroDisponibili += $disponibile ? 1 : 0;
                $giorni[] = ['numero' => $numero, 'data' => $chiave,
                    'etichetta' => $this->dataEstesa($chiave), 'disponibile' => $disponibile,
                    'selezionato' => $chiave === $selezionata, 'oggi' => $chiave === $oggi->format('Y-m-d')];
            }
            while (count($giorni) % 7 !== 0) $giorni[] = null;
            $risultato[] = ['chiave' => $mese->format('Y-m'),
                'titolo' => $mesi[(int) $mese->format('n')] . ' ' . $mese->format('Y'),
                'aperto' => $mese->format('Y-m') === $aperto, 'giorni' => $giorni,
                'numero_disponibili' => $numeroDisponibili];
        }
        return $risultato;
    }

    private function dataEstesa(?string $data): string
    {
        if ($data === null) return '';
        $valore = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
        if ($valore === false) return '';
        $giorni = [1 => 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato', 'Domenica'];
        $mesi = [1 => 'gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno',
            'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre'];
        return $giorni[(int) $valore->format('N')] . ' ' . $valore->format('j') . ' '
            . $mesi[(int) $valore->format('n')] . ' ' . $valore->format('Y');
    }
}
