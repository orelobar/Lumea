<?php

require_once __DIR__ . '/CGestioneListaAttesa.class.php';

require_once __DIR__ . '/../Foundation/FSessione.class.php';
require_once __DIR__ . '/../Foundation/FConnectionDB.class.php';
require_once __DIR__ . '/../Foundation/FUtente.class.php';
require_once __DIR__ . '/../Foundation/FServizio.class.php';
require_once __DIR__ . '/../Foundation/FDisponibilita.class.php';
require_once __DIR__ . '/../Foundation/FPrenotazione.class.php';
require_once __DIR__ . '/../Foundation/FEmail.class.php';

require_once __DIR__ . '/../Entity/EUtente.class.php';
require_once __DIR__ . '/../Entity/EServizio.class.php';
require_once __DIR__ . '/../Entity/EPrenotazione.class.php';
require_once __DIR__ . '/../Entity/EDisponibilita.class.php';

require_once __DIR__ . '/CGestioneAppuntamenti.class.php';

require_once __DIR__ . '/../View/VPrenotazioneCliente.class.php';


class CGestionePrenotazione
{
    public function mostraPrenotazioneCliente(
        array $request = []
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

        $get =
            $request['get']
            ?? [];

        $nuovaPrenotazione =
            ($get['nuova'] ?? '') === '1';

        $categoria =
            $this->getCategoria(
                $get
            );

        $servizi =
            [];

        if ($categoria !== null) {
            $servizi =
                $this->serviziPerCategoria(
                    $categoria
                );
        }

        $servizioSelezionato =
            $this->getServizioSelezionato(
                $get,
                $servizi
            );

        $disponibilita =
            [];

        if (
            $servizioSelezionato !== null
            &&
            $servizioSelezionato->getId()
            !== null
        ) {
            $disponibilita =
                $this->disponibilitaPerServizio(
                    $servizioSelezionato->getId(),
                    date('Y-m-d')
                );
        }

        $dataSelezionata =
            $this->getDataSelezionata(
                $get,
                $disponibilita
            );

        $orariData =
            [];

        if ($dataSelezionata !== null) {
            $orariData =
                $disponibilita[$dataSelezionata] ?? [];
        }

        $oraSelezionata =
            $this->getOraSelezionata(
                $get,
                $orariData
            );

        $gestioneAppuntamenti =
            new CGestioneAppuntamenti();

        $appuntamentoAttivo =
            $gestioneAppuntamenti
                ->appuntamentoAttivoPerCliente(
                    $cliente->getId()
                );

        $storicoPrenotazioni =
            $this->creaRigheStorico(
                array_slice(
                    $gestioneAppuntamenti
                        ->storicoPrenotazioniCliente(
                            $cliente->getId()
                        ),
                    0,
                    5
                )
            );

        $errore =
            $request['errore_locale']
            ?? $this->getErrorePrenotazione(
                $get['errore']
                    ?? ''
            );

        $puoPrenotare =
            $cliente
                ->isPrenotazioniOnlineAbilitate()
            &&
            $appuntamentoAttivo === null;

        $gestioneListaAttesa =
            new CGestioneListaAttesa();

        $richiestaAttiva =
            $gestioneListaAttesa
                ->richiestaAttivaPerCliente(
                    $cliente->getId()
                );

        $puoAttendere =
            $cliente
                ->isPrenotazioniOnlineAbilitate()
            &&
            $richiestaAttiva === null
            &&
            $servizioSelezionato !== null
            &&
            (
                $appuntamentoAttivo === null
                ||
                $appuntamentoAttivo
                    ->getServizio()
                    ->getId()
                ===
                $servizioSelezionato
                    ->getId()
            );

        $slotAttesa =
            [];

        if (
            $puoAttendere
            &&
            $servizioSelezionato
                ->getId()
            !== null
        ) {
            $slotAttesa =
                $gestioneListaAttesa
                    ->slotOccupatiRichiedibili(
                        $cliente->getId(),
                        $servizioSelezionato
                            ->getId()
                    );
        }

        $view =
            new VPrenotazioneCliente();

        $view->mostraPrenotazione(
            [
                'puo_prenotare' =>
                $puoPrenotare,

                'puo_attendere' =>
                $puoAttendere,

                'richiesta_attiva' =>
                $richiestaAttiva,

                'slot_attesa' =>
                $slotAttesa,

                'utente' =>
                $cliente,

                'step' =>
                $this->getStepPrenotazione(
                    $nuovaPrenotazione,
                    $categoria,
                    $servizioSelezionato,
                    $dataSelezionata,
                    $oraSelezionata
                ),

                'categoria' =>
                $categoria,

                'servizi' =>
                $servizi,

                'servizio_selezionato' =>
                $servizioSelezionato,

                'disponibilita' =>
                $disponibilita,

                'mese_calendario' => $get['mese_calendario'] ?? null,

                'data_selezionata' =>
                $dataSelezionata,

                'orari_data' =>
                $orariData,

                'ora_selezionata' =>
                $oraSelezionata,

                'errore' =>
                $errore,

                'appuntamento_attivo' =>
                $appuntamentoAttivo,

                'storico_prenotazioni' =>
                $storicoPrenotazioni,

                'data_minima_attesa' =>
                date(
                    'Y-m-d',
                    strtotime('+2 days')
                )
            ]
        );
    }


