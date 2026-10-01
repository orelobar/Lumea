<?php

require_once __DIR__ . '/../Foundation/FConnectionDB.class.php';
require_once __DIR__ . '/../Foundation/FPrenotazione.class.php';
require_once __DIR__ . '/../Foundation/FPagamento.class.php';
require_once __DIR__ . '/../Foundation/FUtente.class.php';
require_once __DIR__ . '/../Foundation/FCoupon.class.php';

require_once __DIR__ . '/../Entity/EPrenotazione.class.php';
require_once __DIR__ . '/../Entity/EPagamento.class.php';
require_once __DIR__ . '/../Entity/ECoupon.class.php';
require_once __DIR__ . '/../Entity/EUtente.class.php';

class CGestioneAgenda
{
    public function agendaPerData(
        string $data
    ): array {
        $prenotazioni =
            FPrenotazione::loadByData(
                $data
            );

        $agenda = [];

        foreach (
            $prenotazioni
            as $prenotazione
        ) {
            if (
                $prenotazione->getStato()
                !== 'cancellata'
            ) {
                $agenda[] =
                    $prenotazione;
            }
        }

        return $agenda;
    }

    public function dettaglioAppuntamento(
        int $idPrenotazione
    ): ?EPrenotazione {
        return FPrenotazione::load(
            $idPrenotazione
        );
    }

    public function storicoPagamenti(string $dataInizio, string $dataFine): array
    {
        return FPagamento::loadByPeriodo($dataInizio, $dataFine);
    }

    // Verifica pura: mostra anche coupon esauriti, senza modificare il credito.
    public function verificaCoupon(string $codice): ?ECoupon
    {
        return FCoupon::loadByCodice($codice);
    }

    /** Riepilogo prima della conferma; la chiusura rilegge e rivalida tutto. */
    public function riepilogoPagamento(
        int $idPrenotazione,
        ?string $codiceCoupon = null
    ): ?array {
        $prenotazione = FPrenotazione::load($idPrenotazione);
        if (!$this->appuntamentoChiudibile($prenotazione)) {
            return null;
        }

        return $this->calcolaPagamento($prenotazione, $codiceCoupon);
    }

    public function chiudiAppuntamento(
        int $idPrenotazione,
        ?string $codiceCoupon = null
    ): bool {
        $connection = FConnectionDB::connect();
        $connection->beginTransaction();

        try {
            // Blocca appuntamento e cliente prima di leggere stato e contatori.
            $prenotazione = FPrenotazione::loadPerAggiornamento($idPrenotazione);
            if (!$this->appuntamentoChiudibile($prenotazione)) {
                $connection->rollBack();
                return false;
            }

            $dati = $this->calcolaPagamento($prenotazione, $codiceCoupon, true);
            if ($dati === null) {
                $connection->rollBack();
                return false;
            }

            $cliente = $prenotazione->getCliente();
            $coupon = $dati['coupon'];
            if ($coupon !== null && $dati['importoCouponUtilizzato'] > 0) {
                $coupon->utilizza($dati['importoCouponUtilizzato']);
                if (!FCoupon::update($coupon)) {
                    throw new RuntimeException('Errore aggiornamento coupon.');
                }
            }

            $pagamento = new EPagamento(
                $prenotazione,
                $dati['importoPagato'],
                date('Y-m-d H:i:s'),
                null,
                $coupon,
                $dati['importoCouponUtilizzato']
            );
            if (!FPagamento::store($pagamento)) {
                throw new RuntimeException('Errore registrazione pagamento.');
            }

            $prenotazione->completa();
            if (!FPrenotazione::update($prenotazione) || !FUtente::update($cliente)) {
                throw new RuntimeException('Errore completamento appuntamento.');
            }

            // Solo dopo pagamento e completamento: il premio non è retroattivo.
            $nuovoCoupon = $this->generaCouponFedeltaSeSpetta(
                $cliente,
                FPrenotazione::countCompletateByCliente($cliente->getId())
            );
            $connection->commit();
        } catch (Throwable $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            return false;
        }

        // TODO email, soltanto dopo commit, se $nuovoCoupon !== null:
        // premio/traguardo, valore, codice, visibile nell'account e usabile in salone.
        return true;
    }

    public function segnaNoShow(int $idPrenotazione): bool
    {
        $connection = FConnectionDB::connect();
        $connection->beginTransaction();

        try {
            $prenotazione = FPrenotazione::loadPerAggiornamento($idPrenotazione);
            if (!$this->appuntamentoChiudibile($prenotazione)) {
                $connection->rollBack();
                return false;
            }

            $cliente = $prenotazione->getCliente();
            $noShowPrima = $cliente->getNoShowConsecutivi();
            $prenotazione->segnaNoShow();
            $noShowDopo = $cliente->getNoShowConsecutivi();

            if (!FPrenotazione::update($prenotazione) || !FUtente::update($cliente)) {
                throw new RuntimeException('Errore registrazione no-show.');
            }

            $terzoNoShow = $noShowPrima === 2 && $noShowDopo === 3;
            if ($terzoNoShow) {
                foreach (FCoupon::loadDisponibiliByCliente($cliente->getId(), true) as $coupon) {
                    $coupon->dimezzaCreditoResiduo();
                    if (!FCoupon::update($coupon)) {
                        throw new RuntimeException('Errore applicazione penalità coupon.');
                    }
                }
            }
            $connection->commit();
        } catch (Throwable $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            return false;
        }

        // TODO email dopo commit solo se $terzoNoShow: sospensione e penalità credito.
        return true;
    }

