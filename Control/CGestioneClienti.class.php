<?php

require_once __DIR__ . '/../View/VClientiProprietario.class.php';

require_once __DIR__ . '/../Foundation/FUtente.class.php';
require_once __DIR__ . '/../Foundation/FPrenotazione.class.php';
require_once __DIR__ . '/../Foundation/FCoupon.class.php';
require_once __DIR__ . '/../Foundation/FSessione.class.php';

class CGestioneClienti
{
    private function richiediProprietario(): void
    {
        if (!$this->isProprietarioAutenticato()) {
            header('Location: index.php');
            exit;
        }
    }

    public function mostraClienti(array $request = []): void
    {
        $this->richiediProprietario();
        $get = $request['get'] ?? [];
        $id = (int) ($get['id_cliente'] ?? 0);
        $dettaglio = $id > 0 ? $this->storicoCliente($id) : null;
        $clienti = $this->elencoClienti() ?? [];
        $statisticheClienti = [];

        foreach ($clienti as $cliente) {
            $idCliente = $cliente->getId();
            if ($idCliente === null) {
                continue;
            }

            $statisticheClienti[$idCliente] = [
                'no_show_totali' => FPrenotazione::countNoShowByCliente($idCliente),
                'no_show_consecutivi' => $cliente->getNoShowConsecutivi()
            ];
        }

        (new VClientiProprietario())->mostraClienti([
            'clienti' => $clienti,
            'statistiche_clienti' => $statisticheClienti,
            'dettaglio' => $dettaglio,
            'messaggio' => isset($get['esito']) ? 'Prenotazioni online riabilitate.' : null,
            'errore' => isset($get['errore']) || ($id > 0 && $dettaglio === null) ? 'Cliente non trovato o operazione non disponibile.' : null
        ]);
    }

    public function gestisciRiabilitazioneCliente(array $request): void
    {
        $this->richiediProprietario();
        $id = (int) ($request['post']['id_cliente'] ?? 0);
        $ok = $this->riabilitaPrenotazioniOnline($id);
        header('Location: index.php?pagina=clienti&id_cliente=' . $id . ($ok ? '&esito=salvato' : '&errore=operazione'));
        exit;
    }


    public function elencoClienti(): ?array
    {
        if (!$this->isProprietarioAutenticato()) {
            return null;
        }

        return FUtente::loadClienti();
    }

    public function dettaglioCliente(
        int $idCliente
    ): ?array {
        if (!$this->isProprietarioAutenticato()) {
            return null;
        }

        $cliente =
            FUtente::load(
                $idCliente
            );

        if (
            $cliente === null
            ||
            !$cliente->isCliente()
        ) {
            return null;
        }

        $storico =
            FPrenotazione::loadByCliente(
                $idCliente
            );

        $ultimiAppuntamenti =
            array_slice(
                $storico,
                0,
                10
            );

        return [
            'cliente' =>
            $cliente,

            'ultimi_appuntamenti' =>
            $ultimiAppuntamenti,

            'totale_appuntamenti' =>
            count($storico)
        ];
    }

    public function storicoCliente(
        int $idCliente
    ): ?array {
        if (!$this->isProprietarioAutenticato()) {
            return null;
        }

        $cliente = FUtente::load($idCliente);

        if ($cliente === null || !$cliente->isCliente()) {
            return null;
        }

        $righeAppuntamenti = [];

        foreach (FPrenotazione::loadByCliente($idCliente) as $prenotazione) {
            $oraInizio = substr($prenotazione->getOra(), 0, 5);

            $righeAppuntamenti[] = [
                'data' => $this->formattaDataItaliana(
                    $prenotazione->getData()
                ),
                'ora_inizio' => $oraInizio,
                'ora_fine' => $this->calcolaOraFine(
                    $oraInizio,
                    $prenotazione->getDurata()
                ),
                'servizio' => $prenotazione->getNomeServizio(),
                'stato' => $this->etichettaStato(
                    $prenotazione->getStato()
                )
            ];
        }

        // Sola consultazione dei coupon gia' persistiti: nessuna generazione o modifica.
        $righeCoupon = [];
        $creditoDisponibile = 0.0;
        $couponDisponibili = 0;
        foreach (array_reverse(FCoupon::loadByCliente($idCliente)) as $coupon) {
            $disponibile = $coupon->isDisponibile();
            if ($disponibile) {
                $couponDisponibili++;
                $creditoDisponibile += $coupon->getCreditoResiduo();
            }
            $righeCoupon[] = [
                'codice' => $coupon->getCodice(),
                'valore_iniziale' => $coupon->getValoreIniziale(),
                'credito_residuo' => $coupon->getCreditoResiduo(),
                'data_generazione' => $this->formattaDataItaliana(substr($coupon->getDataOraGenerazione(), 0, 10)),
                'stato' => $disponibile ? 'Disponibile' : 'Esaurito',
                'disponibile' => $disponibile,
                'usato_parzialmente' => $disponibile && $coupon->getCreditoResiduo() < $coupon->getValoreIniziale(),
                'traguardo' => $coupon->getTraguardoCompletamenti()
            ];
        }

        return [
            'cliente' => $cliente,
            'appuntamenti' => $righeAppuntamenti,
            'coupon' => $righeCoupon,
            'coupon_disponibili' => $couponDisponibili,
            'credito_coupon' => round($creditoDisponibile, 2),
            'no_show_totali' => FPrenotazione::countNoShowByCliente($idCliente),
            'no_show_consecutivi' => $cliente->getNoShowConsecutivi()
        ];
    }

    private function formattaDataItaliana(string $data): string
    {
        $oggettoData = DateTimeImmutable::createFromFormat('!Y-m-d', $data);

        return $oggettoData === false
            ? $data
            : $oggettoData->format('d/m/Y');
    }

    private function calcolaOraFine(string $oraInizio, int $durata): string
    {
        $ora = DateTimeImmutable::createFromFormat('!H:i', $oraInizio);

        if ($ora === false) {
            return '';
        }

        return $ora->modify('+' . $durata . ' minutes')->format('H:i');
    }

    private function etichettaStato(string $stato): string
    {
        $etichette = [
            'prenotata' => 'Prenotato',
            'completata' => 'Completato',
            'cancellata' => 'Cancellato',
            'no-show' => 'Cliente assente'
        ];

        return $etichette[$stato] ?? $stato;
    }

    public function riabilitaPrenotazioniOnline(
        int $idCliente
    ): bool {
        if (!$this->isProprietarioAutenticato()) {
            return false;
        }

        $cliente =
            FUtente::load(
                $idCliente
            );

        if (
            $cliente === null
            ||
            !$cliente->isCliente()
        ) {
            return false;
        }

        if (
            $cliente
                ->isPrenotazioniOnlineAbilitate()
        ) {
            return false;
        }

        $cliente
            ->riabilitaPrenotazioniOnline();

        return FUtente::update(
            $cliente
        );
    }

    private function isProprietarioAutenticato(): bool
    {
        $idUtente =
            FSessione::getIdUtenteAutenticato();

        if ($idUtente === null) {
            return false;
        }

        $utente =
            FUtente::load(
                $idUtente
            );

        if ($utente === null) {
            return false;
        }

        return $utente->isProprietario();
    }
}
