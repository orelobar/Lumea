<?php

require_once __DIR__ . '/../Foundation/FSessione.class.php';
require_once __DIR__ . '/../Foundation/FUtente.class.php';
require_once __DIR__ . '/../Foundation/FPrenotazione.class.php';
require_once __DIR__ . '/../Foundation/FPagamento.class.php';
require_once __DIR__ . '/../Foundation/FDisponibilita.class.php';

require_once __DIR__ . '/CGestioneFedelta.class.php';

require_once __DIR__ . '/../Entity/EUtente.class.php';

require_once __DIR__ . '/../View/VDashboardCliente.class.php';
require_once __DIR__ . '/../View/VDashboardProprietario.class.php';
require_once __DIR__ . '/../View/VAppuntamentiOggiProprietario.class.php';
require_once __DIR__ . '/../View/VIncassiOggiProprietario.class.php';

class CGestioneDashboard
{
    public function mostraDashboard(
        array $request = []
    ): void {
        $utente =
            $this->getUtenteAutenticato();

        if ($utente === null) {
            $this->redirect(
                'index.php?pagina=login'
            );
        }

        if ($utente->isCliente()) {
            $dati =
                $this->dashboardCliente();

            if ($dati === null) {
                $this->redirect(
                    'index.php?pagina=login'
                );
            }

            $view =
                new VDashboardCliente();

            $view->mostraDashboard(
                $dati
            );

            return;
        }

        if ($utente->isProprietario()) {
            $dati =
                $this->dashboardProprietario();

            if ($dati === null) {
                $this->redirect(
                    'index.php?pagina=login'
                );
            }

            $view =
                new VDashboardProprietario();

            $view->mostraDashboard(
                $dati
            );

            return;
        }

        $this->redirect(
            'index.php?pagina=login'
        );
    }


    public function mostraAppuntamentiOggi(array $request = []): void
    {
        $dati = $this->dettaglioGiornataProprietario();

        if ($dati === null) {
            $this->redirect('index.php');
        }

        $get = $request['get'] ?? [];
        $filtro = is_string($get['filtro'] ?? null)
            ? $get['filtro']
            : 'tutti';

        if (!in_array($filtro, ['tutti', 'da_svolgere', 'completati', 'assenze'], true)) {
            $filtro = 'tutti';
        }

        $titoli = [
            'tutti' => 'Tutti gli appuntamenti',
            'da_svolgere' => 'Ancora da svolgere',
            'completati' => 'Appuntamenti completati',
            'assenze' => 'Assenze di oggi'
        ];

        if ($filtro !== 'tutti') {
            $statoRichiesto = [
                'da_svolgere' => 'prenotata',
                'completati' => 'completata',
                'assenze' => 'no_show'
            ][$filtro];

            $dati['appuntamenti'] = array_values(
                array_filter(
                    $dati['appuntamenti'],
                    static fn(array $riga): bool =>
                        ($riga['stato'] ?? '') === $statoRichiesto
                )
            );
        }

        $dati['filtro_attivo'] = $filtro;
        $dati['titolo_elenco'] = $titoli[$filtro];
        $dati['messaggio'] = isset($get['esito'])
            ? 'Appuntamento aggiornato.'
            : null;
        $dati['errore'] = isset($get['errore'])
            ? 'Operazione non riuscita. Controlla stato, orario e coupon.'
            : null;

        (new VAppuntamentiOggiProprietario())->mostraAppuntamenti($dati);
    }

    public function mostraIncassiOggi(array $request = []): void
    {
        $dati = $this->dettaglioGiornataProprietario();
        if ($dati === null) {
            $this->redirect('index.php');
        }
        (new VIncassiOggiProprietario())->mostraIncassi($dati);
    }


