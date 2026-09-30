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

    public function completaAppuntamento(
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

        if (
            !$this->orarioAppuntamentoRaggiunto(
                $prenotazione
            )
        ) {
            return false;
        }

        $cliente =
            $prenotazione->getCliente();

        $coupon =
            $prenotazione
            ->getCouponApplicato();

        $connection =
            FConnectionDB::connect();

        $connection->beginTransaction();

        try {
            $prenotazione->completa();

            if (
                !FPrenotazione::update(
                    $prenotazione
                )
            ) {
                throw new Exception(
                    'Errore aggiornamento prenotazione.'
                );
            }

            if (
                !FUtente::update(
                    $cliente
                )
            ) {
                throw new Exception(
                    'Errore aggiornamento cliente.'
                );
            }

            if ($coupon !== null) {
                if (
                    !FCoupon::update(
                        $coupon
                    )
                ) {
                    throw new Exception(
                        'Errore aggiornamento coupon.'
                    );
                }
            }

            /*
             * Il pagamento è avvenuto
             * fisicamente in salone.
             *
             * HairBooking registra
             * soltanto l'incasso reale.
             */
            $pagamento =
                new EPagamento(
                    $prenotazione,
                    $prenotazione
                        ->getImportoDaPagare(),
                    date('Y-m-d H:i:s')
                );

            if (
                !FPagamento::store(
                    $pagamento
                )
            ) {
                throw new Exception(
                    'Errore registrazione pagamento.'
                );
            }

            $numeroCompletamenti =
                FPrenotazione
                ::countCompletateByCliente(
                    $cliente->getId()
                );

            $this
                ->generaCouponFedeltaSeSpetta(
                    $cliente,
                    $numeroCompletamenti
                );

            $connection->commit();

            /*
             * In futuro:
             * eventuale notifica per
             * nuovo coupon fedeltà.
             */

            return true;
        } catch (Exception $e) {
            $connection->rollBack();

            return false;
        }
    }

    public function segnaNoShow(
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

        if (
            !$this->orarioAppuntamentoRaggiunto(
                $prenotazione
            )
        ) {
            return false;
        }

        $cliente =
            $prenotazione->getCliente();

        $noShowPrima =
            $cliente
            ->getNoShowConsecutivi();

        $couponPrenotazione =
            $prenotazione
            ->getCouponApplicato();

        $connection =
            FConnectionDB::connect();

        $connection->beginTransaction();

        try {
            $prenotazione->segnaNoShow();

            if (
                !FPrenotazione::update(
                    $prenotazione
                )
            ) {
                throw new Exception(
                    'Errore aggiornamento prenotazione.'
                );
            }

            if (
                !FUtente::update(
                    $cliente
                )
            ) {
                throw new Exception(
                    'Errore aggiornamento cliente.'
                );
            }

            if (
                $couponPrenotazione
                !== null
            ) {
                if (
                    !FCoupon::update(
                        $couponPrenotazione
                    )
                ) {
                    throw new Exception(
                        'Errore aggiornamento coupon.'
                    );
                }
            }

            $noShowDopo =
                $cliente
                ->getNoShowConsecutivi();

            /*
             * Penalità soltanto nel
             * passaggio esatto 2 -> 3.
             */
            if (
                $noShowPrima === 2
                &&
                $noShowDopo === 3
            ) {
                $couponDisponibili =
                    FCoupon
                    ::loadDisponibiliByCliente(
                        $cliente->getId()
                    );

                foreach (
                    $couponDisponibili
                    as $coupon
                ) {
                    $coupon
                        ->dimezzaCreditoResiduo();

                    if (
                        !FCoupon::update(
                            $coupon
                        )
                    ) {
                        throw new Exception(
                            'Errore applicazione penalità coupon.'
                        );
                    }
                }
            }

            $connection->commit();

            return true;
        } catch (Exception $e) {
            $connection->rollBack();

            return false;
        }
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
        $coupon =
            $prenotazione
            ->getCouponApplicato();

        $connection =
            FConnectionDB::connect();

        $connection->beginTransaction();

        try {
            /*
             * cancella():
             *
             * - libera eventuale coupon;
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

            if ($coupon !== null) {
                if (
                    !FCoupon::update(
                        $coupon
                    )
                ) {
                    throw new Exception(
                        'Errore restituzione coupon.'
                    );
                }
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
             * QUI dovrà partire la notifica
             * al cliente:
             *
             * "Il salone ha annullato il tuo
             * appuntamento. Sei già stato
             * contattato dal parrucchiere."
             */

            return true;
        } catch (Exception $e) {
            $connection->rollBack();

            return false;
        }
    }

    private function generaCouponFedeltaSeSpetta(
        EUtente $cliente,
        int $numeroCompletamenti
    ): void {
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
            return;
        }

        if (
            FCoupon
            ::existByClienteAndTraguardo(
                $cliente->getId(),
                $numeroCompletamenti
            )
        ) {
            return;
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

        /*
         * In futuro:
         * notifica nuovo coupon.
         */
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
