<?php

require_once __DIR__ . '/../Foundation/FSessione.class.php';
require_once __DIR__ . '/../Foundation/FUtente.class.php';
require_once __DIR__ . '/../Foundation/FPrenotazione.class.php';
require_once __DIR__ . '/../Foundation/FPagamento.class.php';
require_once __DIR__ . '/../Foundation/FCoupon.class.php';
require_once __DIR__ . '/../Entity/EUtente.class.php';
require_once __DIR__ . '/../View/VCouponCliente.class.php';

class CGestioneFedelta
{
    public function mostraCouponCliente(array $request = []): void
    {
        $cliente = $this->getClienteAutenticato();

        if ($cliente === null || $cliente->getId() === null) {
            $this->redirect('index.php?pagina=login');
        }

        $dati = $this->riepilogoCliente($cliente->getId());

        if ($dati === null) {
            $this->redirect('index.php');
        }

        $utilizzi = [];
        foreach (FPagamento::loadByCliente($cliente->getId()) as $pagamento) {
            $couponUsato = $pagamento->getCouponUtilizzato();
            if ($couponUsato === null || $pagamento->getImportoCouponUtilizzato() <= 0) continue;
            $p = $pagamento->getPrenotazione();
            $utilizzi[$couponUsato->getCodice()][] = [
                'data' => $this->formattaDataOra($p->getData() . ' ' . $p->getOra()),
                'servizio' => $p->getNomeServizio(),
                'utilizzato' => $pagamento->getImportoCouponUtilizzato(),
                'prezzo' => $p->getPrezzo(),
                'pagato' => $pagamento->getImportoPagato()
            ];
        }
        foreach ($dati['coupon'] as &$coupon) {
            $coupon['utilizzi'] = $utilizzi[$coupon['codice']] ?? [];
        }
        unset($coupon);
        (new VCouponCliente())->mostraCoupon($dati);
    }

    public function situazioneFedeltaCliente(): ?array
    {
        $cliente = $this->getClienteAutenticato();

        if ($cliente === null || $cliente->getId() === null) {
            return null;
        }

        return [
            'cliente' => $cliente,
            'appuntamenti_completati' =>
                FPrenotazione::countCompletateByCliente($cliente->getId())
        ];
    }

    public function couponCliente(): ?array
    {
        $cliente = $this->getClienteAutenticato();

        if ($cliente === null || $cliente->getId() === null) {
            return null;
        }

        return FCoupon::loadByCliente($cliente->getId());
    }

    public function riepilogoCliente(int $idCliente): ?array
    {
        $cliente = $this->getClienteAutenticato();

        if ($cliente === null || $cliente->getId() !== $idCliente) {
            return null;
        }

        $numeroCompletati = FPrenotazione::countCompletateByCliente($idCliente);
        $coupon = FCoupon::loadByCliente($idCliente);
        $righeCoupon = [];
        $creditoDisponibile = 0.0;
        $numeroDisponibili = 0;

        foreach (array_reverse($coupon) as $elemento) {
            if ($elemento->isDisponibile()) {
                $creditoDisponibile += $elemento->getCreditoResiduo();
                $numeroDisponibili++;
            }

            $righeCoupon[] = [
                'id' => $elemento->getId(),
                'codice' => $elemento->getCodice(),
                'valore_iniziale' => $elemento->getValoreIniziale(),
                'credito_residuo' => $elemento->getCreditoResiduo(),
                'data_generazione' => $this->formattaDataOra($elemento->getDataOraGenerazione()),
                'stato' => $elemento->isDisponibile() ? 'Disponibile' : 'Esaurito',
                'disponibile' => $elemento->isDisponibile(),
                'traguardo' => $elemento->getTraguardoCompletamenti()
            ];
        }

        return [
            'utente' => $cliente,
            'fedelta' => $this->calcolaAvanzamentoFedelta($numeroCompletati),
            'coupon' => $righeCoupon,
            'coupon_disponibili' => $numeroDisponibili,
            'credito_coupon' => round($creditoDisponibile, 2),
            'spese' => $this->calcolaSpese($idCliente)
        ];
    }

    private function calcolaAvanzamentoFedelta(int $numeroCompletati): array
    {
        $nelCiclo = $numeroCompletati % 60;

        if ($nelCiclo < 10) {
            $obiettivo = 10;
            $valoreCoupon = 10.0;
        } elseif ($nelCiclo < 30) {
            $obiettivo = 30;
            $valoreCoupon = 20.0;
        } else {
            $obiettivo = 60;
            $valoreCoupon = 30.0;
        }

        return [
            'completati_totali' => $numeroCompletati,
            'nel_ciclo' => $nelCiclo,
            'obiettivo' => $obiettivo,
            'appuntamenti_mancanti' => $obiettivo - $nelCiclo,
            'percentuale' => (int) floor(($nelCiclo / $obiettivo) * 100),
            'valore_prossimo_coupon' => $valoreCoupon
        ];
    }

    private function calcolaSpese(int $idCliente): array
    {
        $oggi = new DateTimeImmutable('today');
        $inizioSettimana = $oggi->modify('monday this week');
        $fineSettimana = $inizioSettimana->modify('+7 days');
        $inizioMese = $oggi->modify('first day of this month');
        $fineMese = $inizioMese->modify('+1 month');
        $inizioAnno = $oggi->setDate((int) $oggi->format('Y'), 1, 1);
        $fineAnno = $inizioAnno->modify('+1 year');
        $mesi = [
            1 => 'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
            'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'
        ];

        return [
            'settimana' => FPagamento::totaleClientePeriodo(
                $idCliente,
                $inizioSettimana->format('Y-m-d 00:00:00'),
                $fineSettimana->format('Y-m-d 00:00:00')
            ),
            'mese' => FPagamento::totaleClientePeriodo(
                $idCliente,
                $inizioMese->format('Y-m-d 00:00:00'),
                $fineMese->format('Y-m-d 00:00:00')
            ),
            'anno' => FPagamento::totaleClientePeriodo(
                $idCliente,
                $inizioAnno->format('Y-m-d 00:00:00'),
                $fineAnno->format('Y-m-d 00:00:00')
            ),
            'mese_nome' => $mesi[(int) $oggi->format('n')],
            'anno_numero' => (int) $oggi->format('Y')
        ];
    }

    private function formattaDataOra(string $dataOra): string
    {
        $data = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dataOra);
        return $data === false ? $dataOra : $data->format('d/m/Y');
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
        VBase::redirect($url);
        exit;
    }
}
