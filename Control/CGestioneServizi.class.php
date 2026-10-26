<?php

require_once __DIR__ . '/../View/VServiziProprietario.class.php';

require_once __DIR__ . '/../Foundation/FUtente.class.php';
require_once __DIR__ . '/../Foundation/FSessione.class.php';
require_once __DIR__ . '/../Foundation/FServizio.class.php';
require_once __DIR__ . '/../Entity/EServizio.class.php';

class CGestioneServizi
{
    private function richiediProprietario(): void
    {
        if (!$this->isProprietarioAutenticato()) {
            VBase::redirect('index.php');
            exit;
        }
    }

    public function mostraServizi(array $request = []): void
    {
        $this->richiediProprietario();

        $servizi = $this->elencoServizi();
        $eliminabili = [];

        foreach ($servizi as $servizio) {
            if ($servizio->getId() !== null) {
                $eliminabili[$servizio->getId()] =
                    FServizio::isEliminabile(
                        $servizio->getId()
                    );
            }
        }

        $serviziPerCategoria = ['Uomo' => [], 'Donna' => []];
        foreach ($servizi as $servizio) {
            $serviziPerCategoria[$servizio->getCategoria()][] = $servizio;
        }

        (new VServiziProprietario())->mostraServizi([
            'servizi' => $servizi,
            'servizi_per_categoria' => $serviziPerCategoria,
            'eliminabili' => $eliminabili,
            'messaggio' => $this->getMessaggioServizi(
                is_string($request['get']['esito'] ?? null) ? $request['get']['esito'] : ''
            ),
            'errore' => $request['errore_locale'] ?? null,
            'valori' => $request['post'] ?? []
        ]);
    }

    public function gestisciAggiuntaServizio(array $request): void
    {
        $this->richiediProprietario();

        $dati =
            $this->leggiDatiServizio(
                $request['post'] ?? []
            );

        $ok =
            $dati !== null
            &&
            $this->aggiungiServizio(
                ...$dati
            ) !== null;

        $this->esitoServizi(
            $request,
            $ok
        );
    }

    public function gestisciModificaServizio(array $request): void
    {
        $this->richiediProprietario();

        $dati =
            $this->leggiDatiServizio(
                $request['post'] ?? []
            );

        $ok =
            $dati !== null
            &&
            $this->modificaServizio(
                (int) (
                    $request['post']['id_servizio']
                    ?? 0
                ),
                ...$dati
            );

        $this->esitoServizi(
            $request,
            $ok
        );
    }

    public function gestisciDisattivazioneServizio(
        array $request
    ): void {
        $this->richiediProprietario();

        $this->esitoServizi(
            $request,
            $this->disattivaServizio(
                (int) (
                    $request['post']['id_servizio']
                    ?? 0
                )
            )
        );
    }

    public function gestisciRiattivazioneServizio(
        array $request
    ): void {
        $this->richiediProprietario();

        $this->esitoServizi(
            $request,
            $this->riattivaServizio(
                (int) (
                    $request['post']['id_servizio']
                    ?? 0
                )
            )
        );
    }

    public function gestisciEliminazioneServizio(
        array $request
    ): void {
        $this->richiediProprietario();

        $idServizio =
            (int) (
                $request['post']['id_servizio']
                ?? 0
            );

        if (
            $this->eliminaServizio(
                $idServizio
            )
        ) {
            VBase::redirect('index.php?pagina=servizi&esito=eliminato'
            );
            exit;
        }

        $request['errore_locale'] =
            'Il servizio non può essere eliminato perché è già collegato a prenotazioni o richieste in lista d\'attesa. Puoi disattivarlo per impedirne nuove prenotazioni.';

        $this->mostraServizi(
            $request
        );
    }

    private function leggiDatiServizio(
        array $post
    ): ?array {
        foreach (
            [
                'nome',
                'categoria',
                'prezzo',
                'durata'
            ] as $campo
        ) {
            if (
                !isset($post[$campo])
                ||
                !is_string(
                    $post[$campo]
                )
            ) {
                return null;
            }
        }

        $prezzo =
            str_replace(
                ',',
                '.',
                $post['prezzo']
            );

        if (
            !preg_match(
                '/^\d{1,3}(?:\.\d{1,2})?$/D',
                $prezzo
            )
            ||
            (float) $prezzo > 999.99
            ||
            !ctype_digit(
                $post['durata']
            )
            ||
            trim(
                $post['nome']
            ) === ''
        ) {
            return null;
        }

        return [
            trim(
                $post['nome']
            ),
            $post['categoria'],
            (float) $prezzo,
            (int) $post['durata']
        ];
    }

