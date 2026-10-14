<?php

require_once __DIR__ . '/../Foundation/FSessione.class.php';
require_once __DIR__ . '/../Foundation/FUtente.class.php';
require_once __DIR__ . '/../Foundation/FPagamento.class.php';
require_once __DIR__ . '/../Entity/EUtente.class.php';
require_once __DIR__ . '/../View/VSpeseCliente.class.php';

class CGestioneSpese
{
    public function mostraSpeseCliente(array $request = []): void
    {
        $cliente = $this->getClienteAutenticato();

        if ($cliente === null || $cliente->getId() === null) {
            $this->redirect('index.php?pagina=login');
        }

        $get = $request['get'] ?? [];
        $periodoSelezionato = is_string($get['periodo'] ?? null)
            ? $get['periodo']
            : 'settimana';

        if (!in_array($periodoSelezionato, ['settimana', 'mese', 'anno'], true)) {
            $periodoSelezionato = 'settimana';
        }

        $periodi = $this->creaPeriodiCorrenti();
        $intervallo = $this->creaIntervallo($periodoSelezionato, $get['data'] ?? null);
        $righe = [];

        foreach (FPagamento::loadByClientePeriodo(
            $cliente->getId(),
            $intervallo['inizio']->format('Y-m-d H:i:s'),
            $intervallo['fine']->format('Y-m-d H:i:s')
        ) as $pagamento) {
            $prenotazione = $pagamento->getPrenotazione();
            $coupon = $pagamento->getCouponUtilizzato();

            $righe[] = [
                'data' => $this->formattaData($prenotazione->getData()),
                'servizio' => $prenotazione->getNomeServizio(),
                'prezzo_originario' => $prenotazione->getPrezzo(),
                'coupon' => $coupon === null ? null : $coupon->getCodice(),
                'sconto' => $pagamento->getImportoCouponUtilizzato(),
                'importo_pagato' => $pagamento->getImportoPagato()
            ];
        }

        $riepilogo = [];

        foreach ($periodi as $chiave => $periodo) {
            $riepilogo[$chiave] = [
                'etichetta' => $periodo['etichetta'],
                'totale' => FPagamento::totaleClientePeriodo(
                    $cliente->getId(),
                    $periodo['inizio']->format('Y-m-d H:i:s'),
                    $periodo['fine']->format('Y-m-d H:i:s')
                ),
                'selezionato' => $chiave === $periodoSelezionato
                    && $periodo['inizio'] == $intervallo['inizio']
            ];
        }

        (new VSpeseCliente())->mostraSpese([
            'utente' => $cliente,
            'periodi' => $riepilogo,
            'periodo_selezionato' => $periodoSelezionato,
            'titolo_periodo' => $intervallo['etichetta'],
            'righe' => $righe,
            'navigazione' => $intervallo,
            'totale_selezionato' => FPagamento::totaleClientePeriodo(
                $cliente->getId(),
                $intervallo['inizio']->format('Y-m-d H:i:s'),
                $intervallo['fine']->format('Y-m-d H:i:s')
            )
        ]);
    }

    /** Intervalli di calendario semiaperti: la data finale non viene conteggiata due volte. */
    private function creaIntervallo(string $periodo, $dataRichiesta): array
    {
        $oggi = new DateTimeImmutable('today');
        $data = is_string($dataRichiesta)
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $dataRichiesta) : false;
        if ($data === false || $data->format('Y-m-d') !== $dataRichiesta
            || (int) $data->format('Y') < 1900 || $data > $oggi) {
            $data = $oggi;
        }
        $mesi = [1 => 'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
            'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];
        if ($periodo === 'settimana') {
            $inizio = $data->modify('monday this week');
            $fine = $inizio->modify('+7 days');
            $precedente = $inizio->modify('-7 days');
            $etichetta = 'Settimana ' . $inizio->format('d/m/Y') . ' – ' . $fine->modify('-1 day')->format('d/m/Y');
        } elseif ($periodo === 'mese') {
            $inizio = $data->modify('first day of this month');
            $fine = $inizio->modify('+1 month');
            $precedente = $inizio->modify('-1 month');
            $etichetta = $mesi[(int) $inizio->format('n')] . ' ' . $inizio->format('Y');
        } else {
            $inizio = $data->setDate((int) $data->format('Y'), 1, 1);
            $fine = $inizio->modify('+1 year');
            $precedente = $inizio->modify('-1 year');
            $etichetta = $inizio->format('Y');
        }
        return ['inizio' => $inizio, 'fine' => $fine, 'etichetta' => $etichetta,
            'data' => $data->format('Y-m-d'), 'oggi' => $oggi->format('Y-m-d'),
            'precedente' => $precedente->format('Y-m-d'),
            'successivo' => $fine <= $oggi ? $fine->format('Y-m-d') : null,
            'corrente' => $oggi >= $inizio && $oggi < $fine];
    }

    private function creaPeriodiCorrenti(): array
    {
        $oggi = new DateTimeImmutable('today');
        $inizioSettimana = $oggi->modify('monday this week');
        $inizioMese = $oggi->modify('first day of this month');
        $inizioAnno = $oggi->setDate((int) $oggi->format('Y'), 1, 1);
        $mesi = [
            1 => 'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
            'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'
        ];

        return [
            'settimana' => [
                'etichetta' => 'Questa settimana',
                'inizio' => $inizioSettimana,
                'fine' => $inizioSettimana->modify('+7 days')
            ],
            'mese' => [
                'etichetta' => $mesi[(int) $oggi->format('n')],
                'inizio' => $inizioMese,
                'fine' => $inizioMese->modify('+1 month')
            ],
            'anno' => [
                'etichetta' => $oggi->format('Y'),
                'inizio' => $inizioAnno,
                'fine' => $inizioAnno->modify('+1 year')
            ]
        ];
    }

    private function formattaData(string $data): string
    {
        $valore = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
        return $valore === false ? $data : $valore->format('d/m/Y');
    }

    private function getClienteAutenticato(): ?EUtente
    {
        $idUtente = FSessione::getIdUtenteAutenticato();

        if ($idUtente === null) {
            return null;
        }

        $utente = FUtente::load($idUtente);
        return $utente !== null && $utente->isCliente() ? $utente : null;
    }

    private function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
