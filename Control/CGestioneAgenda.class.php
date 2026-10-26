<?php

require_once __DIR__ . '/../View/VAgendaProprietario.class.php';
require_once __DIR__ . '/../View/VAgendaSetteGiorniProprietario.class.php';
require_once __DIR__ . '/CGestioneAppuntamenti.class.php';

require_once __DIR__ . '/../Foundation/FSessione.class.php';
require_once __DIR__ . '/../Foundation/FConnectionDB.class.php';
require_once __DIR__ . '/../Foundation/FPrenotazione.class.php';
require_once __DIR__ . '/../Foundation/FPagamento.class.php';
require_once __DIR__ . '/../Foundation/FUtente.class.php';
require_once __DIR__ . '/../Foundation/FCoupon.class.php';
require_once __DIR__ . '/CGestioneNotifiche.class.php';
require_once __DIR__ . '/../Foundation/FDisponibilita.class.php';

require_once __DIR__ . '/../Entity/EPrenotazione.class.php';
require_once __DIR__ . '/../Entity/EPagamento.class.php';
require_once __DIR__ . '/../Entity/ECoupon.class.php';
require_once __DIR__ . '/../Entity/EUtente.class.php';

class CGestioneAgenda
{
    private function richiediProprietario(): void
    {
        if (!$this->isProprietarioAutenticato()) {
            VBase::redirect('index.php');
            exit;
        }
    }

    public function mostraAgenda(array $request = []): void
    {
        $this->richiediProprietario();
        $get = $request['get'] ?? [];
        $data = is_string($get['data'] ?? null) ? $get['data'] : date('Y-m-d');
        if (isset($get['giorno'], $get['mese'], $get['anno'])
            && ctype_digit((string) $get['giorno'])
            && strlen((string) $get['giorno']) <= 2
            && ctype_digit((string) $get['mese'])
            && ctype_digit((string) $get['anno'])
            && strlen((string) $get['anno']) === 4
            && checkdate((int) $get['mese'], (int) $get['giorno'], (int) $get['anno'])) {
            $data = sprintf(
                '%04d-%02d-%02d',
                (int) $get['anno'],
                (int) $get['mese'],
                (int) $get['giorno']
            );
        }
        $giorno = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
        if ($giorno === false || $giorno->format('Y-m-d') !== $data) {
            $data = date('Y-m-d');
        }
        $righe = [];
        foreach ($this->agendaPerData($data) as $prenotazione) {
            $righe[] = [
                'prenotazione' => $prenotazione,
                'puo_chiudere' => $this->appuntamentoChiudibile($prenotazione),
                'puo_annullare' => $this->appuntamentoAnnullabileDalProprietario($prenotazione),
                'pagamento' => FPagamento::loadByPrenotazione($prenotazione->getId())
            ];
        }
        (new VAgendaProprietario())->mostraAgenda([
            'data' => $data, 'righe' => $righe,
            'fasce' => FDisponibilita::loadByData($data),
            'messaggio' => ($get['esito'] ?? '') === 'salvato' ? 'Appuntamento aggiornato.' : null,
            'errore' => isset($get['errore']) ? 'Operazione non riuscita. Controlla lo stato e l’orario dell’appuntamento e, se usato, il coupon del cliente.' : null
        ]);
    }

    public function mostraAgendaSetteGiorni(array $request = []): void
    {
        $this->richiediProprietario();

        $get = $request['get'] ?? [];
        $dataInizio = is_string($get['data'] ?? null)
            ? $get['data']
            : date('Y-m-d');

        $inizio = DateTimeImmutable::createFromFormat('!Y-m-d', $dataInizio);

        if ($inizio === false || $inizio->format('Y-m-d') !== $dataInizio) {
            $inizio = new DateTimeImmutable('today');
            $dataInizio = $inizio->format('Y-m-d');
        }

        $giorni = [];
        $totale = 0;

        $dateAperte = FDisponibilita::loadDateAperte($dataInizio);
        foreach ($dateAperte as $data) {
            $giorno = new DateTimeImmutable($data);
            $righe = [];

            foreach ($this->agendaPerData($data) as $prenotazione) {
                $righe[] = [
                    'prenotazione' => $prenotazione
                ];
            }

            $totale += count($righe);
            $giorni[] = [
                'data' => $data,
                'etichetta' => $this->etichettaDataAgenda($giorno),
                'oggi' => $data === date('Y-m-d'),
                'righe' => $righe
            ];
        }

        (new VAgendaSetteGiorniProprietario())->mostraAgenda([
            'data_inizio' => $dataInizio,
            'data_fine' => $dateAperte ? $dateAperte[count($dateAperte) - 1] : $dataInizio,
            'giorni' => $giorni,
            'totale_appuntamenti' => $totale
        ]);
    }

