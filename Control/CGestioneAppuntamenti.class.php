<?php

require_once __DIR__ . '/../Foundation/FSessione.class.php';
require_once __DIR__ . '/../Foundation/FConnectionDB.class.php';
require_once __DIR__ . '/../Foundation/FUtente.class.php';
require_once __DIR__ . '/../Foundation/FPrenotazione.class.php';
require_once __DIR__ . '/../Foundation/FListaAttesa.class.php';
require_once __DIR__ . '/../Foundation/FPagamento.class.php';

require_once __DIR__ . '/../Entity/EPrenotazione.class.php';
require_once __DIR__ . '/../Entity/EListaAttesa.class.php';
require_once __DIR__ . '/../Entity/EServizio.class.php';

class CGestioneAppuntamenti
{
    public function appuntamentoAttivoPerCliente(
        int $idCliente
    ): ?EPrenotazione {
        if (!$this->isClienteAutenticato($idCliente)) {
            return null;
        }

        $cliente =
            FUtente::load(
                $idCliente
            );

        if ($cliente === null) {
            return null;
        }

        if (!$cliente->isCliente()) {
            return null;
        }

        $prenotazioni =
            FPrenotazione::loadByCliente(
                $idCliente
            );

        foreach (
            $prenotazioni
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
        if (!$this->isClienteAutenticato($idCliente)) {
            return [];
        }

        $cliente =
            FUtente::load(
                $idCliente
            );

        if ($cliente === null) {
            return [];
        }

        if (!$cliente->isCliente()) {
            return [];
        }

        $prenotazioni =
            FPrenotazione::loadByCliente(
                $idCliente
            );

        $storico = [];

        foreach (
            $prenotazioni
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

    public function storicoPagamentiCliente(int $idCliente): array
    {
        if (!$this->isClienteAutenticato($idCliente)) {
            return [];
        }

        return FPagamento::loadByCliente($idCliente);
    }

    public function puoAnnullareOnline(
        int $idCliente,
        int $idPrenotazione
    ): bool {
        if (!$this->isClienteAutenticato($idCliente)) {
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
        if (!$this->isClienteAutenticato($idCliente)) {
            return null;
        }

        $cliente =
            FUtente::load(
                $idCliente
            );

        if ($cliente === null) {
            return null;
        }

        if (!$cliente->isCliente()) {
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
            $prenotazione->getStato()
            !== 'prenotata'
        ) {
            return null;
        }

        /*
         * Cancellazione online consentita
         * anche a ESATTAMENTE 48 ore.
         */
        if (
            !$this->mancanoAlmeno48Ore(
                $prenotazione->getData(),
                $prenotazione->getOra()
            )
        ) {
            return null;
        }

        $connection =
            FConnectionDB::connect();

        $connection->beginTransaction();

        try {
            /*
             * 1. Cancellazione della
             * prenotazione del cliente.
             */
            $prenotazione->cancella();

            $prenotazioneAggiornata =
                FPrenotazione::update(
                    $prenotazione
                );

            if (!$prenotazioneAggiornata) {
                throw new Exception(
                    'Errore nella cancellazione della prenotazione.'
                );
            }

            /*
             * 2. Il posto è libero:
             * controlliamo la lista d'attesa
             * FIFO per servizio/data/ora.
             */
            $this
                ->assegnaPrimaRichiestaValida(
                    $prenotazione
                );

            $connection->commit();

            /*
             * Restituiamo la prenotazione
             * ormai cancellata.
             *
             * La View potrà usarla anche
             * per mostrare subito:
             *
             * "Vuoi prenotare lo stesso
             * servizio in un altro giorno?"
             */
            return $prenotazione;
        } catch (Throwable $e) {
            $connection->rollBack();

            return null;
        }
    }

    public function servizioDaRiprenotare(
        int $idCliente,
        int $idPrenotazione
    ): ?EServizio {
        if (!$this->isClienteAutenticato($idCliente)) {
            return null;
        }

        $cliente =
            FUtente::load(
                $idCliente
            );

        if ($cliente === null) {
            return null;
        }

        if (!$cliente->isCliente()) {
            return null;
        }

        /*
         * Se è bloccato per no-show,
         * non può avviare una nuova
         * prenotazione online.
         */
        if (
            !$cliente
                ->isPrenotazioniOnlineAbilitate()
        ) {
            return null;
        }

        /*
         * Non può riprenotare se possiede
         * già un altro appuntamento attivo.
         */
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

        /*
         * Il tasto "Riprenota" nello
         * storico compare solamente per:
         *
         * - completata
         * - no-show
         *
         * NON per cancellata.
         */
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

        /*
         * Non possiamo riprenotare
         * automaticamente un servizio
         * che il proprietario ha
         * disattivato.
         */
        if (!$servizio->isAttivo()) {
            return null;
        }

        return $servizio;
    }

    private function isClienteAutenticato(int $idCliente): bool
    {
        $idUtente = FSessione::getIdUtenteAutenticato();

        if ($idUtente === null || $idUtente !== $idCliente) {
            return false;
        }

        $utente = FUtente::load($idUtente);

        return $utente !== null && $utente->isCliente();
    }

    private function assegnaPrimaRichiestaValida(
        EPrenotazione $prenotazioneLiberata
    ): ?EListaAttesa {
        $servizio =
            $prenotazioneLiberata
            ->getServizio();

        $data =
            $prenotazioneLiberata
            ->getData();

        $ora =
            $prenotazioneLiberata
            ->getOra();

        $coda =
            FListaAttesa
            ::loadAttiveByServizioDataOra(
                $servizio->getId(),
                $data,
                $ora
            );

        foreach (
            $coda
            as $richiesta
        ) {
            if (!$richiesta->isAttiva()) {
                continue;
            }

            /*
             * La lista d'attesa non
             * può più produrre una
             * prenotazione quando
             * siamo arrivati a 48 ore.
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

                $aggiornata =
                    FListaAttesa::update(
                        $richiesta
                    );

                if (!$aggiornata) {
                    throw new Exception(
                        'Errore nella scadenza della lista d\'attesa.'
                    );
                }

                continue;
            }

            $cliente =
                $richiesta->getCliente();

            /*
             * Se nel frattempo il cliente
             * è stato bloccato dalle
             * prenotazioni online,
             * non gli assegniamo
             * automaticamente il posto.
             */
            if (
                !$cliente
                    ->isPrenotazioniOnlineAbilitate()
            ) {
                continue;
            }

            /*
             * Controlliamo quante
             * prenotazioni attive possiede
             * in questo momento.
             */
            $prenotazioniAttive =
                $this->prenotazioniAttiveCliente(
                    $cliente->getId()
                );

            /*
             * Questa situazione viola
             * la regola HairBooking.
             */
            if (
                count($prenotazioniAttive)
                > 1
            ) {
                continue;
            }

            $prenotazioneAlternativa =
                null;

            if (
                count($prenotazioniAttive)
                === 1
            ) {
                $prenotazioneAlternativa =
                    $prenotazioniAttive[0];

                /*
                 * Deve essere realmente
                 * un'alternativa:
                 * stesso servizio.
                 */
                if (
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

                $dataOraAlternativa =
                    $prenotazioneAlternativa
                    ->getData()
                    . ' '
                    . $prenotazioneAlternativa
                    ->getOra();

                $dataOraDesiderata =
                    $richiesta
                    ->getDataDesiderata()
                    . ' '
                    . $richiesta
                    ->getOraDesiderata();

                /*
                 * L'alternativa deve essere
                 * successiva all'orario
                 * desiderato.
                 */
                if (
                    strtotime(
                        $dataOraAlternativa
                    )
                    <=
                    strtotime(
                        $dataOraDesiderata
                    )
                ) {
                    continue;
                }
            }

            /*
             * Il posto dovrebbe essere
             * libero dopo la cancellazione,
             * ma lo ricontrolliamo prima
             * di creare la nuova
             * prenotazione.
             */
            if (
                FPrenotazione
                ::esisteSovrapposizione(
                    $richiesta
                        ->getDataDesiderata(),
                    $richiesta
                        ->getOraDesiderata(),
                    $richiesta
                        ->getServizio()
                        ->getDurata()
                )
            ) {
                continue;
            }

            /*
             * Se esiste un'alternativa,
             * la cancelliamo.
             */
            if (
                $prenotazioneAlternativa
                !== null
            ) {
                $prenotazioneAlternativa
                    ->cancella();

                $alternativaAggiornata =
                    FPrenotazione::update(
                        $prenotazioneAlternativa
                    );

                if (!$alternativaAggiornata) {
                    throw new Exception(
                        'Errore nella cancellazione della prenotazione alternativa.'
                    );
                }
            }

            /*
             * Creiamo la nuova prenotazione
             * nell'orario desiderato.
             */
            $nuovaPrenotazione =
                new EPrenotazione(
                    $cliente,
                    $richiesta->getServizio(),
                    $richiesta
                        ->getDataDesiderata(),
                    $richiesta
                        ->getOraDesiderata()
                );

            $prenotazioneSalvata =
                FPrenotazione::store(
                    $nuovaPrenotazione
                );

            if (!$prenotazioneSalvata) {
                throw new Exception(
                    'Errore nella creazione della prenotazione da lista d\'attesa.'
                );
            }

            /*
             * La richiesta FIFO scelta
             * diventa soddisfatta.
             */
            $richiesta->soddisfa();

            $richiestaAggiornata =
                FListaAttesa::update(
                    $richiesta
                );

            if (!$richiestaAggiornata) {
                throw new Exception(
                    'Errore nell\'aggiornamento della lista d\'attesa.'
                );
            }

            /*
             * Abbiamo soddisfatto il
             * PRIMO cliente valido.
             *
             * Gli altri rimangono
             * attivi nella coda.
             */
            return $richiesta;
        }

        return null;
    }

    private function prenotazioniAttiveCliente(
        int $idCliente
    ): array {
        $prenotazioni =
            FPrenotazione::loadByCliente(
                $idCliente
            );

        $attive = [];

        foreach (
            $prenotazioni
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

        $secondiRimanenti =
            $dataOra - time();

        $quarantottoOre =
            48 * 60 * 60;

        return
            $secondiRimanenti
            >= $quarantottoOre;
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

        $secondiRimanenti =
            $dataOra - time();

        $quarantottoOre =
            48 * 60 * 60;

        return
            $secondiRimanenti
            > $quarantottoOre;
    }
}
