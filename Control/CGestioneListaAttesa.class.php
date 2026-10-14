<?php

require_once __DIR__ . '/../Foundation/FSessione.class.php';
require_once __DIR__ . '/../Foundation/FConnectionDB.class.php';
require_once __DIR__ . '/../Foundation/FUtente.class.php';
require_once __DIR__ . '/../Foundation/FServizio.class.php';
require_once __DIR__ . '/../Foundation/FDisponibilita.class.php';
require_once __DIR__ . '/../Foundation/FPrenotazione.class.php';
require_once __DIR__ . '/../Foundation/FListaAttesa.class.php';

require_once __DIR__ . '/../Entity/EListaAttesa.class.php';
require_once __DIR__ . '/../Entity/EUtente.class.php';
require_once __DIR__ . '/../Entity/EPrenotazione.class.php';
require_once __DIR__ . '/../Entity/EDisponibilita.class.php';

require_once __DIR__ . '/../View/VListaAttesaCliente.class.php';
require_once __DIR__ . '/../View/VNuovaListaAttesaCliente.class.php';

class CGestioneListaAttesa
{
    private bool $richiestaSovrapposta = false;
    public function mostraListaAttesaCliente(array $request = []): void
    {
        $cliente = $this->getClienteAutenticato();

        if ($cliente === null || $cliente->getId() === null) {
            $this->redirect('index.php');
        }

        $righe = [];

        foreach (FListaAttesa::loadByCliente($cliente->getId()) as $richiesta) {
            $this->aggiornaScadenzaSeNecessaria($richiesta);
            $alternativa = $richiesta->getPrenotazioneAlternativa();

            $righe[] = [
                'id' => $richiesta->getId(),
                'servizio' => $richiesta->getServizio()->getNome(),
                'data' => $this->formattaData($richiesta->getDataDesiderata()),
                'ora' => substr($richiesta->getOraDesiderata(), 0, 5),
                'inserita_il' => $this->formattaDataOra(
                    $richiesta->getDataOraInserimento()
                ),
                'stato' => $richiesta->getStato(),
                'stato_etichetta' => $this->etichettaStato(
                    $richiesta->getStato()
                ),
                'stato_descrizione' => $this->descrizioneStato(
                    $richiesta->getStato()
                ),
                'attiva' => $richiesta->isAttiva(),
                'alternativa' => $alternativa === null || $alternativa->getStato() !== 'prenotata'
                    || strtotime($alternativa->getData() . ' ' . $alternativa->getOra()
                        . ' +' . $alternativa->getDurata() . ' minutes') <= time() ? null : [
                    'data' => $this->formattaData(
                        $alternativa->getData()
                    ),
                    'ora' => substr(
                        $alternativa->getOra(),
                        0,
                        5
                    )
                ]
            ];
        }

        $get = $request['get'] ?? [];
        $prenotazioniAttive = $this->prenotazioniAttiveCliente($cliente->getId());
        $attuale = $prenotazioniAttive[0] ?? null;
        $puoCercare = $cliente->isPrenotazioniOnlineAbilitate()
            && count($prenotazioniAttive) <= 1
            && $this->richiestaAttivaPerCliente($cliente->getId()) === null;

        (new VListaAttesaCliente())->mostraListaAttesa([
            'richieste' => $righe,
            'puo_cercare' => $puoCercare,
            'prenotazioni_abilitate' => $cliente->isPrenotazioniOnlineAbilitate(),
            'url_ricerca' => $attuale === null ? 'index.php?pagina=prenota&nuova=1'
                : 'index.php?pagina=lista_attesa&azione=nuova&servizio=' . $attuale->getServizio()->getId(),
            'messaggio' => $this->getMessaggio(
                $get['esito'] ?? ''
            ),
            'errore' => $this->getErrore(
                $get['errore'] ?? ''
            )
        ]);
    }