    public function gestisciConfermaPrenotazione(
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

        $prenotazione =
            $this->confermaPrenotazione(
                $cliente->getId(),
                (int) (
                    $post['id_servizio']
                    ?? 0
                ),
                $post['data']
                    ?? '',
                $post['ora']
                    ?? ''
            );

        if ($prenotazione !== null) {
            $this->redirect(
                'index.php?pagina=prenotazioni&esito=prenotata'
            );
        }

        /*
         * In caso di errore manteniamo
         * le scelte effettuate nel wizard.
         */
        $get =
            $request['get']
            ?? [];

        if (
            !isset(
                $get['categoria']
            )
            &&
            isset(
                $post['categoria']
            )
        ) {
            $get['categoria'] =
                $post['categoria'];
        }

        if (
            !isset(
                $get['servizio']
            )
            &&
            isset(
                $post['id_servizio']
            )
        ) {
            $get['servizio'] =
                $post['id_servizio'];
        }

        if (
            !isset(
                $get['data']
            )
            &&
            isset(
                $post['data']
            )
        ) {
            $get['data'] =
                $post['data'];
        }

        if (
            !isset(
                $get['ora']
            )
            &&
            isset(
                $post['ora']
            )
        ) {
            $get['ora'] =
                $post['ora'];
        }

        $request['get'] =
            $get;

        $request['errore_locale'] =
            'Non è stato possibile confermare la prenotazione.';

        $this->mostraPrenotazioneCliente(
            $request
        );
    }


    public function serviziPerCategoria(
        string $categoria
    ): array {
        return
            FServizio::loadAttiviByCategoria(
                $categoria
            );
    }


    public function disponibilitaPerServizio(
        int $idServizio,
        string $dataInizio
    ): array {
        $servizio =
            FServizio::load(
                $idServizio
            );

        if ($servizio === null) {
            return [];
        }

        if (!$servizio->isAttivo()) {
            return [];
        }

        $durata =
            $servizio->getDurata();

        $fasceDisponibilita =
            FDisponibilita::loadFromDate(
                $dataInizio
            );

        $risultato =
            [];

        foreach (
            $fasceDisponibilita as $fascia
        ) {
            $data =
                $fascia->getData();

            $inizioFascia =
                $this->oraInMinuti(
                    $fascia->getOraInizio()
                );

            $fineFascia =
                $this->oraInMinuti(
                    $fascia->getOraFine()
                );

            $prenotazioni =
                FPrenotazione::loadByData(
                    $data
                );

            $blocchiLiberi =
                $this->calcolaBlocchiLiberi(
                    $inizioFascia,
                    $fineFascia,
                    $prenotazioni
                );

            foreach (
                $blocchiLiberi as $blocco
            ) {
                $iniziSlot =
                    $this->calcolaIniziSlot(
                        $inizioFascia,
                        $fineFascia,
                        $blocco['inizio'],
                        $blocco['fine'],
                        $durata
                    );

                foreach (
                    $iniziSlot as $inizio
                ) {
                    $oraInizio =
                        $this->minutiInOra(
                            $inizio
                        );

                    /*
                     * Non mostriamo mai
                     * uno slot che è già
                     * trascorso.
                     */
                    if (
                        !$this->dataOraFutura(
                            $data,
                            $oraInizio
                        )
                    ) {
                        continue;
                    }

                    $oraFine =
                        $this->minutiInOra(
                            $inizio
                                +
                                $durata
                        );

                    if (
                        !isset(
                            $risultato[$data]
                        )
                    ) {
                        $risultato[$data] =
                            [];
                    }

                    $risultato[$data][] = [
                        'oraInizio' =>
                        $oraInizio,

                        'oraFine' =>
                        $oraFine
                    ];
                }
            }

            if (
                isset(
                    $risultato[$data]
                )
            ) {
                usort(
                    $risultato[$data],
                    static function (
                        array $a,
                        array $b
                    ): int {
                        return
                            strcmp(
                                $a['oraInizio'],
                                $b['oraInizio']
                            );
                    }
                );
            }
        }

        return $risultato;
    }


