<?php
require_once __DIR__ . '/../View/VStatisticheProprietario.class.php';

require_once __DIR__ . '/../Foundation/FSessione.class.php';
require_once __DIR__ . '/../Foundation/FUtente.class.php';
require_once __DIR__ . '/../Foundation/FPagamento.class.php';
require_once __DIR__ . '/../Foundation/FPrenotazione.class.php';
require_once __DIR__ . '/../Entity/EUtente.class.php';

class CGestioneStatistiche
{
    public function mostraStatistiche(array $request = []): void
    {
        $get = $request['get'] ?? [];

        $periodo = $get['periodo'] ?? 'settimana';

        if (!in_array($periodo, ['giorno', 'settimana', 'mese', 'anno'], true)) {
            $periodo = 'settimana';
        }

        $riferimento = is_string($get['riferimento'] ?? null)
            ? $get['riferimento']
            : null;

        $dati = $this->statisticheProprietario(
            $periodo,
            $riferimento
        );

        if ($dati === null) {
            header('Location: index.php');
            exit;
        }

        (new VStatisticheProprietario())->mostraStatistiche([
            'statistiche' => $dati
        ]);
    }


    public function statisticheCliente(): ?array
    {
        $utente = $this->getUtenteAutenticato();

        if (
            $utente === null
            ||
            !$utente->isCliente()
        ) {
            return null;
        }

        $idCliente = $utente->getId();

        if ($idCliente === null) {
            return null;
        }

        $periodi = $this->getPeriodiCorrenti();

        $completateSettimana =
            $this->prenotazioniCompletateClientePeriodo(
                $idCliente,
                $periodi['settimana']['inizio'],
                $periodi['settimana']['fine']
            );

        $completateMese =
            $this->prenotazioniCompletateClientePeriodo(
                $idCliente,
                $periodi['mese']['inizio'],
                $periodi['mese']['fine']
            );

        $completateAnno =
            $this->prenotazioniCompletateClientePeriodo(
                $idCliente,
                $periodi['anno']['inizio'],
                $periodi['anno']['fine']
            );

        return [
            'cliente' => $utente,

            'appuntamenti_completati_totali' =>
                FPrenotazione::countCompletateByCliente(
                    $idCliente
                ),

            'spese' => [
                'settimana' =>
                    $this->calcolaIncassoReale(
                        $completateSettimana
                    ),

                'mese' =>
                    $this->calcolaIncassoReale(
                        $completateMese
                    ),

                'anno' =>
                    $this->calcolaIncassoReale(
                        $completateAnno
                    )
            ]
        ];
    }


