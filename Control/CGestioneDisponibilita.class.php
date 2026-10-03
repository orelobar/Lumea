<?php

require_once __DIR__ . '/../Foundation/FUtente.class.php';
require_once __DIR__ . '/../Foundation/FSessione.class.php';
require_once __DIR__ . '/../Foundation/FDisponibilita.class.php';
require_once __DIR__ . '/../Foundation/FPrenotazione.class.php';

require_once __DIR__ . '/../Entity/EDisponibilita.class.php';
require_once __DIR__ . '/../Entity/EPrenotazione.class.php';

class CGestioneDisponibilita
{
    public function fascePerData(
        string $data
    ): array {
        if (!$this->isProprietarioAutenticato()) {
            return [];
        }

        return FDisponibilita::loadByData(
            $data
        );
    }

    public function aggiungiFascia(
        string $data,
        string $oraInizio,
        string $oraFine
    ): ?EDisponibilita {
        if (!$this->isProprietarioAutenticato()) {
            return null;
        }

        $oraInizioNormalizzata =
            $this->normalizzaOra(
                $oraInizio
            );

        $oraFineNormalizzata =
            $this->normalizzaOra(
                $oraFine
            );

        if (
            $oraInizioNormalizzata === null
            ||
            $oraFineNormalizzata === null
        ) {
            return null;
        }

        if (
            $this->oraInMinuti(
                $oraInizioNormalizzata
            )
            >=
            $this->oraInMinuti(
                $oraFineNormalizzata
            )
        ) {
            return null;
        }

        /*
         * Non consentiamo due fasce
         * sovrapposte nella stessa giornata.
         *
         * Sono invece consentite fasce
         * adiacenti:
         *
         * 09:00-13:00
         * 13:00-17:00
         */
        if (
            $this->esisteSovrapposizioneFasce(
                $data,
                $oraInizioNormalizzata,
                $oraFineNormalizzata
            )
        ) {
            return null;
        }

        $disponibilita =
            new EDisponibilita(
                $data,
                $oraInizioNormalizzata,
                $oraFineNormalizzata
            );

        if (
            !FDisponibilita::store(
                $disponibilita
            )
        ) {
            return null;
        }

        return $disponibilita;
    }

    public function prenotazioniCoinvolteModifica(
        int $idDisponibilita,
        string $nuovaData,
        string $nuovaOraInizio,
        string $nuovaOraFine
    ): array {
        if (!$this->isProprietarioAutenticato()) {
            return [];
        }

        $fascia =
            FDisponibilita::load(
                $idDisponibilita
            );

        if ($fascia === null) {
            return [];
        }

        $oraInizioNormalizzata =
            $this->normalizzaOra(
                $nuovaOraInizio
            );

        $oraFineNormalizzata =
            $this->normalizzaOra(
                $nuovaOraFine
            );

        if (
            $oraInizioNormalizzata === null
            ||
            $oraFineNormalizzata === null
        ) {
            return [];
        }

        /*
         * Ci interessano le prenotazioni
         * della data ORIGINALE.
         *
         * Sono quelle che potrebbero
         * perdere la copertura se la
         * fascia viene spostata o ridotta.
         */
        $dataOriginale =
            $fascia->getData();

        $prenotazioni =
            FPrenotazione::loadByData(
                $dataOriginale
            );

        /*
         * Costruiamo le fasce che
         * esisterebbero DOPO la modifica.
         */
        $fasceRisultanti = [];

        $fasceOriginali =
            FDisponibilita::loadByData(
                $dataOriginale
            );

        foreach (
            $fasceOriginali
            as $fasciaEsistente
        ) {
            if (
                $fasciaEsistente->getId()
                === $idDisponibilita
            ) {
                continue;
            }

            $fasceRisultanti[] = [
                'inizio'
                => $fasciaEsistente
                    ->getOraInizio(),
                'fine'
                => $fasciaEsistente
                    ->getOraFine()
            ];
        }

        /*
         * Se la fascia rimane nello stesso
         * giorno, aggiungiamo alla
         * simulazione la sua nuova forma.
         *
         * Se viene spostata a un'altra
         * data, nella vecchia data
         * semplicemente non esisterà più.
         */
        if (
            $nuovaData
            === $dataOriginale
        ) {
            $fasceRisultanti[] = [
                'inizio'
                => $oraInizioNormalizzata,
                'fine'
                => $oraFineNormalizzata
            ];
        }

        $coinvolte = [];

        foreach (
            $prenotazioni
            as $prenotazione
        ) {
            /*
             * Solo gli appuntamenti ancora
             * attivi devono impedire
             * la modifica.
             */
            if (
                $prenotazione->getStato()
                !== 'prenotata'
            ) {
                continue;
            }

            if (
                !$this
                    ->prenotazioneCopertaDaFasce(
                        $prenotazione,
                        $fasceRisultanti
                    )
            ) {
                $coinvolte[] =
                    $prenotazione;
            }
        }

        return $coinvolte;
    }