    public function gestisciChiusuraAppuntamento(array $request): void
    {
        $this->richiediProprietario();
        $post = $request['post'] ?? [];
        $coupon = is_string($post['coupon'] ?? null) ? trim($post['coupon']) : '';
        $this->esitoAgenda($request, $this->chiudiAppuntamento(
            (int) ($post['id_prenotazione'] ?? 0),
            $coupon === '' ? null : $coupon
        ));
    }

    public function gestisciAssenza(array $request): void
    {
        $this->richiediProprietario();
        $this->esitoAgenda($request, $this->segnaNoShow((int) ($request['post']['id_prenotazione'] ?? 0)));
    }

    public function gestisciAnnullamentoProprietario(array $request): void
    {
        $this->richiediProprietario();
        $this->esitoAgenda($request, $this->annullaAppuntamentoPerChiusura((int) ($request['post']['id_prenotazione'] ?? 0)));
    }

    private function esitoAgenda(array $request, bool $ok): void
    {
        $data = $request['post']['data'] ?? date('Y-m-d');
        $data = is_string($data) ? $data : date('Y-m-d');
        $ritornoAppuntamentiOggi = ($request['post']['ritorno'] ?? '') === 'appuntamenti_oggi';

        if ($ritornoAppuntamentiOggi) {
            $pagina = 'appuntamenti_oggi';
            $filtro = is_string($request['post']['filtro_ritorno'] ?? null)
                ? $request['post']['filtro_ritorno']
                : '';

            if (in_array($filtro, ['da_svolgere', 'completati', 'assenze'], true)) {
                $pagina .= '&filtro=' . rawurlencode($filtro);
            }
        } else {
            $pagina = 'agenda&data=' . rawurlencode($data);
        }

        VBase::redirect('index.php?pagina=' . $pagina . ($ok ? '&esito=salvato' : '&errore=operazione'));
        exit;
    }


