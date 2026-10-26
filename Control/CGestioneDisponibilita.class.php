<?php

require_once __DIR__ . '/../Foundation/FConnectionDB.class.php';
require_once __DIR__ . '/../Foundation/FUtente.class.php';
require_once __DIR__ . '/../Foundation/FSessione.class.php';
require_once __DIR__ . '/../Foundation/FDisponibilita.class.php';
require_once __DIR__ . '/../Foundation/FPrenotazione.class.php';

require_once __DIR__ . '/../Entity/EDisponibilita.class.php';
require_once __DIR__ . '/../Entity/EPrenotazione.class.php';

require_once __DIR__ . '/../View/VDisponibilitaProprietario.class.php';


class CGestioneDisponibilita
{
    private function sostituisciFasce(string $data, array $fasce): void
    {
        if (!FDisponibilita::deleteByData($data)) {
            throw new RuntimeException('Impossibile sostituire gli orari.');
        }
        foreach ($fasce as $fascia) {
            if (!FDisponibilita::store(new EDisponibilita($data, $fascia['inizio'], $fascia['fine']))) {
                throw new RuntimeException('Impossibile salvare gli orari.');
            }
        }
    }


    /*
     * =====================================================
     * OPERAZIONI WEB
     * =====================================================
     */


    public function mostraDisponibilita(array $request = []): void
    {
        if (!$this->isProprietarioAutenticato()) {
            $this->redirect('index.php');
        }
        $get = $request['get'] ?? [];
        $anno = (int) ($get['anno'] ?? date('Y'));
        $mese = (int) ($get['mese'] ?? date('n'));
        if ($anno < 2000 || $anno > 2100 || $mese < 1 || $mese > 12) {
            $anno = (int) date('Y');
            $mese = (int) date('n');
        }
        $primo = new DateTimeImmutable(sprintf('%04d-%02d-01', $anno, $mese));
        $ultimo = $primo->modify('last day of this month');
        $fasce = $this->fascePerPeriodo($primo->format('Y-m-d'), $ultimo->format('Y-m-d'));
        $personalizzate = $this->eccezioniPerPeriodo($primo->format('Y-m-d'), $ultimo->format('Y-m-d'));
        $nomi = [1 => 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato', 'Domenica'];
        $perData = [];
        foreach ($fasce as $fascia) {
            $perData[$fascia->getData()][] = [
                'inizio' => substr($fascia->getOraInizio(), 0, 5),
                'fine' => substr($fascia->getOraFine(), 0, 5)
            ];
        }
        $calendario = [];
        $standard = [];
        for ($giorno = $primo; $giorno <= $ultimo; $giorno = $giorno->modify('+1 day')) {
            $data = $giorno->format('Y-m-d');
            $numero = (int) $giorno->format('N');
            $orari = $perData[$data] ?? [];
            $personale = in_array($data, $personalizzate, true);
            $modificabile = $data >= date('Y-m-d');
            $calendario[] = [
                'data' => $data, 'etichetta' => $nomi[$numero] . ' ' . $giorno->format('d/m/Y'),
                'fasce' => $orari, 'personalizzato' => $personale,
                'modificabile' => $modificabile, 'chiuso' => !$orari,
                'campi' => array_pad($orari, 2, ['inizio' => '', 'fine' => ''])
            ];
            if (!$personale && $modificabile && !isset($standard[$numero])) {
                $standard[$numero] = ['stato' => $orari ? 'aperto' : 'chiuso', 'fasce' => array_pad($orari, 2, ['inizio' => '', 'fine' => ''])];
            }
        }
        foreach ($nomi as $numero => $nome) {
            $standard[$numero] = $standard[$numero] ?? ['stato' => 'chiuso', 'fasce' => array_fill(0, 2, ['inizio' => '', 'fine' => ''])];
            $standard[$numero]['nome'] = $nome;
        }
        ksort($standard);
        $calendarioIniziale = array_slice($calendario, 0, 10);
        $calendarioRestante = array_slice($calendario, 10);

        (new VDisponibilitaProprietario())->mostraDisponibilita([
            'anno' => $anno,
            'mese' => $mese,
            'calendario' => $calendario,
            'calendario_iniziale' => $calendarioIniziale,
            'calendario_restante' => $calendarioRestante,
            'numero_giorni_restanti' => count($calendarioRestante),
            'standard' => $standard,
            'messaggio' => $this->getMessaggio($get['esito'] ?? ''),
            'errore' => $request['errore_locale'] ?? $this->getErrore($get['errore'] ?? '')
        ]);
    }

    public function gestisciApplicazioneOrarioMensile(
        array $request
    ): void {
        if (!$this->isProprietarioAutenticato()) {
            $this->redirect(
                'index.php'
            );
        }

        $post =
            $request['post']
            ?? [];

        $anno =
            (int) (
                $post['anno']
                ?? 0
            );

        $mese =
            (int) (
                $post['mese']
                ?? 0
            );

        $orarioRicevuto =
            $post['orario']
            ?? [];

        $orario =
            $this
                ->pulisciOrarioSettimanaleDaRequest(
                    is_array($orarioRicevuto)
                        ? $orarioRicevuto
                        : []
                );

        if ($orario === null) {
            $this->redirect(
                $this->urlDisponibilita(
                    $anno,
                    $mese,
                    'errore=orario'
                )
            );
        }

        $successo =
            $this->applicaOrarioMensile(
                $anno,
                $mese,
                $orario
            );

        if (!$successo) {
            $this->redirect(
                $this->urlDisponibilita(
                    $anno,
                    $mese,
                    'errore=mensile'
                )
            );
        }

        $this->redirect(
            $this->urlDisponibilita(
                $anno,
                $mese,
                'esito=mensile'
            )
        );
    }


    public function gestisciEccezioneGiornaliera(
        array $request
    ): void {
        if (!$this->isProprietarioAutenticato()) {
            $this->redirect(
                'index.php'
            );
        }

        $post =
            $request['post']
            ?? [];

        $data =
            trim(
                (string) (
                    $post['data']
                    ?? ''
                )
            );

        if (!$this->dataValida($data)) {
            $this->redirect(
                'index.php?pagina=disponibilita'
                    . '&errore=data'
            );
        }

        $fasceRicevute =
            $post['fasce']
            ?? [];

        $fasce =
            $this->pulisciFasceDaRequest(
                is_array($fasceRicevute)
                    ? $fasceRicevute
                    : []
            );

        $anno =
            (int) substr(
                $data,
                0,
                4
            );

        $mese =
            (int) substr(
                $data,
                5,
                2
            );

        if ($fasce === null) {
            $this->redirect(
                $this->urlDisponibilita(
                    $anno,
                    $mese,
                    'errore=orario'
                )
            );
        }

        if ($fasce === []) {
            $this->redirect(
                $this->urlDisponibilita(
                    $anno,
                    $mese,
                    'errore=eccezione_vuota'
                )
            );
        }

        $successo =
            $this
                ->impostaEccezioneGiornaliera(
                    $data,
                    $fasce
                );

        if (!$successo) {
            $this->redirect(
                $this->urlDisponibilita(
                    $anno,
                    $mese,
                    'errore=eccezione'
                )
            );
        }

        $this->redirect(
            $this->urlDisponibilita(
                $anno,
                $mese,
                'esito=eccezione'
            )
        );
    }


    public function gestisciChiusuraGiorno(
        array $request
    ): void {
        if (!$this->isProprietarioAutenticato()) {
            $this->redirect(
                'index.php'
            );
        }

        $post =
            $request['post']
            ?? [];

        $data =
            trim(
                (string) (
                    $post['data']
                    ?? ''
                )
            );

        if (!$this->dataValida($data)) {
            $this->redirect(
                'index.php?pagina=disponibilita'
                    . '&errore=data'
            );
        }

        $anno =
            (int) substr(
                $data,
                0,
                4
            );

        $mese =
            (int) substr(
                $data,
                5,
                2
            );

        if (
            !$this->chiudiGiorno(
                $data
            )
        ) {
            $this->redirect(
                $this->urlDisponibilita(
                    $anno,
                    $mese,
                    'errore=chiusura'
                )
            );
        }

        $this->redirect(
            $this->urlDisponibilita(
                $anno,
                $mese,
                'esito=chiusura'
            )
        );
    }


    /*
     * =====================================================
     * OPERAZIONI DI SISTEMA
     * =====================================================
     */


    public function fascePerData(
        string $data
    ): array {
        if (!$this->isProprietarioAutenticato()) {
            return [];
        }

        return
            FDisponibilita::loadByData(
                $data
            );
    }


    public function fascePerPeriodo(
        string $dataInizio,
        string $dataFine
    ): array {
        if (!$this->isProprietarioAutenticato()) {
            return [];
        }

        if (
            !$this->dataValida(
                $dataInizio
            )
            ||
            !$this->dataValida(
                $dataFine
            )
            ||
            $dataInizio > $dataFine
        ) {
            return [];
        }

        return
            FDisponibilita::loadByPeriodo(
                $dataInizio,
                $dataFine
            );
    }


    /*
     * L'orario settimanale utilizza:
     *
     * 1 = lunedì
     * 2 = martedì
     * ...
     * 7 = domenica
     *
     * Ogni giorno può avere zero,
     * una o più fasce.
     */
    public function applicaOrarioMensile(int $anno, int $mese, array $orarioSettimanale): bool
    {
        if (!$this->isProprietarioAutenticato() || $anno < 2000 || $anno > 2100 || $mese < 1 || $mese > 12) {
            return false;
        }
        $orario = $this->normalizzaOrarioSettimanale($orarioSettimanale);
        if ($orario === null) {
            return false;
        }
        $primo = new DateTimeImmutable(sprintf('%04d-%02d-01', $anno, $mese));
        $ultimo = $primo->modify('last day of this month');
        $oggi = new DateTimeImmutable('today');
        if ($ultimo < $oggi) {
            return false;
        }
        $connection = FConnectionDB::connect();
        $connection->beginTransaction();
        try {
            FConnectionDB::bloccaCalendario();
            for ($giorno = $primo; $giorno <= $ultimo; $giorno = $giorno->modify('+1 day')) {
                $data = $giorno->format('Y-m-d');
                if ($giorno < $oggi || FDisponibilita::isEccezione($data)) {
                    continue;
                }
                $fasce = $orario[(int) $giorno->format('N')];
                if (!$this->prenotazioniAttiveCoperte($data, $fasce)) {
                    throw new RuntimeException('Il nuovo orario non copre tutti gli appuntamenti.');
                }
                $this->sostituisciFasce($data, $fasce);
            }
            $connection->commit();
            return true;
        } catch (Throwable $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            error_log('Lumèa, orario mensile: ' . $e->getMessage());
            return false;
        }
    }

    public function impostaEccezioneGiornaliera(string $data, array $fasce): bool
    {
        if (!$this->isProprietarioAutenticato() || !$this->dataValida($data) || $data < date('Y-m-d')) {
            return false;
        }
        $fasce = $this->normalizzaFasce($fasce);
        if ($fasce === null) {
            return false;
        }
        $connection = FConnectionDB::connect();
        $connection->beginTransaction();
        try {
            FConnectionDB::bloccaCalendario();
            if (!$this->prenotazioniAttiveCoperte($data, $fasce)) {
                throw new RuntimeException('Sono presenti prenotazioni incompatibili con il nuovo orario.');
            }
            $this->sostituisciFasce($data, $fasce);
            if (!FDisponibilita::storeEccezione($data)) {
                throw new RuntimeException('Impossibile memorizzare la giornata personalizzata.');
            }
            $connection->commit();
            return true;
        } catch (Throwable $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            error_log('Lumèa, orario giornaliero: ' . $e->getMessage());
            return false;
        }
    }

    public function chiudiGiorno(
        string $data
    ): bool {
        return
            $this->impostaEccezioneGiornaliera(
                $data,
                []
            );
    }


    public function giornoPersonalizzato(
        string $data
    ): bool {
        if (!$this->isProprietarioAutenticato()) {
            return false;
        }

        if (!$this->dataValida($data)) {
            return false;
        }

        return
            FDisponibilita::isEccezione(
                $data
            );
    }


    public function eccezioniPerPeriodo(
        string $dataInizio,
        string $dataFine
    ): array {
        if (!$this->isProprietarioAutenticato()) {
            return [];
        }

        if (
            !$this->dataValida(
                $dataInizio
            )
            ||
            !$this->dataValida(
                $dataFine
            )
            ||
            $dataInizio > $dataFine
        ) {
            return [];
        }

        return
            FDisponibilita
            ::loadEccezioniByPeriodo(
                $dataInizio,
                $dataFine
            );
    }


    public function aggiungiFascia(
        string $data,
        string $oraInizio,
        string $oraFine
    ): ?EDisponibilita {
        if (!$this->isProprietarioAutenticato()) {
            return null;
        }

        if (!$this->dataValida($data)) {
            return null;
        }

        $oraInizioNormalizzata =
            $this->normalizzaOra(
                $oraInizio
            );

        $oraFineNormalizzata =
            $this->normalizzaOra(
                $oraFine
            );

        if (
            $oraInizioNormalizzata === null
            ||
            $oraFineNormalizzata === null
        ) {
            return null;
        }

        if (
            $this->oraInMinuti(
                $oraInizioNormalizzata
            )
            >=
            $this->oraInMinuti(
                $oraFineNormalizzata
            )
        ) {
            return null;
        }

        if (
            $this->esisteSovrapposizioneFasce(
                $data,
                $oraInizioNormalizzata,
                $oraFineNormalizzata
            )
        ) {
            return null;
        }

        $connection =
            FConnectionDB::connect();

        $connection->beginTransaction();

        try {
            FConnectionDB::bloccaCalendario();

            $disponibilita =
                new EDisponibilita(
                    $data,
                    $oraInizioNormalizzata,
                    $oraFineNormalizzata
                );

            if (
                !FDisponibilita::store(
                    $disponibilita
                )
            ) {
                throw new RuntimeException(
                    'Errore nel salvataggio della disponibilità.'
                );
            }

            if (
                !FDisponibilita::storeEccezione(
                    $data
                )
            ) {
                throw new RuntimeException(
                    'Errore nel salvataggio dell\'eccezione.'
                );
            }

            $connection->commit();

            return $disponibilita;
        } catch (Throwable $e) {

            if (
                $connection->inTransaction()
            ) {
                $connection->rollBack();
            }

            return null;
        }
    }


    public function prenotazioniCoinvolteModifica(
        int $idDisponibilita,
        string $nuovaData,
        string $nuovaOraInizio,
        string $nuovaOraFine
    ): array {
        if (!$this->isProprietarioAutenticato()) {
            return [];
        }

        $fascia =
            FDisponibilita::load(
                $idDisponibilita
            );

        if ($fascia === null) {
            return [];
        }

        $oraInizioNormalizzata =
            $this->normalizzaOra(
                $nuovaOraInizio
            );

        $oraFineNormalizzata =
            $this->normalizzaOra(
                $nuovaOraFine
            );

        if (
            $oraInizioNormalizzata === null
            ||
            $oraFineNormalizzata === null
        ) {
            return [];
        }

        $dataOriginale =
            $fascia->getData();

        $prenotazioni =
            FPrenotazione::loadByData(
                $dataOriginale
            );

        $fasceRisultanti = [];

        $fasceOriginali =
            FDisponibilita::loadByData(
                $dataOriginale
            );

        foreach (
            $fasceOriginali as $fasciaEsistente
        ) {

            if (
                $fasciaEsistente->getId()
                ===
                $idDisponibilita
            ) {
                continue;
            }

            $fasceRisultanti[] = [
                'inizio' =>
                $fasciaEsistente
                    ->getOraInizio(),

                'fine' =>
                $fasciaEsistente
                    ->getOraFine()
            ];
        }

        if (
            $nuovaData
            ===
            $dataOriginale
        ) {
            $fasceRisultanti[] = [
                'inizio' =>
                $oraInizioNormalizzata,

                'fine' =>
                $oraFineNormalizzata
            ];
        }

        $coinvolte = [];

        foreach (
            $prenotazioni as $prenotazione
        ) {

            if (
                $prenotazione->getStato()
                !==
                'prenotata'
            ) {
                continue;
            }

            if (
                !$this
                    ->prenotazioneCopertaDaFasce(
                        $prenotazione,
                        $fasceRisultanti
                    )
            ) {
                $coinvolte[] =
                    $prenotazione;
            }
        }

        return $coinvolte;
    }


    public function modificaFascia(
        int $idDisponibilita,
        string $data,
        string $oraInizio,
        string $oraFine
    ): bool {
        if (!$this->isProprietarioAutenticato()) {
            return false;
        }

        if (!$this->dataValida($data)) {
            return false;
        }

        $fascia =
            FDisponibilita::load(
                $idDisponibilita
            );

        if ($fascia === null) {
            return false;
        }

        $dataOriginale =
            $fascia->getData();

        $oraInizioNormalizzata =
            $this->normalizzaOra(
                $oraInizio
            );

        $oraFineNormalizzata =
            $this->normalizzaOra(
                $oraFine
            );

        if (
            $oraInizioNormalizzata === null
            ||
            $oraFineNormalizzata === null
        ) {
            return false;
        }

        if (
            $this->oraInMinuti(
                $oraInizioNormalizzata
            )
            >=
            $this->oraInMinuti(
                $oraFineNormalizzata
            )
        ) {
            return false;
        }

        if (
            $this->esisteSovrapposizioneFasce(
                $data,
                $oraInizioNormalizzata,
                $oraFineNormalizzata,
                $idDisponibilita
            )
        ) {
            return false;
        }

        $prenotazioniCoinvolte =
            $this
                ->prenotazioniCoinvolteModifica(
                    $idDisponibilita,
                    $data,
                    $oraInizioNormalizzata,
                    $oraFineNormalizzata
                );

        if (
            count(
                $prenotazioniCoinvolte
            ) > 0
        ) {
            return false;
        }

        $connection =
            FConnectionDB::connect();

        $connection->beginTransaction();

        try {
            FConnectionDB::bloccaCalendario();

            $fascia->modifica(
                $data,
                $oraInizioNormalizzata,
                $oraFineNormalizzata
            );

            if (
                !FDisponibilita::update(
                    $fascia
                )
            ) {
                throw new RuntimeException(
                    'Errore nella modifica della disponibilità.'
                );
            }

            if (
                !FDisponibilita::storeEccezione(
                    $dataOriginale
                )
            ) {
                throw new RuntimeException(
                    'Errore nel salvataggio dell\'eccezione originale.'
                );
            }

            if (
                !FDisponibilita::storeEccezione(
                    $data
                )
            ) {
                throw new RuntimeException(
                    'Errore nel salvataggio dell\'eccezione.'
                );
            }

            $connection->commit();

            return true;
        } catch (Throwable $e) {

            if (
                $connection->inTransaction()
            ) {
                $connection->rollBack();
            }

            return false;
        }
    }


    public function prenotazioniCoinvolteEliminazione(
        int $idDisponibilita
    ): array {
        if (!$this->isProprietarioAutenticato()) {
            return [];
        }

        $fascia =
            FDisponibilita::load(
                $idDisponibilita
            );

        if ($fascia === null) {
            return [];
        }

        $data =
            $fascia->getData();

        $prenotazioni =
            FPrenotazione::loadByData(
                $data
            );

        $fasceRisultanti = [];

        $fasce =
            FDisponibilita::loadByData(
                $data
            );

        foreach (
            $fasce as $fasciaEsistente
        ) {

            if (
                $fasciaEsistente->getId()
                ===
                $idDisponibilita
            ) {
                continue;
            }

            $fasceRisultanti[] = [
                'inizio' =>
                $fasciaEsistente
                    ->getOraInizio(),

                'fine' =>
                $fasciaEsistente
                    ->getOraFine()
            ];
        }

        $coinvolte = [];

        foreach (
            $prenotazioni as $prenotazione
        ) {

            if (
                $prenotazione->getStato()
                !==
                'prenotata'
            ) {
                continue;
            }

            if (
                !$this
                    ->prenotazioneCopertaDaFasce(
                        $prenotazione,
                        $fasceRisultanti
                    )
            ) {
                $coinvolte[] =
                    $prenotazione;
            }
        }

        return $coinvolte;
    }


    public function eliminaFascia(
        int $idDisponibilita
    ): bool {
        if (!$this->isProprietarioAutenticato()) {
            return false;
        }

        $fascia =
            FDisponibilita::load(
                $idDisponibilita
            );

        if ($fascia === null) {
            return false;
        }

        $data =
            $fascia->getData();

        $prenotazioniCoinvolte =
            $this
                ->prenotazioniCoinvolteEliminazione(
                    $idDisponibilita
                );

        if (
            count(
                $prenotazioniCoinvolte
            ) > 0
        ) {
            return false;
        }

        $connection =
            FConnectionDB::connect();

        $connection->beginTransaction();

        try {
            FConnectionDB::bloccaCalendario();

            if (
                !FDisponibilita::delete(
                    $idDisponibilita
                )
            ) {
                throw new RuntimeException(
                    'Errore nell\'eliminazione della disponibilità.'
                );
            }

            if (
                !FDisponibilita::storeEccezione(
                    $data
                )
            ) {
                throw new RuntimeException(
                    'Errore nel salvataggio dell\'eccezione.'
                );
            }

            $connection->commit();

            return true;
        } catch (Throwable $e) {

            if (
                $connection->inTransaction()
            ) {
                $connection->rollBack();
            }

            return false;
        }
    }


    /*
     * =====================================================
     * SUPPORTO INPUT WEB
     * =====================================================
     */


    private function pulisciOrarioSettimanaleDaRequest(array $orario): ?array
    {
        $risultato = [];
        for ($giorno = 1; $giorno <= 7; $giorno++) {
            $dati = $orario[$giorno] ?? null;
            if (!is_array($dati) || !in_array($dati['stato'] ?? null, ['aperto', 'chiuso'], true)) {
                return null;
            }
            if ($dati['stato'] === 'chiuso') {
                $risultato[$giorno] = [];
                continue;
            }
            $fasce = $this->pulisciFasceDaRequest($dati['fasce'] ?? []);
            if ($fasce === null || $fasce === []) {
                return null;
            }
            $risultato[$giorno] = $fasce;
        }
        return $risultato;
    }

    private function pulisciFasceDaRequest(array $fasce): ?array
    {
        $risultato = [];
        foreach ($fasce as $fascia) {
            if (!is_array($fascia) || !is_string($fascia['inizio'] ?? '') || !is_string($fascia['fine'] ?? '')) {
                return null;
            }
            $inizio = trim($fascia['inizio'] ?? '');
            $fine = trim($fascia['fine'] ?? '');
            if ($inizio === '' && $fine === '') {
                continue;
            }
            if ($inizio === '' || $fine === '') {
                return null;
            }
            $risultato[] = ['inizio' => $inizio, 'fine' => $fine];
        }
        // L'ordine è quello dei campi Mattina/Pomeriggio: non va riordinato.
        return $this->normalizzaFasce($risultato);
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

        return
            $utente !== null
            &&
            $utente->isProprietario();
    }


    private function normalizzaOrarioSettimanale(
        array $orarioSettimanale
    ): ?array {
        $risultato = [];

        for (
            $giorno = 1;
            $giorno <= 7;
            $giorno++
        ) {

            $fasce =
                $orarioSettimanale[$giorno]
                ?? [];

            if (!is_array($fasce)) {
                return null;
            }

            $normalizzate =
                $this->normalizzaFasce(
                    $fasce
                );

            if ($normalizzate === null) {
                return null;
            }

            $risultato[$giorno] =
                $normalizzate;
        }

        return $risultato;
    }


    private function normalizzaFasce(array $fasce): ?array
    {
        $normalizzate = [];
        $finePrecedente = null;
        foreach ($fasce as $fascia) {
            if (!is_array($fascia) || !is_string($fascia['inizio'] ?? null) || !is_string($fascia['fine'] ?? null)) {
                return null;
            }
            $inizio = $this->normalizzaOra($fascia['inizio']);
            $fine = $this->normalizzaOra($fascia['fine']);
            if ($inizio === null || $fine === null || $inizio >= $fine
                || ($finePrecedente !== null && $inizio < $finePrecedente)) {
                return null;
            }
            $normalizzate[] = ['inizio' => $inizio, 'fine' => $fine];
            $finePrecedente = $fine;
        }
        return $normalizzate;
    }

    private function prenotazioniAttiveCoperte(
        string $data,
        array $fasce
    ): bool {
        $prenotazioni =
            FPrenotazione::loadByData(
                $data
            );

        foreach (
            $prenotazioni as $prenotazione
        ) {

            if (
                $prenotazione->getStato()
                !==
                'prenotata'
            ) {
                continue;
            }

            if (
                !$this
                    ->prenotazioneCopertaDaFasce(
                        $prenotazione,
                        $fasce
                    )
            ) {
                return false;
            }
        }

        return true;
    }


    private function esisteSovrapposizioneFasce(
        string $data,
        string $oraInizio,
        string $oraFine,
        ?int $idDaEscludere = null
    ): bool {
        $fasce =
            FDisponibilita::loadByData(
                $data
            );

        $nuovoInizio =
            $this->oraInMinuti(
                $oraInizio
            );

        $nuovaFine =
            $this->oraInMinuti(
                $oraFine
            );

        foreach (
            $fasce as $fascia
        ) {

            if (
                $idDaEscludere !== null
                &&
                $fascia->getId()
                ===
                $idDaEscludere
            ) {
                continue;
            }

            $inizioEsistente =
                $this->oraInMinuti(
                    $fascia->getOraInizio()
                );

            $fineEsistente =
                $this->oraInMinuti(
                    $fascia->getOraFine()
                );

            if (
                $nuovoInizio
                <
                $fineEsistente
                &&
                $nuovaFine
                >
                $inizioEsistente
            ) {
                return true;
            }
        }

        return false;
    }


    private function prenotazioneCopertaDaFasce(
        EPrenotazione $prenotazione,
        array $fasce
    ): bool {
        $inizioPrenotazione =
            $this->oraInMinuti(
                $prenotazione->getOra()
            );

        $finePrenotazione =
            $inizioPrenotazione
            +
            $prenotazione->getDurata();

        foreach (
            $fasce as $fascia
        ) {

            $inizioFascia =
                $this->oraInMinuti(
                    $fascia['inizio']
                );

            $fineFascia =
                $this->oraInMinuti(
                    $fascia['fine']
                );

            if (
                $inizioPrenotazione
                >=
                $inizioFascia
                &&
                $finePrenotazione
                <=
                $fineFascia
            ) {
                return true;
            }
        }

        return false;
    }


    private function dataValida(
        string $data
    ): bool {
        $dataOggetto =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $data
            );

        return
            $dataOggetto !== false
            &&
            $dataOggetto->format(
                'Y-m-d'
            )
            ===
            $data;
    }