    public function modificaFascia(
        int $idDisponibilita,
        string $data,
        string $oraInizio,
        string $oraFine
    ): bool {
        if (!$this->isProprietarioAutenticato()) {
            return false;
        }

        $fascia =
            FDisponibilita::load(
                $idDisponibilita
            );

        if ($fascia === null) {
            return false;
        }

        $oraInizioNormalizzata =
            $this->normalizzaOra(
                $oraInizio
            );

        $oraFineNormalizzata =
            $this->normalizzaOra(
                $oraFine
            );

        if (
            $oraInizioNormalizzata === null
            ||
            $oraFineNormalizzata === null
        ) {
            return false;
        }

        if (
            $this->oraInMinuti(
                $oraInizioNormalizzata
            )
            >=
            $this->oraInMinuti(
                $oraFineNormalizzata
            )
        ) {
            return false;
        }

        /*
         * Controlliamo che la nuova fascia
         * non si sovrapponga alle altre.
         *
         * Escludiamo dal controllo
         * proprio quella che stiamo
         * modificando.
         */
        if (
            $this->esisteSovrapposizioneFasce(
                $data,
                $oraInizioNormalizzata,
                $oraFineNormalizzata,
                $idDisponibilita
            )
        ) {
            return false;
        }

        /*
         * Se la modifica lascerebbe
         * appuntamenti attivi fuori
         * dall'orario di apertura,
         * NON viene effettuata.
         */
        $prenotazioniCoinvolte =
            $this->prenotazioniCoinvolteModifica(
                $idDisponibilita,
                $data,
                $oraInizioNormalizzata,
                $oraFineNormalizzata
            );

        if (
            count($prenotazioniCoinvolte)
            > 0
        ) {
            return false;
        }

        $fascia->modifica(
            $data,
            $oraInizioNormalizzata,
            $oraFineNormalizzata
        );

        return FDisponibilita::update(
            $fascia
        );
    }

    public function prenotazioniCoinvolteEliminazione(
        int $idDisponibilita
    ): array {
        if (!$this->isProprietarioAutenticato()) {
            return [];
        }

        $fascia =
            FDisponibilita::load(
                $idDisponibilita
            );

        if ($fascia === null) {
            return [];
        }

        $data =
            $fascia->getData();

        $prenotazioni =
            FPrenotazione::loadByData(
                $data
            );

        /*
         * Simuliamo la giornata dopo
         * l'eliminazione della fascia.
         */
        $fasceRisultanti = [];

        $fasce =
            FDisponibilita::loadByData(
                $data
            );

        foreach ($fasce as $fasciaEsistente) {
            if (
                $fasciaEsistente->getId()
                === $idDisponibilita
            ) {
                continue;
            }

            $fasceRisultanti[] = [
                'inizio'
                => $fasciaEsistente
                    ->getOraInizio(),
                'fine'
                => $fasciaEsistente
                    ->getOraFine()
            ];
        }

        $coinvolte = [];

        foreach (
            $prenotazioni
            as $prenotazione
        ) {
            if (
                $prenotazione->getStato()
                !== 'prenotata'
            ) {
                continue;
            }

            if (
                !$this
                    ->prenotazioneCopertaDaFasce(
                        $prenotazione,
                        $fasceRisultanti
                    )
            ) {
                $coinvolte[] =
                    $prenotazione;
            }
        }

        return $coinvolte;
    }

