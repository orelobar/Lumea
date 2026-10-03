<?php

require_once __DIR__ . '/../Foundation/FSessione.class.php';
require_once __DIR__ . '/../Foundation/FUtente.class.php';
require_once __DIR__ . '/../Foundation/FPrenotazione.class.php';
require_once __DIR__ . '/../Foundation/FPagamento.class.php';
require_once __DIR__ . '/../Entity/EUtente.class.php';

class CGestioneDashboard
{
    public function dashboardProprietario(): ?array
    {
        $utente =
            $this->getUtenteAutenticato();

        if (
            $utente === null
            ||
            !$utente->isProprietario()
        ) {
            return null;
        }

        $oggi =
            date('Y-m-d');

        $adesso =
            time();

        $prenotazioni =
            FPrenotazione::loadByData(
                $oggi
            );

        $appuntamentiOggi = [];
        $prossimiAppuntamenti = [];

        $incassoPrevistoCentesimi = 0;
        $incassoEffettivoCentesimi = 0;

        foreach (
            $prenotazioni
            as $prenotazione
        ) {
            $stato =
                $prenotazione->getStato();

            if ($stato === 'cancellata') {
                continue;
            }

            $appuntamentiOggi[] =
                $prenotazione;

            $incassoPrevistoCentesimi +=
                (int) round(
                    $prenotazione->getPrezzo()
                        * 100
                );

            if ($stato === 'prenotata') {
                $dataOraPrenotazione =
                    strtotime(
                        $prenotazione->getData()
                            . ' '
                            . $prenotazione->getOra()
                    );

                if (
                    $dataOraPrenotazione !== false
                    &&
                    $dataOraPrenotazione >= $adesso
                ) {
                    $prossimiAppuntamenti[] =
                        $prenotazione;
                }
            }

            if ($stato === 'completata') {
                $idPrenotazione =
                    $prenotazione->getId();

                if ($idPrenotazione === null) {
                    continue;
                }

                $pagamento =
                    FPagamento::loadByPrenotazione(
                        $idPrenotazione
                    );

                if ($pagamento !== null) {
                    $incassoEffettivoCentesimi +=
                        (int) round(
                            $pagamento
                                ->getImportoPagato()
                                * 100
                        );
                }
            }
        }

        return [
            'data' =>
            $oggi,

            'appuntamenti_oggi' =>
            $appuntamentiOggi,

            'numero_appuntamenti_oggi' =>
            count($appuntamentiOggi),

            'prossimi_appuntamenti' =>
            $prossimiAppuntamenti,

            'prossimo_appuntamento' =>
            $prossimiAppuntamenti[0]
                ?? null,

            'incasso_previsto_oggi' =>
            round(
                $incassoPrevistoCentesimi
                    / 100,
                2
            ),

            'nota_incasso_previsto' =>
            'Importo previsto prima di eventuali coupon e no-show.',

            'incasso_effettivo_oggi' =>
            round(
                $incassoEffettivoCentesimi
                    / 100,
                2
            )
        ];
    }

    private function getUtenteAutenticato(): ?EUtente
    {
        $idUtente =
            FSessione::getIdUtenteAutenticato();

        if ($idUtente === null) {
            return null;
        }

        return FUtente::load(
            $idUtente
        );
    }
}