    public function dashboardCliente(): ?array
    {
        $utente = $this->getUtenteAutenticato();

        if ($utente === null || !$utente->isCliente()) {
            return null;
        }

        $idCliente = $utente->getId();

        if ($idCliente === null) {
            return null;
        }

        $prenotazioni = FPrenotazione::loadByCliente($idCliente);
        $prossimoAppuntamento = null;
        $appuntamentoDaChiudere = false;
        $timestampProssimo = null;
        $ultimaPrenotazioneRipetibile = null;
        $adesso = time();

        foreach ($prenotazioni as $prenotazione) {
            if ($prenotazione->getStato() === 'prenotata') {
                $timestamp = strtotime(
                    $prenotazione->getData() . ' ' . $prenotazione->getOra()
                );

                if ($timestamp !== false && $timestamp < $adesso) {
                    $appuntamentoDaChiudere = true;
                }

                if (
                    $timestamp !== false
                    && $timestamp >= $adesso
                    && ($timestampProssimo === null || $timestamp < $timestampProssimo)
                ) {
                    $prossimoAppuntamento = $prenotazione;
                    $timestampProssimo = $timestamp;
                }
            }

            if (
                $ultimaPrenotazioneRipetibile === null
                && in_array($prenotazione->getStato(), ['completata', 'no-show'], true)
                && $prenotazione->getServizio()->isAttivo()
            ) {
                $ultimaPrenotazioneRipetibile = $prenotazione;
            }
        }

        if (
            $prossimoAppuntamento !== null
            || $appuntamentoDaChiudere
            || !$utente->isPrenotazioniOnlineAbilitate()
        ) {
            $ultimaPrenotazioneRipetibile = null;
        }

        $dettaglioProssimo = null;

        if ($prossimoAppuntamento !== null) {
            $oraInizio = substr($prossimoAppuntamento->getOra(), 0, 5);
            $dettaglioProssimo = [
                'data' => $this->formattaDataItaliana(
                    $prossimoAppuntamento->getData(),
                    true
                ),
                'ora_inizio' => $oraInizio,
                'ora_fine' => $this->calcolaOraFine(
                    $oraInizio,
                    $prossimoAppuntamento->getDurata()
                ),
                'servizio' => $prossimoAppuntamento->getNomeServizio(),
                'servizio_id' => $prossimoAppuntamento->getServizio()->getId(),
                'categoria' => $prossimoAppuntamento->getServizio()->getCategoria(),
                'durata' => $prossimoAppuntamento->getDurata()
            ];
        }

        $riepilogoCliente = (new CGestioneFedelta())
            ->riepilogoCliente($idCliente);

        return [
            'utente' => $utente,
            'prossimo_appuntamento' => $prossimoAppuntamento,
            'appuntamento_da_chiudere' => $appuntamentoDaChiudere,
            'prossimo_appuntamento_dettaglio' => $dettaglioProssimo,
            'ultima_prenotazione_ripetibile' => $ultimaPrenotazioneRipetibile,
            'prenotazioni_online_abilitate' =>
                $utente->isPrenotazioniOnlineAbilitate(),
            'riepilogo_cliente' => $riepilogoCliente
        ];
    }