    public function agendaPerData(
        string $data
    ): array {
        if (!$this->isProprietarioAutenticato()) {
            return [];
        }

        $prenotazioni =
            FPrenotazione::loadByData(
                $data
            );

        $agenda = [];

        foreach (
            $prenotazioni as $prenotazione
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
        if (!$this->isProprietarioAutenticato()) {
            return null;
        }

        return FPrenotazione::load(
            $idPrenotazione
        );
    }

    public function storicoPagamenti(string $dataInizio, string $dataFine): array
    {
        if (!$this->isProprietarioAutenticato()) {
            return [];
        }

        return FPagamento::loadByPeriodo($dataInizio, $dataFine);
    }

    // Verifica pura: mostra anche coupon esauriti, senza modificare il credito.
    public function verificaCoupon(string $codice): ?ECoupon
    {
        if (!$this->isProprietarioAutenticato()) {
            return null;
        }

        return FCoupon::loadByCodice($codice);
    }

    /** Riepilogo prima della conferma; la chiusura rilegge e rivalida tutto. */
    public function riepilogoPagamento(
        int $idPrenotazione,
        ?string $codiceCoupon = null
    ): ?array {
        if (!$this->isProprietarioAutenticato()) {
            return null;
        }

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
        if (!$this->isProprietarioAutenticato()) {
            return false;
        }

        $connection = FConnectionDB::connect();
        $connection->beginTransaction();

        try {
            FConnectionDB::bloccaCalendario();
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

        if ($nuovoCoupon !== null) {
            try {
                CGestioneNotifiche::nuovoCouponFedelta($nuovoCoupon);
            } catch (Throwable $e) {
                error_log('Lumèa: errore email nuovo coupon fedelta: ' . $e->getMessage());
            }
        }
        return true;
    }

    public function segnaNoShow(int $idPrenotazione): bool
    {
        if (!$this->isProprietarioAutenticato()) {
            return false;
        }

        $connection = FConnectionDB::connect();
        $connection->beginTransaction();

        try {
            FConnectionDB::bloccaCalendario();
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

        if ($terzoNoShow) {
            try {
                CGestioneNotifiche::sospensioneTerzoNoShow($cliente);
            } catch (Throwable $e) {
                error_log('Lumèa: errore email sospensione terzo no-show: ' . $e->getMessage());
            }
        }
        return true;
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

    private function appuntamentoChiudibile(?EPrenotazione $prenotazione): bool
    {
        return $prenotazione !== null
            && $prenotazione->getStato() === 'prenotata'
            && $this->orarioAppuntamentoRaggiunto($prenotazione);
    }

    private function appuntamentoAnnullabileDalProprietario(?EPrenotazione $prenotazione): bool
    {
        return $prenotazione !== null
            && $prenotazione->getStato() === 'prenotata'
            && !$this->orarioAppuntamentoRaggiunto($prenotazione);
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
        if (!$this->isProprietarioAutenticato()) {
            return false;
        }

        $prenotazione =
            FPrenotazione::load(
                $idPrenotazione
            );

        if (!$this->appuntamentoAnnullabileDalProprietario($prenotazione)) {
            return false;
        }

        /*
         * Nessuna regola delle 48 ore.
         *
         * L'annullamento libera l'appuntamento;
         * non modifica le fasce di apertura del salone.
         */
        $connection =
            FConnectionDB::connect();

        $connection->beginTransaction();

        $assegnazione = null;

        try {
            FConnectionDB::bloccaCalendario();
            // Una cancellazione del proprietario può concorrere con la chiusura.
            $prenotazione = FPrenotazione::loadPerAggiornamento($idPrenotazione);
            if (!$this->appuntamentoAnnullabileDalProprietario($prenotazione)) {
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

            // La stessa coda FIFO vale per gli annullamenti di entrambi i ruoli.
            // La procedura verifica anche che il salone sia ancora aperto.
            $assegnazione = (new CGestioneAppuntamenti())
                ->assegnaPrimaRichiestaValida($prenotazione);

            if (!$connection->commit()) {
                throw new RuntimeException('Annullamento non confermato.');
            }
        } catch (Throwable $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            error_log('Lumèa, annullamento proprietario: ' . $e->getMessage());

            return false;
        }

        try {
            CGestioneNotifiche::cancellazioneProprietario($prenotazione);
        } catch (Throwable $e) {
            error_log('Lumèa: errore email cancellazione proprietario: ' . $e->getMessage());
        }

        if ($assegnazione !== null) {
            try {
                CGestioneNotifiche::assegnazioneListaAttesa(
                    $assegnazione['prenotazione'],
                    $assegnazione['alternativa']
                );
            } catch (Throwable $e) {
                error_log('Lumèa: errore email lista attesa: ' . $e->getMessage());
            }
        }

        return true;
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
            'LM-'
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

    private function etichettaDataAgenda(DateTimeImmutable $data): string
    {
        $giorni = [
            1 => 'Lunedì',
            2 => 'Martedì',
            3 => 'Mercoledì',
            4 => 'Giovedì',
            5 => 'Venerdì',
            6 => 'Sabato',
            7 => 'Domenica'
        ];
        $mesi = [
            1 => 'gennaio',
            2 => 'febbraio',
            3 => 'marzo',
            4 => 'aprile',
            5 => 'maggio',
            6 => 'giugno',
            7 => 'luglio',
            8 => 'agosto',
            9 => 'settembre',
            10 => 'ottobre',
            11 => 'novembre',
            12 => 'dicembre'
        ];

        return sprintf(
            '%s %d %s',
            $giorni[(int) $data->format('N')],
            (int) $data->format('j'),
            $mesi[(int) $data->format('n')]
        );
    }

    private function orarioAppuntamentoRaggiunto(
        EPrenotazione $prenotazione
    ): bool {
        $fusoOrario = new DateTimeZone('Europe/Rome');
        $dataOraAppuntamento = DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i:s',
            $prenotazione->getData() . ' ' . $prenotazione->getOra(),
            $fusoOrario
        );

        if ($dataOraAppuntamento === false) {
            return false;
        }

        return $dataOraAppuntamento <= new DateTimeImmutable('now', $fusoOrario);
    }
}