    public function statisticheProprietario(
        string $periodoAttivo = 'settimana',
        ?string $riferimento = null
    ): ?array {
        $utente = $this->getUtenteAutenticato();

        if ($utente === null || !$utente->isProprietario()) {
            return null;
        }

        if (!in_array($periodoAttivo, ['giorno', 'settimana', 'mese', 'anno'], true)) {
            $periodoAttivo = 'settimana';
        }

        $dataRiferimento = $this->creaDataRiferimento($riferimento);
        $periodi = $this->getPeriodiPerRiferimento($dataRiferimento);

        $prenotazioniGiorno = $this->prenotazioniConteggiabiliPeriodo(
            $periodi['giorno']['inizio'],
            $periodi['giorno']['fine']
        );

        $prenotazioniSettimana = $this->prenotazioniConteggiabiliPeriodo(
            $periodi['settimana']['inizio'],
            $periodi['settimana']['fine']
        );

        $prenotazioniMese = $this->prenotazioniConteggiabiliPeriodo(
            $periodi['mese']['inizio'],
            $periodi['mese']['fine']
        );

        $prenotazioniAnno = $this->prenotazioniConteggiabiliPeriodo(
            $periodi['anno']['inizio'],
            $periodi['anno']['fine']
        );

        $completateGiorno = $this->filtraCompletate($prenotazioniGiorno);
        $completateSettimana = $this->filtraCompletate($prenotazioniSettimana);
        $completateMese = $this->filtraCompletate($prenotazioniMese);
        $completateAnno = $this->filtraCompletate($prenotazioniAnno);

        $serviziGiorno = $this->calcolaServiziPiuRichiesti(
            $prenotazioniGiorno
        );

        $serviziSettimana = $this->calcolaServiziPiuRichiesti(
            $prenotazioniSettimana
        );

        $serviziMese = $this->calcolaServiziPiuRichiesti(
            $prenotazioniMese
        );

        $serviziAnno = $this->calcolaServiziPiuRichiesti(
            $prenotazioniAnno
        );

        $graficoGiorno = $this->creaGraficoGiorno(
            $prenotazioniGiorno,
            $completateGiorno,
            $dataRiferimento->format('Y-m-d')
        );

        $graficoSettimana = $this->creaGraficoSettimana(
            $prenotazioniSettimana,
            $completateSettimana,
            $periodi['settimana']['inizio']
        );

        $graficoMese = $this->creaGraficoMese(
            $prenotazioniMese,
            $completateMese,
            $periodi['mese']['inizio']
        );

        $graficoAnno = $this->creaGraficoAnno(
            $prenotazioniAnno,
            $completateAnno,
            (int) $dataRiferimento->format('Y')
        );

        return [
            'periodo_attivo' => $periodoAttivo,
            'riferimento' => $dataRiferimento->format('Y-m-d'),
            'oggi' => date('Y-m-d'),
            'proprietario' => $utente,

            'etichette_periodi' => [
                'giorno' => $this->etichettaGiorno($dataRiferimento),
                'settimana' => $this->etichettaSettimana(
                    $periodi['settimana']['inizio'],
                    $periodi['settimana']['fine']
                ),
                'mese' => $this->etichettaMese($dataRiferimento),
                'anno' => 'Anno ' . $dataRiferimento->format('Y')
            ],

            'incassi' => [
                'giorno' => $this->calcolaIncassoReale(
                    $completateGiorno
                ),
                'settimana' => $this->calcolaIncassoReale(
                    $completateSettimana
                ),
                'mese' => $this->calcolaIncassoReale(
                    $completateMese
                ),
                'anno' => $this->calcolaIncassoReale(
                    $completateAnno
                )
            ],

            'appuntamenti_totali' => [
                'giorno' => count($prenotazioniGiorno),
                'settimana' => count($prenotazioniSettimana),
                'mese' => count($prenotazioniMese),
                'anno' => count($prenotazioniAnno)
            ],

            /*
             * Manteniamo anche il dato dei completati perché può essere
             * utile ad altre View o a test esistenti, ma la card principale
             * mostra gli appuntamenti totali del periodo.
             */
            'appuntamenti_completati' => [
                'giorno' => count($completateGiorno),
                'settimana' => count($completateSettimana),
                'mese' => count($completateMese),
                'anno' => count($completateAnno)
            ],

            'servizi_piu_richiesti' => [
                'giorno' => $serviziGiorno,
                'settimana' => $serviziSettimana,
                'mese' => $serviziMese,
                'anno' => $serviziAnno
            ],

            'grafico_giorno' => $graficoGiorno,
            'grafico_settimana' => $graficoSettimana,
            'grafico_mese' => $graficoMese,
            'grafico_anno' => $graficoAnno,

            'grafici' => [
                'giorno' => $graficoGiorno,
                'settimana' => $graficoSettimana,
                'mese' => $graficoMese,
                'anno' => $graficoAnno
            ],

            'navigazione_periodo' => $this->creaNavigazionePeriodo(
                $periodoAttivo,
                $periodi[$periodoAttivo],
                $dataRiferimento
            ),

            'massimo_incasso_giornaliero' => $this->massimoGrafico(
                $graficoSettimana,
                'incasso'
            ),

            'massimo_appuntamenti_giornalieri' => $this->massimoGrafico(
                $graficoSettimana,
                'appuntamenti'
            ),

            'massimo_servizi_mese' => $this->massimoGrafico(
                $serviziMese,
                'quantita'
            ),

            'massimo_servizi' => [
                'giorno' => $this->massimoGrafico($serviziGiorno, 'quantita'),
                'settimana' => $this->massimoGrafico($serviziSettimana, 'quantita'),
                'mese' => $this->massimoGrafico($serviziMese, 'quantita'),
                'anno' => $this->massimoGrafico($serviziAnno, 'quantita')
            ]
        ];
    }


