<?php

require_once __DIR__ . '/../Foundation/FConnectionDB.class.php';
require_once __DIR__ . '/../Foundation/FUtente.class.php';
require_once __DIR__ . '/../Foundation/FServizio.class.php';
require_once __DIR__ . '/../Foundation/FDisponibilita.class.php';
require_once __DIR__ . '/../Foundation/FPrenotazione.class.php';
require_once __DIR__ . '/../Entity/EPrenotazione.class.php';

class CGestionePrenotazione
{
    public function serviziPerCategoria(
        string $categoria
    ): array {
        return FServizio::loadAttiviByCategoria(
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

        $risultato = [];

        foreach (
            $fasceDisponibilita
            as $fascia
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
                $blocchiLiberi
                as $blocco
            ) {
                $inizioBlocco =
                    $blocco['inizio'];

                $fineBlocco =
                    $blocco['fine'];

                for (
                    $inizio = $inizioBlocco;
                    $inizio + $durata
                        <= $fineBlocco;
                    $inizio += $durata
                ) {
                    $oraInizio =
                        $this->minutiInOra(
                            $inizio
                        );

                    $oraFine =
                        $this->minutiInOra(
                            $inizio
                                + $durata
                        );

                    if (
                        !isset(
                            $risultato[$data]
                        )
                    ) {
                        $risultato[$data] = [];
                    }

                    $risultato[$data][] = [
                        'oraInizio'
                        => $oraInizio,
                        'oraFine'
                        => $oraFine
                    ];
                }
            }
        }

        return $risultato;
    }

    public function orarioDisponibile(
        int $idServizio,
        string $data,
        string $oraInizio
    ): bool {
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
            $fasceDisponibilita
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

            $blocchiLiberi =
                $this->calcolaBlocchiLiberi(
                    $inizioFascia,
                    $fineFascia,
                    $prenotazioni
                );

            foreach (
                $blocchiLiberi
                as $blocco
            ) {
                for (
                    $inizio =
                        $blocco['inizio'];
                    $inizio + $durata
                        <= $blocco['fine'];
                    $inizio += $durata
                ) {
                    if (
                        $inizio
                        === $inizioRichiesto
                    ) {
                        return true;
                    }
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

        /*
         * REGOLA HAIRBOOKING:
         * massimo una prenotazione
         * attiva per cliente.
         */
        $prenotazioniCliente =
            FPrenotazione::loadByCliente(
                $idCliente
            );

        foreach (
            $prenotazioniCliente
            as $prenotazioneCliente
        ) {
            if (
                $prenotazioneCliente
                ->getStato()
                === 'prenotata'
            ) {
                return null;
            }
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



        $oraNormalizzata =
            $this->minutiInOra(
                $this->oraInMinuti(
                    $oraInizio
                )
            );

        $connection =
            FConnectionDB::connect();

        $connection->beginTransaction();

        try {
            if (
                !$this->orarioDisponibile(
                    $idServizio,
                    $data,
                    $oraNormalizzata
                )
            ) {
                $connection->rollBack();
                return null;
            }

            if (
                FPrenotazione::esisteSovrapposizione(
                    $data,
                    $oraNormalizzata
                        . ':00',
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
                    $oraNormalizzata
                        . ':00'
                );

            $prenotazioneSalvata =
                FPrenotazione::store(
                    $prenotazione
                );

            if (!$prenotazioneSalvata) {
                $connection->rollBack();
                return null;
            }

            $connection->commit();

            // TODO email di conferma prenotazione, soltanto dopo commit.
            return $prenotazione;
        } catch (Throwable $e) {
            $connection->rollBack();

            return null;
        }
    }

    private function calcolaBlocchiLiberi(
        int $inizioFascia,
        int $fineFascia,
        array $prenotazioni
    ): array {
        $blocchiLiberi = [];

        $cursore =
            $inizioFascia;

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

            $inizioPrenotazione =
                $this->oraInMinuti(
                    $prenotazione
                        ->getOra()
                );

            $finePrenotazione =
                $inizioPrenotazione
                + $prenotazione
                ->getDurata();

            if (
                $finePrenotazione
                <= $inizioFascia
                ||
                $inizioPrenotazione
                >= $fineFascia
            ) {
                continue;
            }

            if (
                $inizioPrenotazione
                > $cursore
            ) {
                $fineBlocco =
                    $inizioPrenotazione;

                if (
                    $fineBlocco
                    > $fineFascia
                ) {
                    $fineBlocco =
                        $fineFascia;
                }

                if (
                    $cursore
                    < $fineBlocco
                ) {
                    $blocchiLiberi[] = [
                        'inizio'
                        => $cursore,
                        'fine'
                        => $fineBlocco
                    ];
                }
            }

            if (
                $finePrenotazione
                > $cursore
            ) {
                $cursore =
                    $finePrenotazione;
            }

            if (
                $cursore
                < $inizioFascia
            ) {
                $cursore =
                    $inizioFascia;
            }

            if (
                $cursore
                >= $fineFascia
            ) {
                break;
            }
        }

        if (
            $cursore
            < $fineFascia
        ) {
            $blocchiLiberi[] = [
                'inizio'
                => $cursore,
                'fine'
                => $fineFascia
            ];
        }

        return $blocchiLiberi;
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

    private function minutiInOra(
        int $minutiTotali
    ): string {
        $ore =
            intdiv(
                $minutiTotali,
                60
            );

        $minuti =
            $minutiTotali % 60;

        return sprintf(
            '%02d:%02d',
            $ore,
            $minuti
        );
    }
}