    private function normalizzaOra(string $ora): ?string
    {
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::00)?$/D', $ora)) {
            return null;
        }
        return substr($ora, 0, 5) . ':00';
    }

    private function oraInMinuti(
        string $ora
    ): int {
        $parti =
            explode(
                ':',
                $ora
            );

        return ((int) $parti[0] * 60)
            +
            (int) $parti[1];
    }


    /*
     * =====================================================
     * MESSAGGI VIEW
     * =====================================================
     */


    private function getMessaggio(
        string $esito
    ): ?string {
        switch ($esito) {

            case 'mensile':
                return 'Orario mensile applicato correttamente.';

            case 'eccezione':
                return 'Orario della giornata modificato correttamente.';

            case 'chiusura':
                return 'Giornata chiusa correttamente.';

            default:
                return null;
        }
    }


    private function getErrore(
        string $errore
    ): ?string {
        switch ($errore) {

            case 'orario':
                return 'Controlla Mattina e Pomeriggio: ogni intervallo deve essere completo, con apertura prima della chiusura. Il pomeriggio non può iniziare prima della chiusura della mattina. Per i giorni senza orari scegli Chiuso.';

            case 'mensile':
                return 'Non è stato possibile applicare l\'orario mensile. Verifica anche le prenotazioni già presenti.';

            case 'data':
                return 'La data selezionata non è valida.';

            case 'eccezione':
                return 'Non è stato possibile modificare la giornata. Potrebbero essere presenti prenotazioni incompatibili con il nuovo orario.';

            case 'eccezione_vuota':
                return 'Per chiudere completamente la giornata usa il pulsante "Imposta come chiuso".';

            case 'chiusura':
                return 'Non è possibile chiudere la giornata perché sono presenti prenotazioni attive oppure la data non è valida.';

            default:
                return null;
        }
    }


    private function urlDisponibilita(
        int $anno,
        int $mese,
        string $parametro
    ): string {
        $url =
            'index.php?pagina=disponibilita';

        if (
            $anno >= 2000
            &&
            $anno <= 2100
        ) {
            $url .=
                '&anno='
                . $anno;
        }

        if (
            $mese >= 1
            &&
            $mese <= 12
        ) {
            $url .=
                '&mese='
                . $mese;
        }

        if ($parametro !== '') {
            $url .=
                '&'
                . $parametro;
        }

        return $url;
    }


    private function redirect(
        string $url
    ): void {
        VBase::redirect($url);

        exit;
    }
}