    public function orarioDisponibile(
        int $idServizio,
        string $data,
        string $oraInizio
    ): bool {
        if (
            !$this->dataOraFutura(
                $data,
                $oraInizio
            )
        ) {
            return false;
        }

        $servizio =
            FServizio::load(
                $idServizio
            );

        if ($servizio === null) {
            return false;
        }

        if (!$servizio->isAttivo()) {
            return false;
        }

        $durata =
            $servizio->getDurata();

        $inizioRichiesto =
            $this->oraInMinuti(
                $oraInizio
            );

        $fasceDisponibilita =
            FDisponibilita::loadByData(
                $data
            );

        $prenotazioni =
            FPrenotazione::loadByData(
                $data
            );

        foreach (
            $fasceDisponibilita as $fascia
        ) {
            $inizioFascia =
                $this->oraInMinuti(
                    $fascia->getOraInizio()
                );

            $fineFascia =
                $this->oraInMinuti(
                    $fascia->getOraFine()
                );

            $blocchiLiberi =
                $this->calcolaBlocchiLiberi(
                    $inizioFascia,
                    $fineFascia,
                    $prenotazioni
                );

            foreach (
                $blocchiLiberi as $blocco
            ) {
                $iniziSlot =
                    $this->calcolaIniziSlot(
                        $inizioFascia,
                        $fineFascia,
                        $blocco['inizio'],
                        $blocco['fine'],
                        $durata
                    );

                if (
                    in_array(
                        $inizioRichiesto,
                        $iniziSlot,
                        true
                    )
                ) {
                    return true;
                }
            }
        }

        return false;
    }