    public function dashboardProprietario(): ?array
    {
        $utente = $this->getUtenteAutenticato();

        if ($utente === null || !$utente->isProprietario()) {
            return null;
        }

        $oggi = date('Y-m-d');
        $adesso = time();
        $prenotazioni = FPrenotazione::loadByData($oggi);

        $appuntamentiOggi = [];
        $prossimiAppuntamenti = [];
        $conteggiStato = [
            'prenotata' => 0,
            'completata' => 0,
            'no-show' => 0
        ];
        $incassoPrevistoCentesimi = 0;
        $incassoEffettivoCentesimi = 0;

        foreach ($prenotazioni as $prenotazione) {
            $stato = $prenotazione->getStato();

            if ($stato === 'cancellata') {
                continue;
            }

            $appuntamentiOggi[] = $prenotazione;

            if (isset($conteggiStato[$stato])) {
                $conteggiStato[$stato]++;
            }

            if (in_array($stato, ['prenotata', 'completata'], true)) {
                $incassoPrevistoCentesimi += (int) round(
                    $prenotazione->getPrezzo() * 100
                );
            }

            if ($stato === 'prenotata') {
                $dataOraPrenotazione = strtotime(
                    $prenotazione->getData() . ' ' . $prenotazione->getOra()
                );

                if (
                    $dataOraPrenotazione !== false
                    && $dataOraPrenotazione >= $adesso
                ) {
                    $prossimiAppuntamenti[] =
                        $this->creaRigaAppuntamentoDashboard($prenotazione);
                }
            }

            if ($stato === 'completata') {
                $idPrenotazione = $prenotazione->getId();

                if ($idPrenotazione === null) {
                    continue;
                }

                $pagamento = FPagamento::loadByPrenotazione($idPrenotazione);

                $importoEffettivo = $pagamento !== null
                    ? $pagamento->getImportoPagato()
                    : $prenotazione->getPrezzo();

                $incassoEffettivoCentesimi += (int) round(
                    $importoEffettivo * 100
                );
            }
        }

        $dateAperte = FDisponibilita::loadDateAperte($oggi);
        $finePeriodo = $dateAperte ? $dateAperte[count($dateAperte) - 1] : $oggi;
        $prenotazioniPeriodo = FPrenotazione::loadByPeriodo(
            $oggi,
            $finePeriodo
        );
        $numeroAppuntamentiPeriodo = 0;
        $appuntamentiSetteGiorni = [];
        $conteggiPerGiorno = [];

        foreach ($dateAperte as $dataGiorno) {
            $conteggiPerGiorno[$dataGiorno] = 0;
        }

        foreach ($prenotazioniPeriodo as $prenotazione) {
            if ($prenotazione->getStato() === 'cancellata'
                || !isset($conteggiPerGiorno[$prenotazione->getData()])) {
                continue;
            }

            $dataPrenotazione = $prenotazione->getData();
            if (array_key_exists($dataPrenotazione, $conteggiPerGiorno)) {
                $conteggiPerGiorno[$dataPrenotazione]++;
            }

            if ($prenotazione->getStato() !== 'prenotata') {
                continue;
            }

            $dataOraPrenotazione = strtotime(
                $prenotazione->getData() . ' ' . $prenotazione->getOra()
            );

            if ($dataOraPrenotazione !== false && $dataOraPrenotazione >= $adesso) {
                $numeroAppuntamentiPeriodo++;
                $appuntamentiSetteGiorni[] = $this->creaRigaAppuntamentoDashboard($prenotazione);
            }
        }

        $giorniSetteGiorni = [];
        $giorniBrevi = [
            1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Gio',
            5 => 'Ven', 6 => 'Sab', 7 => 'Dom'
        ];

        foreach ($conteggiPerGiorno as $dataGiorno => $numeroGiorno) {
            $oggettoGiorno = DateTimeImmutable::createFromFormat('!Y-m-d', $dataGiorno);
            if ($oggettoGiorno === false) {
                continue;
            }

            $giorniSetteGiorni[] = [
                'data' => $dataGiorno,
                'oggi' => $dataGiorno === $oggi,
                'giorno_breve' => $giorniBrevi[(int) $oggettoGiorno->format('N')],
                'data_formattata' => $this->formattaDataItaliana($dataGiorno),
                'numero' => $numeroGiorno
            ];
        }

        usort(
            $prossimiAppuntamenti,
            static fn(array $a, array $b): int =>
                strcmp(
                    $a['data'] . ' ' . $a['ora_inizio'],
                    $b['data'] . ' ' . $b['ora_inizio']
                )
        );

        usort(
            $appuntamentiSetteGiorni,
            static fn(array $a, array $b): int =>
                strcmp(
                    $a['data'] . ' ' . $a['ora_inizio'],
                    $b['data'] . ' ' . $b['ora_inizio']
                )
        );

        $oraCorrente = (int) date('G');
        if ($oraCorrente < 12) {
            $saluto = 'Buongiorno';
        } elseif ($oraCorrente < 18) {
            $saluto = 'Buon pomeriggio';
        } else {
            $saluto = 'Buonasera';
        }

        return [
            'data' => $oggi,
            'data_formattata' => $this->formattaDataItaliana($oggi, true),
            'saluto' => $saluto,
            'numero_appuntamenti_oggi' => count($appuntamentiOggi),
            'numero_prenotati_oggi' => $conteggiStato['prenotata'],
            'numero_completati_oggi' => $conteggiStato['completata'],
            'numero_assenze_oggi' => $conteggiStato['no-show'],
            'numero_appuntamenti_sette_giorni' => $numeroAppuntamentiPeriodo,
            'appuntamenti_sette_giorni' => array_slice($appuntamentiSetteGiorni, 0, 5),
            'giorni_sette_giorni' => $giorniSetteGiorni,
            'prossimi_appuntamenti' => array_slice($prossimiAppuntamenti, 0, 3),
            'numero_prossimi_oggi' => count($prossimiAppuntamenti),
            'incasso_previsto_oggi' => round(
                $incassoPrevistoCentesimi / 100,
                2
            ),
            'nota_incasso_previsto' =>
                'Prima di eventuali coupon, cancellazioni o assenze.',
            'incasso_effettivo_oggi' => round(
                $incassoEffettivoCentesimi / 100,
                2
            )
        ];
    }