    public function mostraNuovaRichiestaCliente(array $request = []): void
    {
        $cliente = $this->getClienteAutenticato();

        if ($cliente === null || $cliente->getId() === null) {
            $this->redirect('index.php?pagina=login');
        }

        $get = $request['get'] ?? [];
        $prenotazioniAttive = $this->prenotazioniAttiveCliente($cliente->getId());
        $appuntamentoAttuale = $prenotazioniAttive[0] ?? null;
        $idServizio = $appuntamentoAttuale === null
            ? (int) ($get['servizio'] ?? 0)
            : (int) $appuntamentoAttuale->getServizio()->getId();
        $servizio = FServizio::load($idServizio);

        if ($servizio === null || !$servizio->isAttivo()) {
            $this->redirect('index.php?pagina=prenota');
        }

        $richiestaAttiva = $this->richiestaAttivaPerCliente($cliente->getId());
        $puoAttendere = $cliente->isPrenotazioniOnlineAbilitate()
            && count($prenotazioniAttive) <= 1
            && $richiestaAttiva === null;
        $slotAttesa = $puoAttendere
            ? $this->slotOccupatiRichiedibili($cliente->getId(), $idServizio)
            : [];
        $dettaglioAppuntamento = null;

        if ($appuntamentoAttuale !== null) {
            $oraInizio = substr($appuntamentoAttuale->getOra(), 0, 5);
            $ora = DateTimeImmutable::createFromFormat('!H:i', $oraInizio);
            $dettaglioAppuntamento = [
                'servizio' => $appuntamentoAttuale->getNomeServizio(),
                'data' => $this->formattaData($appuntamentoAttuale->getData()),
                'ora_inizio' => $oraInizio,
                'ora_fine' => $ora === false
                    ? ''
                    : $ora->modify('+' . $appuntamentoAttuale->getDurata() . ' minutes')->format('H:i')
            ];
        }

        (new VNuovaListaAttesaCliente())->mostraNuovaRichiesta([
            'utente' => $cliente,
            'servizio' => $servizio,
            'appuntamento_attuale' => $dettaglioAppuntamento,
            'slot_attesa' => $slotAttesa,
            'puo_attendere' => $puoAttendere,
            'richiesta_attiva' => $richiestaAttiva,
            'errore' => $this->getErrore($get['errore'] ?? '')
        ]);
    }

    public function slotOccupatiRichiedibili(
        int $idCliente,
        int $idServizio
    ): array {
        if (!$this->isClienteAutenticato($idCliente)) {
            return [];
        }

        $servizio = FServizio::load($idServizio);

        if ($servizio === null || !$servizio->isAttivo()) {
            return [];
        }

        $prenotazioniCliente =
            $this->prenotazioniAttiveCliente(
                $idCliente
            );

        if (count($prenotazioniCliente) > 1) {
            return [];
        }

        $alternativa =
            $prenotazioniCliente[0]
            ?? null;

        if (
            $alternativa !== null
            &&
            $alternativa
            ->getServizio()
            ->getId()
            !== $idServizio
        ) {
            return [];
        }

        $righe = [];
        $dateVisitate = [];

        foreach (
            FDisponibilita::loadFromDate(
                date('Y-m-d')
            )
            as $fascia
        ) {
            $data = $fascia->getData();

            if (isset($dateVisitate[$data])) {
                continue;
            }

            $dateVisitate[$data] = true;

            foreach (
                $this->iniziSlotRichiedibiliPerData(
                    $data,
                    $servizio->getDurata()
                )
                as $inizioMinuti
            ) {
                $ora =
                    $this->minutiInOra(
                        $inizioMinuti
                    );

                $oraNormalizzata =
                    $this->normalizzaOra(
                        $ora
                    );

                if (
                    !$this->mancanoPiuDi48Ore(
                        $data,
                        $oraNormalizzata
                    )
                ) {
                    continue;
                }

                if (
                    !FPrenotazione
                        ::esisteSovrapposizione(
                            $data,
                            $oraNormalizzata,
                            $servizio
                                ->getDurata()
                        )
                ) {
                    continue;
                }

                if ($alternativa !== null && $alternativa->siSovrapponeA(
                    $data, $oraNormalizzata, $servizio->getDurata()
                )) {
                    continue;
                }

                $inizio =
                    DateTimeImmutable
                    ::createFromFormat(
                        '!H:i',
                        $ora
                    );

                if ($inizio === false) {
                    continue;
                }

                $chiave =
                    $data
                    . '|'
                    . $ora;

                $righe[$chiave] = [
                    'valore' =>
                    $chiave,

                    'data' =>
                    $this->formattaData(
                        $data
                    ),

                    'ora_inizio' =>
                    $ora,

                    'ora_fine' =>
                    $inizio
                        ->modify(
                            '+'
                                . $servizio
                                ->getDurata()
                                . ' minutes'
                        )
                        ->format('H:i')
                ];
            }
        }

        ksort($righe);

        return array_values($righe);
    }