    public function confermaPrenotazione(
        int $idCliente,
        int $idServizio,
        string $data,
        string $oraInizio
    ): ?EPrenotazione {
        if (
            !$this->isClienteAutenticato(
                $idCliente
            )
            ||
            !$this->dataOraFutura(
                $data,
                $oraInizio
            )
        ) {
            return null;
        }

        $connection =
            FConnectionDB::connect();

        $connection->beginTransaction();

        try {
            /*
             * Serializziamo il calendario
             * prima di ricontrollare
             * lo slot selezionato.
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
            ) {
                $connection->rollBack();

                return null;
            }

            $ora =
                substr(
                    $oraInizio,
                    0,
                    5
                );

            /*
             * Lumèa consente
             * una sola prenotazione
             * attiva per cliente.
             */
            foreach (
                FPrenotazione::loadByCliente(
                    $idCliente
                ) as $esistente
            ) {
                if (
                    $esistente->getStato()
                    === 'prenotata'
                ) {
                    /*
                     * Se lo stesso form arriva due volte, la prima richiesta
                     * può aver già concluso il commit. In quel caso il
                     * risultato richiesto esiste già e non è un errore.
                     */
                    $stessaPrenotazione =
                        $esistente->getServizio()->getId() === $idServizio
                        && $esistente->getData() === $data
                        && substr($esistente->getOra(), 0, 5) === $ora;

                    $connection->rollBack();

                    return $stessaPrenotazione ? $esistente : null;
                }
            }

            /*
             * Ricontrolliamo lo slot
             * DOPO il lock del calendario.
             *
             * orarioDisponibile usa lo
             * stesso algoritmo impiegato
             * per generare gli orari
             * mostrati al cliente.
             */
            if (
                !$this->orarioDisponibile(
                    $idServizio,
                    $data,
                    $ora
                )
                ||
                FPrenotazione
                ::esisteSovrapposizione(
                    $data,
                    $ora . ':00',
                    $servizio->getDurata()
                )
            ) {
                $connection->rollBack();

                return null;
            }

            $prenotazione =
                new EPrenotazione(
                    $cliente,
                    $servizio,
                    $data,
                    $ora . ':00'
                );

            if (
                !FPrenotazione::store(
                    $prenotazione
                )
            ) {
                throw new RuntimeException(
                    'Prenotazione non salvata.'
                );
            }

            if (!$connection->commit()) {
                throw new RuntimeException(
                    'Commit della prenotazione non riuscito.'
                );
            }
        } catch (Throwable $e) {
            if (
                $connection
                    ->inTransaction()
            ) {
                $connection->rollBack();
            }

            error_log(
                'Lumèa, prenotazione: '
                    . $e->getMessage()
            );

            return null;
        }

        /*
         * L'email parte soltanto
         * dopo il commit.
         */
        try {
            FEmail::confermaPrenotazione(
                $prenotazione
            );
        } catch (Throwable $e) {
            error_log(
                'Lumèa, email prenotazione: '
                    . $e->getMessage()
            );
        }

        return $prenotazione;
    }