    private function creaGraficoGiorno(
        array $prenotazioni,
        array $completate,
        string $data
    ): array {
        $righe = [];

        for ($ora = 0; $ora < 24; $ora++) {
            $etichetta = sprintf('%02d:00', $ora);
            $righe[$etichetta] = [
                'data' => $data,
                'etichetta' => $etichetta,
                'appuntamenti' => 0,
                'completati' => 0,
                'incasso' => 0.0
            ];
        }

        foreach ($prenotazioni as $prenotazione) {
            $ora = substr($prenotazione->getOra(), 0, 2) . ':00';

            if (isset($righe[$ora])) {
                $righe[$ora]['appuntamenti']++;
            }
        }

        foreach ($completate as $prenotazione) {
            $ora = substr($prenotazione->getOra(), 0, 2) . ':00';
            $idPrenotazione = $prenotazione->getId();

            if ($idPrenotazione === null || !isset($righe[$ora])) {
                continue;
            }

            $righe[$ora]['completati']++;
            $righe[$ora]['incasso'] +=
                $this->importoEffettivoPrenotazione($prenotazione);
        }

        foreach ($righe as &$riga) {
            $riga['incasso'] = round($riga['incasso'], 2);
        }
        unset($riga);

        return array_values($righe);
    }


    private function creaGraficoSettimana(
        array $prenotazioni,
        array $completate,
        string $dataInizio
    ): array {
        $giorniBrevi = [
            1 => 'Lun',
            2 => 'Mar',
            3 => 'Mer',
            4 => 'Gio',
            5 => 'Ven',
            6 => 'Sab',
            7 => 'Dom'
        ];

        $righePerData = [];
        $inizio = new DateTimeImmutable($dataInizio);

        for ($indice = 0; $indice < 7; $indice++) {
            $giorno = $inizio->modify('+' . $indice . ' days');
            $data = $giorno->format('Y-m-d');

            $righePerData[$data] = [
                'data' => $data,
                'etichetta' =>
                    $giorniBrevi[(int) $giorno->format('N')]
                    . ' '
                    . $giorno->format('d/m'),
                'appuntamenti' => 0,
                'completati' => 0,
                'incasso' => 0.0
            ];
        }

        return $this->riempiGrafico(
            $righePerData,
            $prenotazioni,
            $completate
        );
    }


    private function creaGraficoMese(
        array $prenotazioni,
        array $completate,
        string $dataInizio
    ): array {
        $inizio = new DateTimeImmutable($dataInizio);
        $giorni = (int) $inizio->format('t');
        $righe = [];

        for ($indice = 0; $indice < $giorni; $indice++) {
            $giorno = $inizio->modify('+' . $indice . ' days');

            $righe[$giorno->format('Y-m-d')] = [
                'data' => $giorno->format('Y-m-d'),
                'etichetta' => $giorno->format('d/m'),
                'appuntamenti' => 0,
                'completati' => 0,
                'incasso' => 0.0
            ];
        }

        return $this->riempiGrafico(
            $righe,
            $prenotazioni,
            $completate
        );
    }