    public function gestisciInserimentoListaAttesa(
        array $request
    ): void {
        $cliente =
            $this->getClienteAutenticato();

        if (
            $cliente === null
            ||
            $cliente->getId() === null
        ) {
            $this->redirect(
                'index.php'
            );
        }

        $post =
            $request['post']
            ?? [];

        $idServizio =
            (int) (
                $post['id_servizio']
                ?? 0
            );

        $dataDesiderata =
            $post['data_desiderata']
            ?? '';

        $oraDesiderata =
            $post['ora_desiderata']
            ?? '';

        $slotAttesa =
            $post['slot_attesa']
            ?? '';

        if (
            preg_match(
                '/^(\d{4}-\d{2}-\d{2})\|((?:[01]\d|2[0-3]):[0-5]\d)$/D',
                $slotAttesa,
                $parti
            )
            === 1
        ) {
            $dataDesiderata =
                $parti[1];

            $oraDesiderata =
                $parti[2];
        }

        $richiesta =
            $this->inserisciInListaAttesa(
                $cliente->getId(),
                $idServizio,
                $dataDesiderata,
                $oraDesiderata
            );

        if ($richiesta !== null) {
            $this->redirect(
                'index.php?pagina=lista_attesa&esito=attesa'
            );
        }

        $servizio =
            FServizio::load(
                $idServizio
            );

        $url = 'index.php?pagina=lista_attesa&azione=nuova&errore='
            . ($this->richiestaSovrapposta ? 'sovrapposizione' : 'attesa');

        if ($servizio !== null && $servizio->getId() !== null) {
            $url .= '&servizio=' . $servizio->getId();
        }

        $this->redirect($url);
    }

    public function gestisciAnnullamentoListaAttesa(
        array $request
    ): void {
        $cliente =
            $this->getClienteAutenticato();

        if (
            $cliente === null
            ||
            $cliente->getId() === null
        ) {
            $this->redirect(
                'index.php'
            );
        }

        $post =
            $request['post']
            ?? [];

        $annullata =
            $this->annullaRichiesta(
                $cliente->getId(),
                (int) (
                    $post['id_richiesta']
                    ?? 0
                )
            );

        if ($annullata) {
            $this->redirect(
                'index.php?pagina=lista_attesa&esito=attesa_annullata'
            );
        }

        $this->redirect(
            'index.php?pagina=lista_attesa&errore=attesa'
        );
    }

    public function richiestaAttivaPerCliente(
        int $idCliente
    ): ?EListaAttesa {
        if (
            !$this->isClienteAutenticato(
                $idCliente
            )
        ) {
            return null;
        }

        $cliente =
            FUtente::load(
                $idCliente
            );

        if (
            $cliente === null
            ||
            !$cliente->isCliente()
        ) {
            return null;
        }

        $richiesta =
            FListaAttesa::loadAttivaByCliente(
                $idCliente
            );

        if ($richiesta === null) {
            return null;
        }

        if (
            !$this->aggiornaScadenzaSeNecessaria(
                $richiesta
            )
        ) {
            return null;
        }

        return
            $richiesta->isAttiva()
            ? $richiesta
            : null;
    }

    public function statoRichiestaPerCliente(
        int $idCliente,
        int $idRichiesta
    ): ?string {
        if (
            !$this->isClienteAutenticato(
                $idCliente
            )
        ) {
            return null;
        }

        $richiesta =
            FListaAttesa::load(
                $idRichiesta
            );

        if (
            $richiesta === null
            ||
            $richiesta
            ->getCliente()
            ->getId()
            !== $idCliente
        ) {
            return null;
        }

        if (
            !$this->aggiornaScadenzaSeNecessaria(
                $richiesta
            )
        ) {
            return null;
        }

        return
            $richiesta->getStato();
    }