    private function getClienteAutenticato(): ?EUtente
    {
        $idUtente =
            FSessione::getIdUtenteAutenticato();

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
            FSessione::getIdUtenteAutenticato();

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


    private function getCategoria(
        array $get
    ): ?string {
        $categoria =
            $get['categoria']
            ?? null;

        if (
            !in_array(
                $categoria,
                [
                    'Uomo',
                    'Donna'
                ],
                true
            )
        ) {
            return null;
        }

        return $categoria;
    }


    private function getServizioSelezionato(
        array $get,
        array $servizi
    ): ?EServizio {
        $idServizio =
            (int) (
                $get['servizio']
                ?? 0
            );

        foreach (
            $servizi as $servizio
        ) {
            if (
                $servizio->getId()
                === $idServizio
            ) {
                return $servizio;
            }
        }

        return null;
    }


    private function getDataSelezionata(
        array $get,
        array $disponibilita
    ): ?string {
        $data =
            $get['data']
            ?? null;

        if ($data === null) {
            return null;
        }

        if (
            !isset(
                $disponibilita[$data]
            )
        ) {
            return null;
        }

        return $data;
    }


    private function getOraSelezionata(
        array $get,
        array $orari
    ): ?string {
        $ora =
            $get['ora']
            ?? null;

        if ($ora === null) {
            return null;
        }

        foreach (
            $orari as $orario
        ) {
            if (
                $orario['oraInizio']
                === $ora
            ) {
                return $ora;
            }
        }

        return null;
    }


    private function creaRigheStorico(
        array $prenotazioni
    ): array {
        $righe =
            [];

        $etichette = [
            'completata' =>
            'Completata',

            'cancellata' =>
            'Cancellata',

            'no-show' =>
            'Non presentato'
        ];

        foreach (
            $prenotazioni as $prenotazione
        ) {
            $data =
                DateTimeImmutable
                ::createFromFormat(
                    '!Y-m-d',
                    $prenotazione
                        ->getData()
                );

            $oraInizio =
                substr(
                    $prenotazione
                        ->getOra(),
                    0,
                    5
                );

            $ora =
                DateTimeImmutable
                ::createFromFormat(
                    '!H:i',
                    $oraInizio
                );

            $righe[] = [
                'data' =>
                $data === false
                    ? $prenotazione
                        ->getData()
                    : $data
                        ->format('d/m/Y'),

                'ora_inizio' =>
                $oraInizio,

                'ora_fine' =>
                $ora === false
                    ? ''
                    : $ora
                        ->modify(
                            '+'
                                . $prenotazione
                                    ->getDurata()
                                . ' minutes'
                        )
                        ->format('H:i'),

                'servizio' =>
                $prenotazione
                    ->getNomeServizio(),

                'stato' =>
                $etichette[$prenotazione
                    ->getStato()]
                    ??
                    $prenotazione
                        ->getStato()
            ];
        }

        return $righe;
    }


    private function getStepPrenotazione(
        bool $nuovaPrenotazione,
        ?string $categoria,
        ?EServizio $servizio,
        ?string $data,
        ?string $ora
    ): string {
        if (
            !$nuovaPrenotazione
            &&
            $categoria === null
            &&
            $servizio === null
            &&
            $data === null
            &&
            $ora === null
        ) {
            return 'inizio';
        }

        if ($categoria === null) {
            return 'categoria';
        }

        if ($servizio === null) {
            return 'servizio';
        }

        if ($data === null) {
            return 'data';
        }

        if ($ora === null) {
            return 'ora';
        }

        return 'riepilogo';
    }


    private function getErrorePrenotazione(
        string $errore
    ): ?string {
        switch ($errore) {
            case 'attesa':
                return 'Non è stato possibile entrare in lista d\'attesa.';

            default:
                return null;
        }
    }


    private function calcolaIniziSlot(
        int $inizioFascia,
        int $fineFascia,
        int $inizioBlocco,
        int $fineBlocco,
        int $durata
    ): array {
        return EDisponibilita::calcolaIniziSlot(
            $inizioFascia,
            $fineFascia,
            $inizioBlocco,
            $fineBlocco,
            $durata
        );
    }


    private function calcolaBlocchiLiberi(
        int $inizioFascia,
        int $fineFascia,
        array $prenotazioni
    ): array {
        $blocchiLiberi =
            [];

        $cursore =
            $inizioFascia;

        foreach (
            $prenotazioni as $prenotazione
        ) {
            if (
                $prenotazione->getStato()
                !== 'prenotata'
            ) {
                continue;
            }

            $inizioPrenotazione =
                $this->oraInMinuti(
                    $prenotazione
                        ->getOra()
                );

            $finePrenotazione =
                $inizioPrenotazione
                +
                $prenotazione
                    ->getDurata();

            if (
                $finePrenotazione
                <=
                $inizioFascia
                ||
                $inizioPrenotazione
                >=
                $fineFascia
            ) {
                continue;
            }

            if (
                $inizioPrenotazione
                >
                $cursore
            ) {
                $fineBlocco =
                    $inizioPrenotazione;

                if (
                    $fineBlocco
                    >
                    $fineFascia
                ) {
                    $fineBlocco =
                        $fineFascia;
                }

                if (
                    $cursore
                    <
                    $fineBlocco
                ) {
                    $blocchiLiberi[] = [
                        'inizio' =>
                        $cursore,

                        'fine' =>
                        $fineBlocco
                    ];
                }
            }

            if (
                $finePrenotazione
                >
                $cursore
            ) {
                $cursore =
                    $finePrenotazione;
            }

            if (
                $cursore
                <
                $inizioFascia
            ) {
                $cursore =
                    $inizioFascia;
            }

            if (
                $cursore
                >=
                $fineFascia
            ) {
                break;
            }
        }

        if (
            $cursore
            <
            $fineFascia
        ) {
            $blocchiLiberi[] = [
                'inizio' =>
                $cursore,

                'fine' =>
                $fineFascia
            ];
        }

        return $blocchiLiberi;
    }


    private function dataOraFutura(
        string $data,
        string $ora
    ): bool {
        if (
            !preg_match(
                '/^(?:[01]\d|2[0-3]):[0-5]\d(?::00)?$/D',
                $ora
            )
        ) {
            return false;
        }

        $giorno =
            DateTimeImmutable
            ::createFromFormat(
                '!Y-m-d',
                $data
            );

        if (
            $giorno === false
            ||
            $giorno->format('Y-m-d')
            !==
            $data
        ) {
            return false;
        }

        $dataOra =
            strtotime(
                $data
                    . ' '
                    . $ora
            );

        return
            $dataOra !== false
            &&
            $dataOra > time();
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
