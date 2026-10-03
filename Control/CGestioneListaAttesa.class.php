<?php

require_once __DIR__ . '/../Foundation/FSessione.class.php';
require_once __DIR__ . '/../Foundation/FUtente.class.php';
require_once __DIR__ . '/../Foundation/FServizio.class.php';
require_once __DIR__ . '/../Foundation/FDisponibilita.class.php';
require_once __DIR__ . '/../Foundation/FPrenotazione.class.php';
require_once __DIR__ . '/../Foundation/FListaAttesa.class.php';
require_once __DIR__ . '/../Entity/EListaAttesa.class.php';

class CGestioneListaAttesa
{
    public function richiestaAttivaPerCliente(
        int $idCliente
    ): ?EListaAttesa {
        if (!$this->isClienteAutenticato($idCliente)) {
            return null;
        }

        $cliente = FUtente::load($idCliente);

        if ($cliente === null) {
            return null;
        }

        if (!$cliente->isCliente()) {
            return null;
        }

        $richiesta =
            FListaAttesa::loadAttivaByCliente(
                $idCliente
            );

        if ($richiesta === null) {
            return null;
        }

        $aggiornata =
            $this->aggiornaScadenzaSeNecessaria(
                $richiesta
            );

        if (!$aggiornata) {
            return null;
        }

        if (!$richiesta->isAttiva()) {
            return null;
        }

        return $richiesta;
    }

    public function statoRichiestaPerCliente(
        int $idCliente,
        int $idRichiesta
    ): ?string {
        if (!$this->isClienteAutenticato($idCliente)) {
            return null;
        }

        $richiesta =
            FListaAttesa::load(
                $idRichiesta
            );

        if ($richiesta === null) {
            return null;
        }

        if (
            $richiesta
            ->getCliente()
            ->getId()
            !== $idCliente
        ) {
            return null;
        }

        $aggiornata =
            $this->aggiornaScadenzaSeNecessaria(
                $richiesta
            );

        if (!$aggiornata) {
            return null;
        }

        return $richiesta->getStato();
    }

    public function inserisciInListaAttesa(
        int $idCliente,
        int $idServizio,
        string $dataDesiderata,
        string $oraDesiderata
    ): ?EListaAttesa {
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

        if (
            !$cliente
                ->isPrenotazioniOnlineAbilitate()
        ) {
            return null;
        }

        $servizio =
            FServizio::load(
                $idServizio
            );

        if ($servizio === null) {
            return null;
        }

        if (!$servizio->isAttivo()) {
            return null;
        }

        $richiestaGiaAttiva =
            $this->richiestaAttivaPerCliente(
                $idCliente
            );

        if ($richiestaGiaAttiva !== null) {
            return null;
        }

        $oraNormalizzata =
            $this->normalizzaOra(
                $oraDesiderata
            );

        if (
            !$this->orarioSuGrigliaQuindiciMinuti(
                $oraNormalizzata
            )
        ) {
            return null;
        }

        /*
         * La lista d'attesa è utilizzabile
         * solamente quando mancano PIÙ
         * di 48 ore all'orario desiderato.
         */
        if (
            !$this->mancanoPiuDi48Ore(
                $dataDesiderata,
                $oraNormalizzata
            )
        ) {
            return null;
        }

        if (
            !$this->intervalloDentroDisponibilita(
                $dataDesiderata,
                $oraNormalizzata,
                $servizio->getDurata()
            )
        ) {
            return null;
        }

        /*
         * Posso entrare in lista solamente
         * se l'orario desiderato è realmente
         * occupato.
         */
        $orarioOccupato =
            FPrenotazione::esisteSovrapposizione(
                $dataDesiderata,
                $oraNormalizzata,
                $servizio->getDurata()
            );

        if (!$orarioOccupato) {
            return null;
        }

        $prenotazioniAttive =
            $this->prenotazioniAttiveCliente(
                $idCliente
            );

        /*
         * HairBooking consente al massimo
         * una prenotazione attiva.
         *
         * Questa è comunque una protezione
         * aggiuntiva contro dati incoerenti.
         */
        if (
            count($prenotazioniAttive) > 1
        ) {
            return null;
        }

        $prenotazioneAlternativa = null;

        /*
         * Se il cliente possiede già
         * una prenotazione attiva,
         * quella diventa automaticamente
         * l'alternativa.
         */
        if (
            count($prenotazioniAttive) === 1
        ) {
            $prenotazioneAlternativa =
                $prenotazioniAttive[0];

            /*
             * L'alternativa deve riguardare
             * lo stesso servizio.
             */
            if (
                $prenotazioneAlternativa
                ->getServizio()
                ->getId()
                !==
                $servizio->getId()
            ) {
                return null;
            }

            $dataOraAlternativa =
                $prenotazioneAlternativa
                ->getData()
                . ' '
                . $this->normalizzaOra(
                    $prenotazioneAlternativa
                        ->getOra()
                );

            $dataOraDesiderata =
                $dataDesiderata
                . ' '
                . $oraNormalizzata;

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
                return null;
            }
        }

