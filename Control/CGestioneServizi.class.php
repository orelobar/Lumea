<?php

require_once __DIR__ . '/../Foundation/FUtente.class.php';
require_once __DIR__ . '/../Foundation/FSessione.class.php';
require_once __DIR__ . '/../Foundation/FServizio.class.php';
require_once __DIR__ . '/../Entity/EServizio.class.php';

class CGestioneServizi
{
    public function elencoServizi(): array
    {
        if (!$this->isProprietarioAutenticato()) {
            return [];
        }

        /*
         * Nell'area del proprietario
         * mostriamo sia i servizi attivi
         * sia quelli disattivati.
         */
        return FServizio::loadAll();
    }

    public function aggiungiServizio(
        string $nome,
        string $categoria,
        float $prezzo,
        int $durata
    ): ?EServizio {
        if (!$this->isProprietarioAutenticato()) {
            return null;
        }

        /*
         * HairBooking prevede attualmente
         * solamente le categorie:
         *
         * - Uomo
         * - Donna
         */
        if (
            !$this->categoriaValida(
                $categoria
            )
        ) {
            return null;
        }

        try {
            /*
             * Il costruttore di EServizio
             * controlla anche la durata:
             *
             * durata > 0
             * durata multipla di 15.
             */
            $servizio =
                new EServizio(
                    $nome,
                    $categoria,
                    $prezzo,
                    $durata
                );

            $salvato =
                FServizio::store(
                    $servizio
                );

            if (!$salvato) {
                return null;
            }

            return $servizio;
        } catch (InvalidArgumentException $e) {
            /*
             * Per esempio:
             * durata 22 minuti.
             */
            return null;
        }
    }

    public function modificaServizio(
        int $idServizio,
        string $nome,
        string $categoria,
        float $prezzo,
        int $durata
    ): bool {
        if (!$this->isProprietarioAutenticato()) {
            return false;
        }

        $servizio =
            FServizio::load(
                $idServizio
            );

        if ($servizio === null) {
            return false;
        }

        if (
            !$this->categoriaValida(
                $categoria
            )
        ) {
            return false;
        }

        try {
            /*
             * Modifichiamo l'Entity
             * solamente in memoria.
             */
            $servizio->setNome(
                $nome
            );

            $servizio->setCategoria(
                $categoria
            );

            $servizio->setPrezzo(
                $prezzo
            );

            $servizio->setDurata(
                $durata
            );

            /*
             * Poi rendiamo persistenti
             * le modifiche.
             */
            return FServizio::update(
                $servizio
            );
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    public function disattivaServizio(
        int $idServizio
    ): bool {
        if (!$this->isProprietarioAutenticato()) {
            return false;
        }

        $servizio =
            FServizio::load(
                $idServizio
            );

        if ($servizio === null) {
            return false;
        }

        /*
         * Non cancelliamo fisicamente
         * il servizio.
         *
         * Lo rendiamo semplicemente
         * non prenotabile.
         */
        $servizio->disattiva();

        return FServizio::update(
            $servizio
        );
    }

    public function riattivaServizio(
        int $idServizio
    ): bool {
        if (!$this->isProprietarioAutenticato()) {
            return false;
        }

        $servizio =
            FServizio::load(
                $idServizio
            );

        if ($servizio === null) {
            return false;
        }

        $servizio->attiva();

        return FServizio::update(
            $servizio
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

    private function categoriaValida(
        string $categoria
    ): bool {
        return in_array(
            $categoria,
            [
                'Uomo',
                'Donna'
            ],
            true
        );
    }
}