    private function dettaglioGiornataProprietario(): ?array
    {
        $utente = $this->getUtenteAutenticato();
        if ($utente === null || !$utente->isProprietario()) {
            return null;
        }

        $oggi = date('Y-m-d');
        $righeAppuntamenti = [];
        $righeIncassi = [];
        $conteggi = ['prenotata' => 0, 'completata' => 0, 'no_show' => 0];
        $previsto = 0.0;
        $effettivo = 0.0;
        $daRegistrare = 0.0;

        foreach (FPrenotazione::loadByData($oggi) as $prenotazione) {
            $stato = $prenotazione->getStato();
            $cliente = $prenotazione->getCliente();
            $id = $prenotazione->getId();
            $pagamento = $id === null ? null : FPagamento::loadByPrenotazione($id);

            if ($stato !== 'cancellata') {
                if ($stato === 'prenotata') { $conteggi['prenotata']++; }
                if ($stato === 'completata') { $conteggi['completata']++; }
                if ($stato === 'no-show') { $conteggi['no_show']++; }
                $righeAppuntamenti[] = [
                    'id' => $id,
                    'ora' => substr($prenotazione->getOra(), 0, 5),
                    'cliente' => trim($cliente->getNome() . ' ' . $cliente->getCognome()),
                    'telefono' => $cliente->getTelefono(),
                    'email' => $cliente->getEmail(),
                    'servizio' => $prenotazione->getNomeServizio(),
                    'prezzo' => $prenotazione->getPrezzo(),
                    'stato' => str_replace('-', '_', $stato),
                    'stato_etichetta' => $this->etichettaStato($stato),
                    'puo_aggiornare' => $stato === 'prenotata'
                        && strtotime($prenotazione->getData() . ' ' . $prenotazione->getOra()) <= time()
                ];
            }

            if (in_array($stato, ['prenotata', 'completata'], true)) {
                $previsto += $prenotazione->getPrezzo();
            }
            if ($stato === 'prenotata') {
                $daRegistrare += $prenotazione->getPrezzo();
            }
            if ($pagamento !== null) {
                $effettivo += $pagamento->getImportoPagato();
            } elseif ($stato === 'completata') {
                /*
                 * Compatibilità con eventuali dati storici creati
                 * prima dell'introduzione del pagamento atomico:
                 * un completato senza riga pagamento viene letto
                 * al prezzo storico della prenotazione.
                 */
                $effettivo += $prenotazione->getPrezzo();
            }

            if ($stato === 'cancellata') {
                continue;
            }

            $righeIncassi[] = [
                'cliente' => trim($cliente->getNome() . ' ' . $cliente->getCognome()),
                'servizio' => $prenotazione->getNomeServizio(),
                'prezzo' => $prenotazione->getPrezzo(),
                'coupon' => $pagamento === null ? 0.0 : $pagamento->getImportoCouponUtilizzato(),
                'effettivo' => $pagamento !== null
                    ? $pagamento->getImportoPagato()
                    : ($stato === 'completata' ? $prenotazione->getPrezzo() : 0.0),
                'pagato' => $pagamento !== null || $stato === 'completata',
                'stato_codice' => str_replace('-', '_', $stato),
                'stato' => $this->etichettaStato($stato)
            ];
        }

        return [
            'data' => $oggi,
            'data_formattata' => $this->formattaDataItaliana($oggi, true),
            'appuntamenti' => $righeAppuntamenti,
            'conteggi' => $conteggi,
            'incasso_previsto' => round($previsto, 2),
            'incasso_effettivo' => round($effettivo, 2),
            'incasso_da_registrare' => round($daRegistrare, 2),
            'righe' => $righeIncassi
        ];
    }