    private function creaGraficoAnno(
        array $prenotazioni,
        array $completate,
        int $anno
    ): array {
        $mesi = [
            1 => 'Gen',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'Mag',
            6 => 'Giu',
            7 => 'Lug',
            8 => 'Ago',
            9 => 'Set',
            10 => 'Ott',
            11 => 'Nov',
            12 => 'Dic'
        ];

        $righe = [];

        for ($mese = 1; $mese <= 12; $mese++) {
            $righe[sprintf('%04d-%02d', $anno, $mese)] = [
                'data' => sprintf('%04d-%02d', $anno, $mese),
                'etichetta' => $mesi[$mese],
                'appuntamenti' => 0,
                'completati' => 0,
                'incasso' => 0.0
            ];
        }

        return $this->riempiGrafico(
            $righe,
            $prenotazioni,
            $completate,
            true
        );
    }


    private function riempiGrafico(
        array $righe,
        array $prenotazioni,
        array $completate,
        bool $perMese = false
    ): array {
        foreach ($prenotazioni as $prenotazione) {
            $chiave = $perMese
                ? substr($prenotazione->getData(), 0, 7)
                : $prenotazione->getData();

            if (!isset($righe[$chiave])) {
                continue;
            }

            $righe[$chiave]['appuntamenti']++;
        }

        foreach ($completate as $prenotazione) {
            $chiave = $perMese
                ? substr($prenotazione->getData(), 0, 7)
                : $prenotazione->getData();

            if (!isset($righe[$chiave])) {
                continue;
            }

            $idPrenotazione = $prenotazione->getId();

            if ($idPrenotazione === null) {
                continue;
            }

            $righe[$chiave]['completati']++;
            $righe[$chiave]['incasso'] +=
                $this->importoEffettivoPrenotazione($prenotazione);
        }

        foreach ($righe as &$riga) {
            $riga['incasso'] = round($riga['incasso'], 2);
        }
        unset($riga);

        return array_values($righe);
    }


    private function massimoGrafico(array $righe, string $chiave): float
    {
        $massimo = 0.0;

        foreach ($righe as $riga) {
            $valore = (float) ($riga[$chiave] ?? 0);
            $massimo = max($massimo, $valore);
        }

        return max(1.0, $massimo);
    }


    private function getUtenteAutenticato(): ?EUtente
    {
        $idUtente = FSessione::getIdUtenteAutenticato();

        if ($idUtente === null) {
            return null;
        }

        return FUtente::load($idUtente);
    }


    private function prenotazioniConteggiabiliPeriodo(
        string $dataInizio,
        string $dataFine
    ): array {
        $prenotazioni = FPrenotazione::loadByPeriodo(
            $dataInizio,
            $dataFine
        );

        $conteggiabili = [];

        foreach ($prenotazioni as $prenotazione) {
            /*
             * Una prenotazione cancellata non fa più parte degli
             * appuntamenti effettivamente previsti/svolti del periodo.
             * No-show e prenotazioni ancora aperte restano invece
             * appuntamenti reali del calendario e vengono conteggiati.
             */
            if ($prenotazione->getStato() === 'cancellata') {
                continue;
            }

            $conteggiabili[] = $prenotazione;
        }

        return $conteggiabili;
    }


    private function filtraCompletate(array $prenotazioni): array
    {
        $completate = [];

        foreach ($prenotazioni as $prenotazione) {
            if ($prenotazione->getStato() === 'completata') {
                $completate[] = $prenotazione;
            }
        }

        return $completate;
    }


    private function prenotazioniCompletatePeriodo(
        string $dataInizio,
        string $dataFine
    ): array {
        return $this->filtraCompletate(
            $this->prenotazioniConteggiabiliPeriodo(
                $dataInizio,
                $dataFine
            )
        );
    }


    private function prenotazioniCompletateClientePeriodo(
        int $idCliente,
        string $dataInizio,
        string $dataFine
    ): array {
        $prenotazioni = $this->prenotazioniCompletatePeriodo(
            $dataInizio,
            $dataFine
        );

        $completateCliente = [];

        foreach ($prenotazioni as $prenotazione) {
            if (
                $prenotazione
                    ->getCliente()
                    ->getId()
                === $idCliente
            ) {
                $completateCliente[] = $prenotazione;
            }
        }

        return $completateCliente;
    }