    public function inserisciInListaAttesa(
        int $idCliente,
        int $idServizio,
        string $dataDesiderata,
        string $oraDesiderata
    ): ?EListaAttesa {
        $this->richiestaSovrapposta = false;
        if (
            !$this->isClienteAutenticato(
                $idCliente
            )
            ||
            !preg_match(
                '/^(?:[01]\d|2[0-3]):[0-5]\d(?::00)?$/D',
                $oraDesiderata
            )
        ) {
            return null;
        }

        $giorno =
            DateTimeImmutable
            ::createFromFormat(
                '!Y-m-d',
                $dataDesiderata
            );

        if (
            $giorno === false
            ||
            $giorno->format('Y-m-d')
            !== $dataDesiderata
        ) {
            return null;
        }

        $connection =
            FConnectionDB::connect();

        $connection->beginTransaction();

        try {
            /*
             * Anche la lista d'attesa
             * lavora sulla stessa
             * fotografia bloccata
             * del calendario.
             */
            FConnectionDB::bloccaCalendario();

            $cliente =
                FUtente::load(
                    $idCliente
                );

            $servizio =
                FServizio::load(
                    $idServizio
                );

            $ora =
                $this->normalizzaOra(
                    $oraDesiderata
                );

            if (
                $cliente === null
                ||
                !$cliente->isCliente()
                ||
                !$cliente
                    ->isPrenotazioniOnlineAbilitate()
                ||
                $servizio === null
                ||
                !$servizio->isAttivo()
                ||
                $this->richiestaAttivaPerCliente(
                    $idCliente
                ) !== null
                ||
                !$this->mancanoPiuDi48Ore(
                    $dataDesiderata,
                    $ora
                )
                ||
                !$this->intervalloDentroDisponibilita(
                    $dataDesiderata,
                    $ora,
                    $servizio->getDurata()
                )
            ) {
                $connection->rollBack();

                return null;
            }

            $attive =
                $this->prenotazioniAttiveCliente(
                    $idCliente
                );

            if (count($attive) > 1) {
                $connection->rollBack();

                return null;
            }

            $alternativa =
                $attive[0]
                ?? null;

            if ($alternativa !== null && $alternativa->siSovrapponeA(
                $dataDesiderata, $ora, $servizio->getDurata()
            )) {
                $this->richiestaSovrapposta = true;
                $connection->rollBack();
                return null;
            }

            /*
             * Se esiste già una prenotazione,
             * la richiesta in lista d'attesa
             * deve riguardare lo stesso servizio
             * ma un orario diverso.
             */
            if (
                $alternativa !== null
                &&
                (
                    $alternativa
                    ->getServizio()
                    ->getId()
                    !== $idServizio
                    ||
                    (
                        $alternativa->getData()
                        === $dataDesiderata
                        &&
                        $alternativa->getOra()
                        === $ora
                    )
                )
            ) {
                $connection->rollBack();

                return null;
            }

            /*
             * Non basta che l'ora abbia
             * un formato valido.
             *
             * Deve essere un vero slot
             * generato dal sistema:
             *
             * - griglia iniziale ogni 30 minuti;
             * - riallineamenti dinamici
             *   derivati dagli appuntamenti;
             * - durata reale del servizio;
             * - slot attualmente occupato.
             */
            if (
                !$this->slotOccupatoRichiedibile(
                    $dataDesiderata,
                    $ora,
                    $servizio->getDurata(),
                    $alternativa
                )
            ) {
                $connection->rollBack();

                return null;
            }

            $richiesta =
                new EListaAttesa(
                    $cliente,
                    $servizio,
                    $dataDesiderata,
                    $ora,
                    date('Y-m-d H:i:s'),
                    $alternativa
                );

            if (
                !FListaAttesa::store(
                    $richiesta
                )
            ) {
                throw new RuntimeException(
                    'Richiesta non salvata.'
                );
            }

            $connection->commit();

            return $richiesta;
        } catch (Throwable $e) {
            if (
                $connection->inTransaction()
            ) {
                $connection->rollBack();
            }

            error_log(
                'Lumèa, lista di attesa: '
                    . $e->getMessage()
            );

            return null;
        }
    }

    public function annullaRichiesta(
        int $idCliente,
        int $idRichiesta
    ): bool {
        if (
            !$this->isClienteAutenticato(
                $idCliente
            )
        ) {
            return false;
        }

        $richiesta =
            FListaAttesa::load(
                $idRichiesta
            );

        if (
            $richiesta === null
            ||
            $richiesta
            ->getCliente()
            ->getId()
            !== $idCliente
        ) {
            return false;
        }

        if (
            !$this->aggiornaScadenzaSeNecessaria(
                $richiesta
            )
        ) {
            return false;
        }

        if (!$richiesta->isAttiva()) {
            return false;
        }

        $richiesta->annulla();

        return
            FListaAttesa::update(
                $richiesta
            );
    }

