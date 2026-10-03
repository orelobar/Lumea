<?php

require_once __DIR__ . '/../Foundation/FSessione.class.php';
require_once __DIR__ . '/../Foundation/FUtente.class.php';
require_once __DIR__ . '/../Foundation/FPagamento.class.php';
require_once __DIR__ . '/../Foundation/FPrenotazione.class.php';
require_once __DIR__ . '/../Entity/EUtente.class.php';

class CGestioneStatistiche
{
    public function statisticheCliente(): ?array
    {
        $utente = $this->getUtenteAutenticato();

        if (
            $utente === null
            ||
            !$utente->isCliente()
        ) {
            return null;
        }

        $idCliente = $utente->getId();

        if ($idCliente === null) {
            return null;
        }

        $periodi = $this->getPeriodiCorrenti();

        $completateSettimana =
            $this->prenotazioniCompletateClientePeriodo(
                $idCliente,
                $periodi['settimana']['inizio'],
                $periodi['settimana']['fine']
            );

        $completateMese =
            $this->prenotazioniCompletateClientePeriodo(
                $idCliente,
                $periodi['mese']['inizio'],
                $periodi['mese']['fine']
            );

        $completateAnno =
            $this->prenotazioniCompletateClientePeriodo(
                $idCliente,
                $periodi['anno']['inizio'],
                $periodi['anno']['fine']
            );

        return [
            'cliente' =>
            $utente,

            'appuntamenti_completati_totali' =>
            FPrenotazione::countCompletateByCliente(
                $idCliente
            ),

            'spese' => [
                'settimana' =>
                $this->calcolaIncassoReale(
                    $completateSettimana
                ),

                'mese' =>
                $this->calcolaIncassoReale(
                    $completateMese
                ),

                'anno' =>
                $this->calcolaIncassoReale(
                    $completateAnno
                )
            ]
        ];
    }

    public function statisticheProprietario(): ?array
    {
        $utente = $this->getUtenteAutenticato();

        if (
            $utente === null
            ||
            !$utente->isProprietario()
        ) {
            return null;
        }

        $periodi = $this->getPeriodiCorrenti();

        $completateSettimana =
            $this->prenotazioniCompletatePeriodo(
                $periodi['settimana']['inizio'],
                $periodi['settimana']['fine']
            );

        $completateMese =
            $this->prenotazioniCompletatePeriodo(
                $periodi['mese']['inizio'],
                $periodi['mese']['fine']
            );

        $completateAnno =
            $this->prenotazioniCompletatePeriodo(
                $periodi['anno']['inizio'],
                $periodi['anno']['fine']
            );

        return [
            'proprietario' =>
            $utente,

            'incassi' => [
                'settimana' =>
                $this->calcolaIncassoReale(
                    $completateSettimana
                ),

                'mese' =>
                $this->calcolaIncassoReale(
                    $completateMese
                ),

                'anno' =>
                $this->calcolaIncassoReale(
                    $completateAnno
                )
            ],

            'appuntamenti_completati' => [
                'settimana' =>
                count($completateSettimana),

                'mese' =>
                count($completateMese),

                'anno' =>
                count($completateAnno)
            ],

            'servizi_piu_richiesti' => [
                'settimana' =>
                $this->calcolaServiziPiuRichiesti(
                    $completateSettimana
                ),

                'mese' =>
                $this->calcolaServiziPiuRichiesti(
                    $completateMese
                ),

                'anno' =>
                $this->calcolaServiziPiuRichiesti(
                    $completateAnno
                )
            ]
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

    private function prenotazioniCompletatePeriodo(
        string $dataInizio,
        string $dataFine
    ): array {
        $prenotazioni =
            FPrenotazione::loadByPeriodo(
                $dataInizio,
                $dataFine
            );

        $completate = [];

        foreach (
            $prenotazioni
            as $prenotazione
        ) {
            if (
                $prenotazione->getStato()
                === 'completata'
            ) {
                $completate[] =
                    $prenotazione;
            }
        }

        return $completate;
    }

    private function prenotazioniCompletateClientePeriodo(
        int $idCliente,
        string $dataInizio,
        string $dataFine
    ): array {
        $prenotazioni =
            $this->prenotazioniCompletatePeriodo(
                $dataInizio,
                $dataFine
            );

        $completateCliente = [];

        foreach (
            $prenotazioni
            as $prenotazione
        ) {
            if (
                $prenotazione
                ->getCliente()
                ->getId()
                === $idCliente
            ) {
                $completateCliente[] =
                    $prenotazione;
            }
        }

        return $completateCliente;
    }

    private function calcolaIncassoReale(
        array $prenotazioni
    ): float {
        $totaleCentesimi = 0;

        foreach (
            $prenotazioni
            as $prenotazione
        ) {
            $idPrenotazione =
                $prenotazione->getId();

            if ($idPrenotazione === null) {
                continue;
            }

            $pagamento =
                FPagamento::loadByPrenotazione(
                    $idPrenotazione
                );

            if ($pagamento === null) {
                continue;
            }

            $totaleCentesimi +=
                (int) round(
                    $pagamento->getImportoPagato()
                        * 100
                );
        }

        return round(
            $totaleCentesimi / 100,
            2
        );
    }

    private function calcolaServiziPiuRichiesti(
        array $prenotazioni
    ): array {
        $conteggi = [];
        $nomiServizi = [];

        foreach (
            $prenotazioni
            as $prenotazione
        ) {
            $servizio =
                $prenotazione->getServizio();

            $idServizio =
                $servizio->getId();

            if ($idServizio === null) {
                continue;
            }

            if (
                !isset(
                    $conteggi[$idServizio]
                )
            ) {
                $conteggi[$idServizio] = 0;
            }

            $conteggi[$idServizio]++;

            $nomiServizi[$idServizio] =
                $servizio->getNome();
        }

        arsort($conteggi);

        $classifica = [];

        foreach (
            $conteggi
            as $idServizio => $quantita
        ) {
            $classifica[] = [
                'id_servizio' =>
                (int) $idServizio,

                'nome_servizio' =>
                $nomiServizi[$idServizio],

                'quantita' =>
                $quantita
            ];
        }

        return $classifica;
    }

    private function getPeriodiCorrenti(): array
    {
        return [
            'settimana' => [
                'inizio' =>
                date(
                    'Y-m-d',
                    strtotime(
                        'monday this week'
                    )
                ),

                'fine' =>
                date(
                    'Y-m-d',
                    strtotime(
                        'sunday this week'
                    )
                )
            ],

            'mese' => [
                'inizio' =>
                date(
                    'Y-m-d',
                    strtotime(
                        'first day of this month'
                    )
                ),

                'fine' =>
                date(
                    'Y-m-d',
                    strtotime(
                        'last day of this month'
                    )
                )
            ],

            'anno' => [
                'inizio' =>
                date('Y-01-01'),

                'fine' =>
                date('Y-12-31')
            ]
        ];
    }
}