    public function eliminaFascia(
        int $idDisponibilita
    ): bool {
        if (!$this->isProprietarioAutenticato()) {
            return false;
        }

        $fascia =
            FDisponibilita::load(
                $idDisponibilita
            );

        if ($fascia === null) {
            return false;
        }

        /*
         * Prima controlliamo se esistono
         * clienti già prenotati che
         * resterebbero senza copertura.
         */
        $prenotazioniCoinvolte =
            $this
            ->prenotazioniCoinvolteEliminazione(
                $idDisponibilita
            );

        if (
            count($prenotazioniCoinvolte)
            > 0
        ) {
            return false;
        }

        return FDisponibilita::delete(
            $idDisponibilita
        );
    }

    private function isProprietarioAutenticato(): bool
    {
        $idUtente = FSessione::getIdUtenteAutenticato();

        if ($idUtente === null) {
            return false;
        }

        $utente = FUtente::load($idUtente);

        return $utente !== null && $utente->isProprietario();
    }

    private function esisteSovrapposizioneFasce(
        string $data,
        string $oraInizio,
        string $oraFine,
        ?int $idDaEscludere = null
    ): bool {
        $fasce =
            FDisponibilita::loadByData(
                $data
            );

        $nuovoInizio =
            $this->oraInMinuti(
                $oraInizio
            );

        $nuovaFine =
            $this->oraInMinuti(
                $oraFine
            );

        foreach ($fasce as $fascia) {
            if (
                $idDaEscludere !== null
                &&
                $fascia->getId()
                === $idDaEscludere
            ) {
                continue;
            }

            $inizioEsistente =
                $this->oraInMinuti(
                    $fascia->getOraInizio()
                );

            $fineEsistente =
                $this->oraInMinuti(
                    $fascia->getOraFine()
                );

            /*
             * Stessa formula di overlap:
             *
             * nuovo inizio < fine esistente
             * AND
             * nuova fine > inizio esistente
             */
            if (
                $nuovoInizio
                < $fineEsistente
                &&
                $nuovaFine
                > $inizioEsistente
            ) {
                return true;
            }
        }

        return false;
    }

    private function prenotazioneCopertaDaFasce(
        EPrenotazione $prenotazione,
        array $fasce
    ): bool {
        $inizioPrenotazione =
            $this->oraInMinuti(
                $prenotazione->getOra()
            );

        $finePrenotazione =
            $inizioPrenotazione
            + $prenotazione->getDurata();

        foreach ($fasce as $fascia) {
            $inizioFascia =
                $this->oraInMinuti(
                    $fascia['inizio']
                );

            $fineFascia =
                $this->oraInMinuti(
                    $fascia['fine']
                );

            if (
                $inizioPrenotazione
                >= $inizioFascia
                &&
                $finePrenotazione
                <= $fineFascia
            ) {
                return true;
            }
        }

        return false;
    }

    private function normalizzaOra(
        string $ora
    ): ?string {
        $parti =
            explode(
                ':',
                $ora
            );

        if (
            count($parti) < 2
        ) {
            return null;
        }

        if (
            !is_numeric($parti[0])
            ||
            !is_numeric($parti[1])
        ) {
            return null;
        }

        $ore =
            (int) $parti[0];

        $minuti =
            (int) $parti[1];

        if (
            $ore < 0
            ||
            $ore > 23
            ||
            $minuti < 0
            ||
            $minuti > 59
        ) {
            return null;
        }

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

        return ((int) $parti[0] * 60)
            +
            (int) $parti[1];
    }
}