    private function formattaData(
        string $data
    ): string {
        $valore =
            DateTimeImmutable
            ::createFromFormat(
                '!Y-m-d',
                $data
            );

        return
            $valore === false
            ? $data
            : $valore->format(
                'd/m/Y'
            );
    }

    private function formattaDataOra(
        string $dataOra
    ): string {
        $valore =
            DateTimeImmutable
            ::createFromFormat(
                'Y-m-d H:i:s',
                $dataOra
            );

        return
            $valore === false
            ? $dataOra
            : $valore->format(
                'd/m/Y H:i'
            );
    }

    private function etichettaStato(
        string $stato
    ): string {
        $etichette = [
            'attiva' =>
            'In attesa',

            'soddisfatta' =>
            'Orario assegnato',

            'annullata' =>
            'Annullata',

            'scaduta' =>
            'Scaduta'
        ];

        return
            $etichette[$stato]
            ?? $stato;
    }

    private function descrizioneStato(
        string $stato
    ): string {
        $descrizioni = [
            'attiva' =>
            'La richiesta è attiva: se l’orario si libera, arriva il tuo turno e la richiesta è ancora valida, potrà essere assegnato automaticamente.',

            'soddisfatta' =>
            'L’orario richiesto è stato assegnato ed è diventato una prenotazione.',

            'annullata' =>
            'Hai annullato questa richiesta.',

            'scaduta' =>
            'La richiesta non è più valida perché mancano meno di 48 ore.'
        ];

        return
            $descrizioni[$stato]
            ?? '';
    }

    private function getMessaggio(
        string $esito
    ): ?string {
        if ($esito === 'attesa') {
            return
                'Richiesta inserita correttamente nella lista d’attesa.';
        }

        if (
            $esito
            === 'attesa_annullata'
        ) {
            return
                'Richiesta in lista d’attesa annullata.';
        }

        return null;
    }

    private function getErrore(
        string $errore
    ): ?string {
        if ($errore === 'sovrapposizione') {
            return 'Hai già un appuntamento confermato in questa fascia oraria. Scegli un orario che non si sovrapponga.';
        }
        return
            $errore === 'attesa'
            ?
            'Non è stato possibile aggiornare la richiesta in lista d’attesa.'
            :
            null;
    }

    private function getClienteAutenticato(): ?EUtente
    {
        $idUtente =
            FSessione
            ::getIdUtenteAutenticato();

        if ($idUtente === null) {
            return null;
        }

        $utente =
            FUtente::load(
                $idUtente
            );

        if (
            $utente === null
            ||
            !$utente->isCliente()
        ) {
            return null;
        }

        return $utente;
    }

    private function isClienteAutenticato(
        int $idCliente
    ): bool {
        $idUtente =
            FSessione
            ::getIdUtenteAutenticato();

        if (
            $idUtente === null
            ||
            $idUtente !== $idCliente
        ) {
            return false;
        }

        $utente =
            FUtente::load(
                $idUtente
            );

        return
            $utente !== null
            &&
            $utente->isCliente();
    }

    private function prenotazioniAttiveCliente(
        int $idCliente
    ): array {
        $prenotazioniAttive = [];

        foreach (
            FPrenotazione::loadByCliente(
                $idCliente
            )
            as $prenotazione
        ) {
            if (
                $prenotazione->getStato()
                === 'prenotata'
            ) {
                $prenotazioniAttive[] =
                    $prenotazione;
            }
        }

        return $prenotazioniAttive;
    }

    private function aggiornaScadenzaSeNecessaria(
        EListaAttesa $richiesta
    ): bool {
        if (!$richiesta->isAttiva()) {
            return true;
        }

        if (
            $this->mancanoPiuDi48Ore(
                $richiesta
                    ->getDataDesiderata(),
                $richiesta
                    ->getOraDesiderata()
            )
        ) {
            return true;
        }

        $richiesta->scadi();

        return
            FListaAttesa::update(
                $richiesta
            );
    }

    private function mancanoPiuDi48Ore(
        string $data,
        string $ora
    ): bool {
        $dataOra =
            strtotime(
                $data
                    . ' '
                    . $this->normalizzaOra(
                        $ora
                    )
            );

        if ($dataOra === false) {
            return false;
        }

        return (
                $dataOra
                -
                time()
            )
            >
            (
                48
                * 60
                * 60
            );
    }