        $richiesta =
            new EListaAttesa(
                $cliente,
                $servizio,
                $dataDesiderata,
                $oraNormalizzata,
                date('Y-m-d H:i:s'),
                $prenotazioneAlternativa
            );

        $salvata =
            FListaAttesa::store(
                $richiesta
            );

        if (!$salvata) {
            return null;
        }

        return $richiesta;
    }

    public function annullaRichiesta(
        int $idCliente,
        int $idRichiesta
    ): bool {
        if (!$this->isClienteAutenticato($idCliente)) {
            return false;
        }

        $richiesta =
            FListaAttesa::load(
                $idRichiesta
            );

        if ($richiesta === null) {
            return false;
        }

        if (
            $richiesta
            ->getCliente()
            ->getId()
            !== $idCliente
        ) {
            return false;
        }

        $aggiornata =
            $this->aggiornaScadenzaSeNecessaria(
                $richiesta
            );

        if (!$aggiornata) {
            return false;
        }

        if (!$richiesta->isAttiva()) {
            return false;
        }

        $richiesta->annulla();

        return FListaAttesa::update(
            $richiesta
        );
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

    private function prenotazioniAttiveCliente(
        int $idCliente
    ): array {
        $prenotazioni =
            FPrenotazione::loadByCliente(
                $idCliente
            );

        $prenotazioniAttive = [];

        foreach (
            $prenotazioni
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

        /*
         * Finché mancano più di 48 ore,
         * la richiesta resta attiva.
         */
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

        /*
         * Raggiunto il limite delle
         * 48 ore, la richiesta scade.
         *
         * L'eventuale prenotazione
         * alternativa NON viene toccata.
         */
        $richiesta->scadi();

        $aggiornata =
            FListaAttesa::update(
                $richiesta
            );

        if (!$aggiornata) {
            return false;
        }

        return true;
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

        $secondiRimanenti =
            $dataOra - time();

        $quarantottoOre =
            48 * 60 * 60;

        return
            $secondiRimanenti
            > $quarantottoOre;
    }

    private function intervalloDentroDisponibilita(
        string $data,
        string $oraInizio,
        int $durata
    ): bool {
        $fasce =
            FDisponibilita::loadByData(
                $data
            );

        $inizioRichiesto =
            $this->oraInMinuti(
                $oraInizio
            );

        $fineRichiesta =
            $inizioRichiesto
            + $durata;

        foreach ($fasce as $fascia) {
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

    private function orarioSuGrigliaQuindiciMinuti(
        string $ora
    ): bool {
        $minuti =
            $this->oraInMinuti(
                $ora
            );

        return
            $minuti % 15 === 0;
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
            (int) $parti[0];

        $minuti =
            (int) $parti[1];

        return sprintf(
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
            (int) $parti[0];

        $minuti =
            (int) $parti[1];

        return ($ore * 60)
            + $minuti;
    }
}