    private function calcolaIncassoReale(array $prenotazioni): float
    {
        $totaleCentesimi = 0;

        foreach ($prenotazioni as $prenotazione) {
            $idPrenotazione = $prenotazione->getId();

            if ($idPrenotazione === null) {
                continue;
            }

            $totaleCentesimi += (int) round(
                $this->importoEffettivoPrenotazione($prenotazione) * 100
            );
        }

        return round($totaleCentesimi / 100, 2);
    }


    private function importoEffettivoPrenotazione(
        EPrenotazione $prenotazione
    ): float {
        $idPrenotazione = $prenotazione->getId();

        if ($idPrenotazione === null) {
            return 0.0;
        }

        $pagamento = FPagamento::loadByPrenotazione(
            $idPrenotazione
        );

        if ($pagamento !== null) {
            return $pagamento->getImportoPagato();
        }

        /*
         * Fallback limitato ai dati storici incoerenti.
         * I nuovi completamenti registrano sempre il pagamento
         * nella stessa transazione, quindi un coupon reale resta
         * rappresentato dall'importo salvato in FPagamento.
         */
        return $prenotazione->getStato() === 'completata'
            ? $prenotazione->getPrezzo()
            : 0.0;
    }


    private function calcolaServiziPiuRichiesti(array $prenotazioni): array
    {
        $conteggi = [];
        $nomiServizi = [];

        foreach ($prenotazioni as $prenotazione) {
            $servizio = $prenotazione->getServizio();
            $idServizio = $servizio->getId();

            if ($idServizio === null) {
                continue;
            }

            if (!isset($conteggi[$idServizio])) {
                $conteggi[$idServizio] = 0;
            }

            $conteggi[$idServizio]++;
            $nomiServizi[$idServizio] = $prenotazione->getNomeServizio();
        }

        arsort($conteggi);
        $classifica = [];
        $totaleRichieste = array_sum($conteggi);

        foreach ($conteggi as $idServizio => $quantita) {
            $classifica[] = [
                'id_servizio' => (int) $idServizio,
                'nome_servizio' => $nomiServizi[$idServizio],
                'quantita' => $quantita,
                'percentuale' => $totaleRichieste > 0
                    ? round(($quantita / $totaleRichieste) * 100)
                    : 0
            ];
        }

        return $classifica;
    }


    private function creaDataRiferimento(?string $riferimento): DateTimeImmutable
    {
        $oggi = new DateTimeImmutable('today');

        if ($riferimento === null) {
            return $oggi;
        }

        $data = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $riferimento
        );

        if (
            $data === false
            ||
            $data->format('Y-m-d') !== $riferimento
        ) {
            return $oggi;
        }