    private function intervalloDentroDisponibilita(
        string $data,
        string $oraInizio,
        int $durata
    ): bool {
        $inizioRichiesto =
            $this->oraInMinuti(
                $oraInizio
            );

        $fineRichiesta =
            $inizioRichiesto
            +
            $durata;

        foreach (
            FDisponibilita::loadByData(
                $data
            )
            as $fascia
        ) {
            $inizioFascia =
                $this->oraInMinuti(
                    $fascia
                        ->getOraInizio()
                );

            $fineFascia =
                $this->oraInMinuti(
                    $fascia
                        ->getOraFine()
                );

            if (
                $inizioRichiesto
                >= $inizioFascia
                &&
                $fineRichiesta
                <= $fineFascia
            ) {
                return true;
            }
        }

        return false;
    }

    private function slotOccupatoRichiedibile(
        string $data,
        string $ora,
        int $durata,
        ?EPrenotazione $alternativa
    ): bool {
        $inizioRichiesto =
            $this->oraInMinuti(
                $ora
            );

        $inizi =
            $this->iniziSlotRichiedibiliPerData(
                $data,
                $durata
            );

        if (
            !in_array(
                $inizioRichiesto,
                $inizi,
                true
            )
        ) {
            return false;
        }

        if (
            !FPrenotazione
                ::esisteSovrapposizione(
                    $data,
                    $this->normalizzaOra(
                        $ora
                    ),
                    $durata
                )
        ) {
            return false;
        }

        if ($alternativa !== null && $alternativa->siSovrapponeA($data, $ora, $durata)) {
            return false;
        }

        return true;
    }

    private function iniziSlotRichiedibiliPerData(
        string $data,
        int $durata
    ): array {
        $inizi = [];
        $prenotazioni = FPrenotazione::loadByData($data);

        foreach (FDisponibilita::loadByData($data) as $fascia) {
            $inizioFascia = $this->oraInMinuti($fascia->getOraInizio());
            $fineFascia = $this->oraInMinuti($fascia->getOraFine());

            // La griglia è la stessa usata per una prenotazione ordinaria.
            foreach (EDisponibilita::calcolaIniziSlot(
                $inizioFascia, $fineFascia, $inizioFascia, $fineFascia, $durata
            ) as $inizio) {
                $inizi[$inizio] = $inizio;
            }

            foreach ($prenotazioni as $prenotazione) {
                if ($prenotazione->getStato() !== 'prenotata') {
                    continue;
                }
                $inizioPrenotazione = $this->oraInMinuti($prenotazione->getOra());
                $finePrenotazione = $inizioPrenotazione + $prenotazione->getDurata();
                if ($finePrenotazione <= $inizioFascia || $inizioPrenotazione >= $fineFascia) {
                    continue;
                }

                // Anche i bordi reali sono candidati per la lista d'attesa.
                foreach (EDisponibilita::calcolaIniziSlot(
                    $inizioFascia, $fineFascia,
                    max($inizioFascia, $finePrenotazione), $fineFascia, $durata
                ) as $inizio) {
                    $inizi[$inizio] = $inizio;
                }
                foreach (EDisponibilita::calcolaIniziSlot(
                    $inizioFascia, $fineFascia,
                    $inizioFascia, min($fineFascia, $inizioPrenotazione), $durata
                ) as $inizio) {
                    $inizi[$inizio] = $inizio;
                }
            }
        }

        ksort($inizi, SORT_NUMERIC);
        return array_values($inizi);
    }

    private function normalizzaOra(
        string $ora
    ): string {
        $parti =
            explode(
                ':',
                $ora
            );

        $ore =
            (int) (
                $parti[0]
                ?? 0
            );

        $minuti =
            (int) (
                $parti[1]
                ?? 0
            );

        return
            sprintf(
                '%02d:%02d:00',
                $ore,
                $minuti
            );
    }

    private function oraInMinuti(
        string $ora
    ): int {
        $parti =
            explode(
                ':',
                $ora
            );

        $ore =
            (int) (
                $parti[0]
                ?? 0
            );

        $minuti =
            (int) (
                $parti[1]
                ?? 0
            );

        return ($ore * 60)
            +
            $minuti;
    }

    private function minutiInOra(
        int $minutiTotali
    ): string {
        $ore =
            intdiv(
                $minutiTotali,
                60
            );

        $minuti =
            $minutiTotali
            % 60;

        return
            sprintf(
                '%02d:%02d',
                $ore,
                $minuti
            );
    }

    private function redirect(
        string $url
    ): void {
        header(
            'Location: '
                . $url
        );

        exit;
    }
}