    private function appuntamentoChiudibile(?EPrenotazione $prenotazione): bool
    {
        return $prenotazione !== null
            && $prenotazione->getStato() === 'prenotata'
            && $this->orarioAppuntamentoRaggiunto($prenotazione);
    }

    private function calcolaPagamento(
        EPrenotazione $prenotazione,
        ?string $codiceCoupon,
        bool $perAggiornamento = false
    ): ?array {
        $coupon = null;
        $creditoUsato = 0.0;
        if ($codiceCoupon !== null) {
            $coupon = FCoupon::loadByCodice($codiceCoupon, $perAggiornamento);
            if ($coupon === null
                || !$coupon->appartieneA($prenotazione->getCliente())
                || !$coupon->isDisponibile()
                || $coupon->getCreditoResiduo() <= 0
            ) {
                return null;
            }
            $creditoUsato = min($prenotazione->getPrezzo(), $coupon->getCreditoResiduo());
        }

        return [
            'prezzo' => $prenotazione->getPrezzo(),
            'coupon' => $coupon,
            'importoCouponUtilizzato' => $creditoUsato,
            'importoPagato' => round($prenotazione->getPrezzo() - $creditoUsato, 2)
        ];
    }

    public function annullaAppuntamentoPerChiusura(
        int $idPrenotazione
    ): bool {
        $prenotazione =
            FPrenotazione::load(
                $idPrenotazione
            );

        if ($prenotazione === null) {
            return false;
        }

        if (
            $prenotazione->getStato()
            !== 'prenotata'
        ) {
            return false;
        }

        /*
         * Nessuna regola delle 48 ore.
         *
         * Questa è una cancellazione
         * effettuata dal proprietario
         * per necessità del salone.
         */
        $connection =
            FConnectionDB::connect();

        $connection->beginTransaction();

        try {
            // Una cancellazione del proprietario può concorrere con la chiusura.
            $prenotazione = FPrenotazione::loadPerAggiornamento($idPrenotazione);
            if ($prenotazione === null || $prenotazione->getStato() !== 'prenotata') {
                $connection->rollBack();
                return false;
            }

            /*
             * cancella():
             *
             * - stato -> cancellata.
             */
            $prenotazione->cancella();

            if (
                !FPrenotazione::update(
                    $prenotazione
                )
            ) {
                throw new Exception(
                    'Errore cancellazione prenotazione.'
                );
            }

            /*
             * IMPORTANTE:
             *
             * non richiamiamo nessuna
             * gestione FIFO.
             *
             * Il posto non è diventato
             * disponibile: il salone
             * sta chiudendo quella fascia.
             */
            $connection->commit();

            /*
             * TODO email dopo commit
             * al cliente:
             *
             * "Il salone ha annullato il tuo
             * appuntamento. Sei già stato
             * contattato dal parrucchiere."
             */

            return true;
        } catch (Throwable $e) {
            $connection->rollBack();

            return false;
        }
    }

    private function generaCouponFedeltaSeSpetta(
        EUtente $cliente,
        int $numeroCompletamenti
    ): ?ECoupon {
        $valoreCoupon = null;

        $posizioneNelCiclo =
            $numeroCompletamenti % 60;

        if (
            $posizioneNelCiclo === 10
        ) {
            $valoreCoupon = 10.0;
        } elseif (
            $posizioneNelCiclo === 30
        ) {
            $valoreCoupon = 20.0;
        } elseif (
            $posizioneNelCiclo === 0
            &&
            $numeroCompletamenti > 0
        ) {
            $valoreCoupon = 30.0;
        }

        if ($valoreCoupon === null) {
            return null;
        }

        if (
            FCoupon
            ::existByClienteAndTraguardo(
                $cliente->getId(),
                $numeroCompletamenti
            )
        ) {
            return null;
        }

        $codice =
            'HB-'
            . $cliente->getId()
            . '-'
            . $numeroCompletamenti;

        $coupon =
            new ECoupon(
                $codice,
                $cliente,
                $valoreCoupon,
                date('Y-m-d H:i:s'),
                $numeroCompletamenti
            );

        if (
            !FCoupon::store(
                $coupon
            )
        ) {
            throw new Exception(
                'Errore generazione coupon fedeltà.'
            );
        }

        return $coupon;
    }

    private function orarioAppuntamentoRaggiunto(
        EPrenotazione $prenotazione
    ): bool {
        $dataOraAppuntamento =
            strtotime(
                $prenotazione->getData()
                    . ' '
                    . $prenotazione->getOra()
            );

        if (
            $dataOraAppuntamento === false
        ) {
            return false;
        }

        return
            $dataOraAppuntamento
            <= time();
    }
}