        // Anche i periodi futuri mostrano gli appuntamenti gia' prenotati;
        // gli incassi restano quelli effettivamente registrati.
        return $data;
    }


    private function getPeriodiPerRiferimento(
        DateTimeImmutable $riferimento
    ): array {
        $inizioSettimana = $riferimento->modify('monday this week');
        $fineSettimana = $inizioSettimana->modify('+6 days');

        $inizioMese = $riferimento->modify('first day of this month');
        $fineMese = $riferimento->modify('last day of this month');

        $inizioAnno = $riferimento->setDate(
            (int) $riferimento->format('Y'),
            1,
            1
        );

        $fineAnno = $riferimento->setDate(
            (int) $riferimento->format('Y'),
            12,
            31
        );

        return [
            'giorno' => [
                'inizio' => $riferimento->format('Y-m-d'),
                'fine' => $riferimento->format('Y-m-d')
            ],
            'settimana' => [
                'inizio' => $inizioSettimana->format('Y-m-d'),
                'fine' => $fineSettimana->format('Y-m-d')
            ],
            'mese' => [
                'inizio' => $inizioMese->format('Y-m-d'),
                'fine' => $fineMese->format('Y-m-d')
            ],
            'anno' => [
                'inizio' => $inizioAnno->format('Y-m-d'),
                'fine' => $fineAnno->format('Y-m-d')
            ]
        ];
    }


    private function getPeriodiCorrenti(): array
    {
        return $this->getPeriodiPerRiferimento(
            new DateTimeImmutable('today')
        );
    }


    private function creaNavigazionePeriodo(
        string $periodo,
        array $intervallo,
        DateTimeImmutable $riferimento
    ): array {
        $inizioPeriodo = new DateTimeImmutable($intervallo['inizio']);
        $oggi = new DateTimeImmutable('today');
        $periodiCorrenti = $this->getPeriodiPerRiferimento($oggi);
        $inizioPeriodoCorrente = new DateTimeImmutable(
            $periodiCorrenti[$periodo]['inizio']
        );

        switch ($periodo) {
            case 'giorno':
                $precedente = $inizioPeriodo->modify('-1 day');
                $successivo = $inizioPeriodo->modify('+1 day');
                break;

            case 'mese':
                $precedente = $inizioPeriodo->modify('-1 month');
                $successivo = $inizioPeriodo->modify('+1 month');
                break;

            case 'anno':
                $precedente = $inizioPeriodo->modify('-1 year');
                $successivo = $inizioPeriodo->modify('+1 year');
                break;

            case 'settimana':
            default:
                $precedente = $inizioPeriodo->modify('-7 days');
                $successivo = $inizioPeriodo->modify('+7 days');
                break;
        }

        return [
            'precedente' => $precedente->format('Y-m-d'),
            'successivo' => $successivo->format('Y-m-d'),
            'corrente' => $oggi->format('Y-m-d'),
            'e_corrente' => $inizioPeriodo == $inizioPeriodoCorrente,
            'riferimento_selezionato' => $riferimento->format('Y-m-d')
        ];
    }


    private function etichettaSettimana(
        string $dataInizio,
        string $dataFine
    ): string {
        $inizio = new DateTimeImmutable($dataInizio);
        $fine = new DateTimeImmutable($dataFine);
        $meseInizio = $this->nomeMese((int) $inizio->format('n'));
        $meseFine = $this->nomeMese((int) $fine->format('n'));

        if ($inizio->format('Y-m') === $fine->format('Y-m')) {
            return (int) $inizio->format('j')
                . ' – '
                . (int) $fine->format('j')
                . ' '
                . $meseFine
                . ' '
                . $fine->format('Y');
        }

        if ($inizio->format('Y') === $fine->format('Y')) {
            return (int) $inizio->format('j')
                . ' '
                . $meseInizio
                . ' – '
                . (int) $fine->format('j')
                . ' '
                . $meseFine
                . ' '
                . $fine->format('Y');
        }

        return (int) $inizio->format('j')
            . ' '
            . $meseInizio
            . ' '
            . $inizio->format('Y')
            . ' – '
            . (int) $fine->format('j')
            . ' '
            . $meseFine
            . ' '
            . $fine->format('Y');
    }


    private function etichettaGiorno(DateTimeImmutable $data): string
    {
        return (int) $data->format('j')
            . ' '
            . $this->nomeMese((int) $data->format('n'))
            . ' '
            . $data->format('Y');
    }


    private function etichettaMese(DateTimeImmutable $data): string
    {
        return ucfirst($this->nomeMese((int) $data->format('n')))
            . ' '
            . $data->format('Y');
    }


    private function nomeMese(int $mese): string
    {
        $mesi = [
            1 => 'gennaio',
            2 => 'febbraio',
            3 => 'marzo',
            4 => 'aprile',
            5 => 'maggio',
            6 => 'giugno',
            7 => 'luglio',
            8 => 'agosto',
            9 => 'settembre',
            10 => 'ottobre',
            11 => 'novembre',
            12 => 'dicembre'
        ];

        return $mesi[$mese] ?? '';
    }
}
