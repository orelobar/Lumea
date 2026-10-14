<?php

require_once __DIR__ . '/../Foundation/FSessione.class.php';
require_once __DIR__ . '/../Foundation/FConnectionDB.class.php';
require_once __DIR__ . '/../Foundation/FUtente.class.php';
require_once __DIR__ . '/../Foundation/FPrenotazione.class.php';
require_once __DIR__ . '/../Foundation/FListaAttesa.class.php';
require_once __DIR__ . '/../Foundation/FPagamento.class.php';
require_once __DIR__ . '/../Foundation/FDisponibilita.class.php';
require_once __DIR__ . '/../Foundation/FEmail.class.php';

require_once __DIR__ . '/../Entity/EUtente.class.php';
require_once __DIR__ . '/../Entity/EPrenotazione.class.php';
require_once __DIR__ . '/../Entity/EListaAttesa.class.php';
require_once __DIR__ . '/../Entity/EServizio.class.php';

require_once __DIR__ . '/CGestioneListaAttesa.class.php';
require_once __DIR__ . '/CGestionePrenotazione.class.php';

require_once __DIR__ . '/../View/VAppuntamentiCliente.class.php';


class CGestioneAppuntamenti
{
    public function mostraPrenotazioniCliente(
        array $request = []
    ): void {
        $cliente = $this->getClienteAutenticato();

        if (
            $cliente === null
            ||
            $cliente->getId() === null
        ) {
            $this->redirect('index.php');
        }

        $idCliente = $cliente->getId();

        $appuntamentoAttivo =
            $this->appuntamentoAttivoPerCliente(
                $idCliente
            );

        $storico =
            $this->storicoPrenotazioniCliente(
                $idCliente
            );

        $puoAnnullare = false;

        if (
            $appuntamentoAttivo !== null
            &&
            $appuntamentoAttivo->getId() !== null
        ) {
            $puoAnnullare =
                $this->puoAnnullareOnline(
                    $idCliente,
                    $appuntamentoAttivo->getId()
                );
        }

        $gestioneListaAttesa =
            new CGestioneListaAttesa();

        $richiestaAttesa =
            $gestioneListaAttesa
            ->richiestaAttivaPerCliente(
                $idCliente
            );

        $get = $request['get'] ?? [];

        $ripetibili = [];

        foreach (
            $storico
            as $prenotazioneStorica
        ) {
            if (
                $this->servizioDaRiprenotare(
                    $idCliente,
                    $prenotazioneStorica->getId()
                ) !== null
            ) {
                $ripetibili[$prenotazioneStorica->getId()] = true;
            }
        }

        $appuntamentoAttivoDettaglio = $appuntamentoAttivo === null
            ? null
            : $this->creaRigaPrenotazioneCliente($appuntamentoAttivo);
        $storicoDettagli = [];

        foreach ($storico as $prenotazioneStorica) {
            $storicoDettagli[] = $this->creaRigaPrenotazioneCliente($prenotazioneStorica);
        }

        $view = new VAppuntamentiCliente();
        $view->mostraAppuntamenti(
            $appuntamentoAttivo,
            $storico,
            $puoAnnullare,
            $richiestaAttesa,
            $this->getMessaggioPrenotazioni($get['esito'] ?? ''),
            $this->getErrorePrenotazioni($get['errore'] ?? ''),
            $ripetibili,
            $appuntamentoAttivoDettaglio,
            $storicoDettagli,
            $cliente->isPrenotazioniOnlineAbilitate()
        );
    }


    public function gestisciAnnullamentoPrenotazione(
        array $request
    ): void {
        $cliente =
            $this->getClienteAutenticato();

        if (
            $cliente === null
            ||
            $cliente->getId() === null
        ) {
            $this->redirect('index.php');
        }

        $post = $request['post'] ?? [];

        $prenotazione =
            $this->annullaPrenotazione(
                $cliente->getId(),
                (int) (
                    $post['id_prenotazione']
                    ?? 0
                )
            );

        if ($prenotazione !== null) {
            $this->redirect(
                'index.php?pagina=prenotazioni&esito=annullata'
            );
        }

        $this->redirect(
            'index.php?pagina=prenotazioni&errore=annullamento'
        );
    }