    private function etichettaStato(string $stato): string
    {
        return [
            'prenotata' => 'Da svolgere',
            'completata' => 'Completato',
            'no-show' => 'Non presentato',
            'cancellata' => 'Cancellato'
        ][$stato] ?? $stato;
    }


    private function creaRigaAppuntamentoDashboard(
        EPrenotazione $prenotazione
    ): array {
        $cliente = $prenotazione->getCliente();
        $oraInizio = substr($prenotazione->getOra(), 0, 5);

        return [
            'id' => $prenotazione->getId(),
            'data' => $prenotazione->getData(),
            'data_formattata' => $this->formattaDataItaliana($prenotazione->getData()),
            'ora_inizio' => $oraInizio,
            'ora_fine' => $this->calcolaOraFine(
                $oraInizio,
                $prenotazione->getDurata()
            ),
            'servizio' => $prenotazione->getNomeServizio(),
            'cliente' => trim(
                $cliente->getNome() . ' ' . $cliente->getCognome()
            )
        ];
    }

    private function calcolaOraFine(string $oraInizio, int $durata): string
    {
        $ora = DateTimeImmutable::createFromFormat('!H:i', $oraInizio);

        if ($ora === false) {
            return '';
        }

        return $ora->modify('+' . $durata . ' minutes')->format('H:i');
    }

    private function formattaDataItaliana(
        string $data,
        bool $includiGiornoSettimana = false
    ): string {
        $oggettoData = DateTimeImmutable::createFromFormat('!Y-m-d', $data);

        if ($oggettoData === false) {
            return $data;
        }

        $mesi = [
            1 => 'gennaio', 'febbraio', 'marzo', 'aprile', 'maggio',
            'giugno', 'luglio', 'agosto', 'settembre', 'ottobre',
            'novembre', 'dicembre'
        ];
        $giorni = [
            1 => 'lunedì', 'martedì', 'mercoledì', 'giovedì',
            'venerdì', 'sabato', 'domenica'
        ];

        $testo = sprintf(
            '%d %s %s',
            (int) $oggettoData->format('j'),
            $mesi[(int) $oggettoData->format('n')],
            $oggettoData->format('Y')
        );

        if ($includiGiornoSettimana) {
            $testo = $giorni[(int) $oggettoData->format('N')] . ' ' . $testo;
        }

        return $testo;
    }


    private function getUtenteAutenticato(): ?EUtente
    {
        $idUtente =
            FSessione::getIdUtenteAutenticato();

        if ($idUtente === null) {
            return null;
        }

        return
            FUtente::load(
                $idUtente
            );
    }


    private function redirect(
        string $url
    ): void {
        header(
            'Location: ' . $url
        );

        exit;
    }
}