    private function esitoServizi(
        array $request,
        bool $ok
    ): void {
        if ($ok) {
            VBase::redirect('index.php?pagina=servizi&esito=salvato'
            );
            exit;
        }

        $request['errore_locale'] =
            'Servizio non salvato. Inserisci nome, categoria Uomo o Donna, prezzo tra 0 e 999,99 € e durata positiva multipla di 15 minuti.';

        $this->mostraServizi(
            $request
        );
    }

    private function getMessaggioServizi(
        string $esito
    ): ?string {
        switch ($esito) {
            case 'salvato':
                return 'Servizio aggiornato.';

            case 'eliminato':
                return 'Servizio eliminato definitivamente.';

            default:
                return null;
        }
    }

    public function elencoServizi(): array
    {
        if (
            !$this->isProprietarioAutenticato()
        ) {
            return [];
        }

        /*
         * Nell'area del proprietario
         * mostriamo sia i servizi attivi
         * sia quelli disattivati.
         */
        return FServizio::loadAll();
    }

    public function aggiungiServizio(
        string $nome,
        string $categoria,
        float $prezzo,
        int $durata
    ): ?EServizio {
        if (
            !$this->isProprietarioAutenticato()
        ) {
            return null;
        }

        /*
         * Lumèa prevede attualmente
         * solamente le categorie:
         *
         * - Uomo
         * - Donna
         */
        if (
            !$this->categoriaValida(
                $categoria
            )
        ) {
            return null;
        }

        try {
            /*
             * Il costruttore di EServizio
             * controlla anche la durata:
             *
             * durata > 0
             * durata multipla di 15.
             */
            $servizio =
                new EServizio(
                    $nome,
                    $categoria,
                    $prezzo,
                    $durata
                );

            $salvato =
                FServizio::store(
                    $servizio
                );

            if (!$salvato) {
                return null;
            }

            return $servizio;
        } catch (
            InvalidArgumentException $e
        ) {
            /*
             * Per esempio:
             * durata 22 minuti.
             */
            return null;
        }
    }

    public function modificaServizio(
        int $idServizio,
        string $nome,
        string $categoria,
        float $prezzo,
        int $durata
    ): bool {
        if (
            !$this->isProprietarioAutenticato()
        ) {
            return false;
        }

        $servizio =
            FServizio::load(
                $idServizio
            );

        if ($servizio === null) {
            return false;
        }

        if (
            !$this->categoriaValida(
                $categoria
            )
        ) {
            return false;
        }

        try {
            /*
             * Modifichiamo l'Entity
             * solamente in memoria.
             */
            $servizio->setNome(
                $nome
            );

            $servizio->setCategoria(
                $categoria
            );

            $servizio->setPrezzo(
                $prezzo
            );

            $servizio->setDurata(
                $durata
            );

            /*
             * Poi rendiamo persistenti
             * le modifiche.
             */
            return
                FServizio::update(
                    $servizio
                );
        } catch (
            InvalidArgumentException $e
        ) {
            return false;
        }
    }

    public function disattivaServizio(
        int $idServizio
    ): bool {
        if (
            !$this->isProprietarioAutenticato()
        ) {
            return false;
        }

        $servizio =
            FServizio::load(
                $idServizio
            );

        if ($servizio === null) {
            return false;
        }

        /*
         * Non cancelliamo fisicamente
         * il servizio.
         *
         * Lo rendiamo semplicemente
         * non prenotabile.
         */
        $servizio->disattiva();

        return
            FServizio::update(
                $servizio
            );
    }

    public function riattivaServizio(
        int $idServizio
    ): bool {
        if (
            !$this->isProprietarioAutenticato()
        ) {
            return false;
        }

        $servizio =
            FServizio::load(
                $idServizio
            );

        if ($servizio === null) {
            return false;
        }

        $servizio->attiva();

        return
            FServizio::update(
                $servizio
            );
    }

    public function eliminaServizio(
        int $idServizio
    ): bool {
        if (
            !$this->isProprietarioAutenticato()
        ) {
            return false;
        }

        if (
            !FServizio::isEliminabile(
                $idServizio
            )
        ) {
            return false;
        }

        return
            FServizio::delete(
                $idServizio
            );
    }

    private function isProprietarioAutenticato(): bool
    {
        $idUtente =
            FSessione
            ::getIdUtenteAutenticato();

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

    private function categoriaValida(
        string $categoria
    ): bool {
        return in_array(
            $categoria,
            [
                'Uomo',
                'Donna'
            ],
            true
        );
    }
}