    public function gestisciRipetiServizio(
        array $request
    ): void {
        $cliente =
            $this->getClienteAutenticato();

        if (
            $cliente === null
            ||
            $cliente->getId() === null
        ) {
            $this->redirect('index.php');
        }

        $get = $request['get'] ?? [];

        $servizio =
            $this->servizioDaRiprenotare(
                $cliente->getId(),
                (int) (
                    $get['idPrenotazione']
                    ?? 0
                )
            );

        if (
            $servizio === null
            ||
            $servizio->getId() === null
        ) {
            $this->redirect(
                'index.php?pagina=prenotazioni&errore=ripeti'
            );
        }

        $url =
            'index.php?pagina=prenota'
            . '&categoria='
            . rawurlencode(
                $servizio->getCategoria()
            )
            . '&servizio='
            . $servizio->getId();

        $this->redirect($url);
    }


    public function appuntamentoAttivoPerCliente(
        int $idCliente
    ): ?EPrenotazione {
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
                return $prenotazione;
            }
        }

        return null;
    }


    public function storicoPrenotazioniCliente(
        int $idCliente
    ): array {
        if (
            !$this->isClienteAutenticato(
                $idCliente
            )
        ) {
            return [];
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
            return [];
        }

        $storico = [];

        foreach (
            FPrenotazione::loadByCliente(
                $idCliente
            )
            as $prenotazione
        ) {
            if (
                $prenotazione->getStato()
                !== 'prenotata'
            ) {
                $storico[] =
                    $prenotazione;
            }
        }

        return $storico;
    }


    public function storicoPagamentiCliente(
        int $idCliente
    ): array {
        if (
            !$this->isClienteAutenticato(
                $idCliente
            )
        ) {
            return [];
        }

        return
            FPagamento::loadByCliente(
                $idCliente
            );
    }


    public function puoAnnullareOnline(
        int $idCliente,
        int $idPrenotazione
    ): bool {
        if (
            !$this->isClienteAutenticato(
                $idCliente
            )
        ) {
            return false;
        }

        $prenotazione =
            FPrenotazione::load(
                $idPrenotazione
            );

        if ($prenotazione === null) {
            return false;
        }

        if (
            $prenotazione
            ->getCliente()
            ->getId()
            !== $idCliente
        ) {
            return false;
        }

        if (
            $prenotazione->getStato()
            !== 'prenotata'
        ) {
            return false;
        }

        return
            $this->mancanoAlmeno48Ore(
                $prenotazione->getData(),
                $prenotazione->getOra()
            );
    }


    public function annullaPrenotazione(
        int $idCliente,
        int $idPrenotazione
    ): ?EPrenotazione {
        if (
            !$this->isClienteAutenticato(
                $idCliente
            )
        ) {
            return null;
        }

        $connection =
            FConnectionDB::connect();

        $connection->beginTransaction();

        $assegnazione = null;

        try {
            FConnectionDB::bloccaCalendario();

            $prenotazione =
                FPrenotazione::loadPerAggiornamento(
                    $idPrenotazione
                );

            if (
                $prenotazione === null
                ||
                $prenotazione
                ->getCliente()
                ->getId()
                !== $idCliente
                ||
                $prenotazione->getStato()
                !== 'prenotata'
                ||
                !$this->mancanoAlmeno48Ore(
                    $prenotazione->getData(),
                    $prenotazione->getOra()
                )
            ) {
                $connection->rollBack();

                return null;
            }

            $prenotazione->cancella();

            if (
                !FPrenotazione::update(
                    $prenotazione
                )
            ) {
                throw new RuntimeException(
                    'Cancellazione non salvata.'
                );
            }

            /*
             * La cancellazione modifica il
             * calendario. Prima del commit
             * ricalcoliamo quindi la coda
             * della giornata e assegniamo
             * la prima richiesta FIFO che
             * risulta davvero prenotabile.
             */
            $assegnazione =
                $this->assegnaPrimaRichiestaValida(
                    $prenotazione
                );

            $connection->commit();
        } catch (Throwable $e) {
            if (
                $connection->inTransaction()
            ) {
                $connection->rollBack();
            }

            error_log(
                'Lumèa, annullamento: '
                    . $e->getMessage()
            );

            return null;
        }

        /*
         * L'email viene inviata soltanto
         * dopo il commit della transazione.
         */
        if ($assegnazione !== null) {
            try {
                FEmail::assegnazioneListaAttesa(
                    $assegnazione['prenotazione'],
                    $assegnazione['alternativa']
                );
            } catch (Throwable $e) {
                error_log(
                    'Lumèa, email lista attesa: '
                        . $e->getMessage()
                );
            }
        }

        return $prenotazione;
    }


    public function servizioDaRiprenotare(
        int $idCliente,
        int $idPrenotazione
    ): ?EServizio {
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

        if (
            !$cliente
                ->isPrenotazioniOnlineAbilitate()
        ) {
            return null;
        }

        if (
            $this->appuntamentoAttivoPerCliente(
                $idCliente
            ) !== null
        ) {
            return null;
        }

        $prenotazione =
            FPrenotazione::load(
                $idPrenotazione
            );

        if ($prenotazione === null) {
            return null;
        }

        if (
            $prenotazione
            ->getCliente()
            ->getId()
            !== $idCliente
        ) {
            return null;
        }

        if (
            !in_array(
                $prenotazione->getStato(),
                [
                    'completata',
                    'no-show'
                ],
                true
            )
        ) {
            return null;
        }

        $servizio =
            $prenotazione
            ->getServizio();

        if (!$servizio->isAttivo()) {
            return null;
        }

        return $servizio;
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


    /**
     * Operazione condivisa dagli annullamenti cliente e proprietario.
     * Il chiamante mantiene aperta la transazione e il blocco del calendario;
     * invia l'eventuale notifica soltanto dopo il commit.
     */
    public function assegnaPrimaRichiestaValida(
        EPrenotazione $prenotazioneLiberata
    ): ?array {
        if (!FConnectionDB::connect()->inTransaction()) {
            throw new LogicException('L’assegnazione richiede una transazione attiva.');
        }
        FConnectionDB::bloccaCalendario();

        $salvata = $prenotazioneLiberata->getId() === null
            ? null
            : FPrenotazione::loadPerAggiornamento($prenotazioneLiberata->getId());
        if ($salvata === null || $salvata->getStato() !== 'cancellata') {
            throw new LogicException('La prenotazione deve essere già cancellata.');
        }

        $data =
            $salvata->getData();

        /*
         * Prima il sistema cercava soltanto
         * richieste con la stessa identica
         * ora iniziale dell'appuntamento
         * cancellato.
         *
         * Non è sufficiente quando i servizi
         * hanno durate differenti.
         *
         * Ora carichiamo tutte le richieste
         * attive di quella giornata in vero
         * ordine FIFO e verifichiamo quale
         * sia diventata realmente assegnabile.
         */
        $coda =
            FListaAttesa::loadAttiveByData(
                $data
            );

        /*
         * Per la verifica finale riutilizziamo
         * lo stesso algoritmo della prenotazione
         * normale.
         *
         * Quindi valgono anche qui:
         *
         * - griglia iniziale ogni 30 minuti;
         * - durata del servizio multipla di 15;
         * - ricalcolo sui nuovi estremi dei
         *   blocchi liberi;
         * - nessun buco creato da orari
         *   arbitrari.
         */
        $gestionePrenotazione =
            new CGestionePrenotazione();

        foreach (
            $coda
            as $richiesta
        ) {
            if (!$richiesta->isAttiva()) {
                continue;
            }

            /*
             * Una richiesta resta assegnabile
             * soltanto finché mancano più
             * di 48 ore all'orario desiderato.
             */
            if (
                !$this->mancanoPiuDi48Ore(
                    $richiesta
                        ->getDataDesiderata(),
                    $richiesta
                        ->getOraDesiderata()
                )
            ) {
                $richiesta->scadi();

                if (
                    !FListaAttesa::update(
                        $richiesta
                    )
                ) {
                    throw new RuntimeException(
                        "Errore nella scadenza della lista d'attesa."
                    );
                }

                continue;
            }

            $cliente =
                $richiesta->getCliente();

            if (
                !$cliente
                    ->isPrenotazioniOnlineAbilitate()
            ) {
                continue;
            }

            $prenotazioniAttive =
                $this->prenotazioniAttiveCliente(
                    $cliente->getId()
                );

            /*
             * Protezione contro eventuali
             * dati incoerenti.
             */
            if (
                count(
                    $prenotazioniAttive
                )
                > 1
            ) {
                continue;
            }

            $prenotazioneAlternativa =
                $prenotazioniAttive[0]
                ?? null;

            // Ricontrolla gli intervalli dopo eventuali cambiamenti successivi alla richiesta.
            if ($prenotazioneAlternativa !== null && $prenotazioneAlternativa->siSovrapponeA(
                $richiesta->getDataDesiderata(), $richiesta->getOraDesiderata(),
                $richiesta->getServizio()->getDurata()
            )) {
                continue;
            }

            /*
             * Se il cliente possiede già
             * una prenotazione attiva,
             * l'orario in lista d'attesa
             * deve riguardare lo stesso
             * servizio.
             */
            if (
                $prenotazioneAlternativa
                !== null
                &&
                $prenotazioneAlternativa
                ->getServizio()
                ->getId()
                !==
                $richiesta
                ->getServizio()
                ->getId()
            ) {
                continue;
            }

            $servizioRichiesto =
                $richiesta->getServizio();

            if (
                $servizioRichiesto->getId()
                === null
                ||
                !$servizioRichiesto->isAttivo()
            ) {
                continue;
            }

            /*
             * Il servizio deve stare
             * interamente dentro una fascia
             * di apertura.
             */
            if (
                !$this->intervalloDentroDisponibilita(
                    $richiesta
                        ->getDataDesiderata(),
                    $richiesta
                        ->getOraDesiderata(),
                    $servizioRichiesto
                        ->getDurata()
                )
            ) {
                continue;
            }

            /*
             * Ricalcolo vero del calendario.
             *
             * Non basta che l'intervallo
             * sia diventato genericamente
             * libero: deve corrispondere a
             * uno slot che il sistema
             * mostrerebbe in questo momento
             * a un cliente che sceglie
             * questo stesso servizio.
             */
            if (
                !$gestionePrenotazione
                    ->orarioDisponibile(
                        $servizioRichiesto
                            ->getId(),
                        $richiesta
                            ->getDataDesiderata(),
                        substr(
                            $richiesta
                                ->getOraDesiderata(),
                            0,
                            5
                        )
                    )
            ) {
                continue;
            }

            /*
             * Controllo ulteriore contro
             * sovrapposizioni, eseguito
             * nella stessa transazione
             * che ha liberato il calendario.
             */
            if (
                FPrenotazione
                ::esisteSovrapposizione(
                    $richiesta
                        ->getDataDesiderata(),
                    $richiesta
                        ->getOraDesiderata(),
                    $servizioRichiesto
                        ->getDurata()
                )
            ) {
                continue;
            }

            /*
             * Se il cliente aveva un
             * appuntamento alternativo,
             * lo cancelliamo solo dopo
             * avere verificato il nuovo
             * orario.
             */
            if (
                $prenotazioneAlternativa
                !== null
            ) {
                $prenotazioneAlternativa
                    ->cancella();

                if (
                    !FPrenotazione::update(
                        $prenotazioneAlternativa
                    )
                ) {
                    throw new RuntimeException(
                        'Errore nella cancellazione della prenotazione alternativa.'
                    );
                }
            }

            $nuovaPrenotazione =
                new EPrenotazione(
                    $cliente,
                    $servizioRichiesto,
                    $richiesta
                        ->getDataDesiderata(),
                    $richiesta
                        ->getOraDesiderata()
                );

            if (
                !FPrenotazione::store(
                    $nuovaPrenotazione
                )
            ) {
                throw new RuntimeException(
                    "Errore nella creazione della prenotazione da lista d'attesa."
                );
            }

            $richiesta->soddisfa();

            if (
                !FListaAttesa::update(
                    $richiesta
                )
            ) {
                throw new RuntimeException(
                    "Errore nell'aggiornamento della lista d'attesa."
                );
            }

            return [
                'richiesta' =>
                $richiesta,

                'prenotazione' =>
                $nuovaPrenotazione,

                'alternativa' =>
                $prenotazioneAlternativa
            ];
        }

        return null;
    }


    private function prenotazioniAttiveCliente(
        int $idCliente
    ): array {
        $attive = [];

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
                $attive[] =
                    $prenotazione;
            }
        }

        return $attive;
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
                    $fascia->getOraInizio()
                );

            $fineFascia =
                $this->oraInMinuti(
                    $fascia->getOraFine()
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


    private function oraInMinuti(
        string $ora
    ): int {
        $parti =
            explode(
                ':',
                $ora
            );

        return ((int) (
                $parti[0]
                ?? 0
            ) * 60)
            +
            (int) (
                $parti[1]
                ?? 0
            );
    }


    private function mancanoAlmeno48Ore(
        string $data,
        string $ora
    ): bool {
        $dataOra =
            strtotime(
                $data
                    . ' '
                    . $ora
            );

        if ($dataOra === false) {
            return false;
        }

        return (
                $dataOra
                -
                time()
            )
            >=
            (
                48
                * 60
                * 60
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
                    . $ora
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


    private function creaRigaPrenotazioneCliente(EPrenotazione $prenotazione): array
    {
        $etichette = [
            'prenotata' => 'Confermata',
            'completata' => 'Completata',
            'cancellata' => 'Cancellata',
            'no-show' => 'Non presentato'
        ];
        $data = DateTimeImmutable::createFromFormat('!Y-m-d', $prenotazione->getData());
        $oraInizio = substr($prenotazione->getOra(), 0, 5);
        $ora = DateTimeImmutable::createFromFormat('!H:i', $oraInizio);

        return [
            'id' => $prenotazione->getId(),
            'data' => $data === false ? $prenotazione->getData() : $data->format('d/m/Y'),
            'ora_inizio' => $oraInizio,
            'ora_fine' => $ora === false
                ? ''
                : $ora->modify('+' . $prenotazione->getDurata() . ' minutes')->format('H:i'),
            'servizio' => $prenotazione->getNomeServizio(),
            'durata' => $prenotazione->getDurata(),
            'stato' => $etichette[$prenotazione->getStato()] ?? $prenotazione->getStato()
        ];
    }

    private function getMessaggioPrenotazioni(
        string $esito
    ): ?string {
        switch ($esito) {
            case 'prenotata':
                return
                    'Prenotazione confermata correttamente.';

            case 'annullata':
                return
                    'Prenotazione annullata correttamente.';

            case 'attesa':
                return
                    'Richiesta inserita correttamente in lista d\'attesa.';

            case 'attesa_annullata':
                return
                    'Richiesta in lista d\'attesa annullata.';

            default:
                return null;
        }
    }


    private function getErrorePrenotazioni(
        string $errore
    ): ?string {
        switch ($errore) {
            case 'annullamento':
                return
                    'Non è stato possibile annullare la prenotazione.';

            case 'attesa':
                return
                    'Non è stato possibile modificare la lista d\'attesa.';

            case 'ripeti':
                return
                    'Il servizio non può essere riprenotato.';

            default:
                return null;
        }
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
