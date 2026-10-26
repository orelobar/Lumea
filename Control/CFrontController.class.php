<?php
require_once __DIR__ . '/CGestioneStatistiche.class.php';
require_once __DIR__ . '/CGestioneClienti.class.php';
require_once __DIR__ . '/CGestioneServizi.class.php';
require_once __DIR__ . '/CGestioneAgenda.class.php';

require_once __DIR__ . '/CAutenticazione.class.php';
require_once __DIR__ . '/CHomePubblica.class.php';
require_once __DIR__ . '/CRegistrazione.class.php';
require_once __DIR__ . '/CGestioneDashboard.class.php';
require_once __DIR__ . '/CGestionePrenotazione.class.php';
require_once __DIR__ . '/CGestioneAppuntamenti.class.php';
require_once __DIR__ . '/CGestioneListaAttesa.class.php';
require_once __DIR__ . '/CGestioneFedelta.class.php';
require_once __DIR__ . '/CGestioneSpese.class.php';
require_once __DIR__ . '/CGestioneDisponibilita.class.php';


class CFrontController
{
    /**
     * URL delle operazioni di sistema: metodo HTTP -> selettori già autorizzati.
     * Le classi e i metodi eseguibili restano quelli della lista chiusa in risolviRotta().
     * I POST HTML rappresentano creazioni o transizioni di stato; nessuna API JSON.
     */
    private const ROTTE_AUTODESCRITTIVE = [
        '~^sessione$~D' => [
            'GET' => ['login', null],
            'POST' => ['login', null],
        ],
        '~^sessione/uscita$~D' => [
            'POST' => [null, 'logout'],
        ],
        '~^clienti/registrazione$~D' => [
            'GET' => ['registrazione', null],
        ],
        '~^clienti$~D' => [
            'POST' => ['registrazione', null],
            'GET' => ['clienti', null],
        ],
        '~^clienti/(?<valore>[1-9][0-9]{0,9})$~D' => [
            'GET' => ['clienti', null, 'id_cliente'],
        ],
        '~^clienti/(?<valore>[1-9][0-9]{0,9})/riabilitazione$~D' => [
            'POST' => ['clienti', 'riabilita_cliente', 'id_cliente'],
        ],
        '~^dashboard$~D' => [
            'GET' => ['dashboard', null],
        ],
        '~^prenotazioni/nuova$~D' => [
            'GET' => ['prenota', null],
        ],
        '~^prenotazioni$~D' => [
            'GET' => ['prenotazioni', null],
            'POST' => ['prenota', 'conferma_prenotazione'],
        ],
        '~^prenotazioni/(?<valore>[1-9][0-9]{0,9})/annullamento$~D' => [
            'POST' => ['prenotazioni', 'annulla_prenotazione', 'id_prenotazione'],
        ],
        '~^prenotazioni/(?<valore>[1-9][0-9]{0,9})/ripetizione$~D' => [
            'GET' => ['prenota', 'ripeti', 'idPrenotazione'],
        ],
        '~^lista-attesa$~D' => [
            'GET' => ['lista_attesa', null],
            'POST' => ['lista_attesa', 'inserisci_attesa'],
        ],
        '~^lista-attesa/nuova$~D' => [
            'GET' => ['lista_attesa', 'nuova'],
        ],
        '~^lista-attesa/(?<valore>[1-9][0-9]{0,9})/annullamento$~D' => [
            'POST' => ['lista_attesa', 'annulla_attesa', 'id_richiesta'],
        ],
        '~^coupon$~D' => [
            'GET' => ['coupon', null],
        ],
        '~^spese$~D' => [
            'GET' => ['spese', null],
        ],
        '~^agenda$~D' => [
            'GET' => ['agenda', null],
        ],
        '~^agenda/(?<valore>[0-9]{4}-[0-9]{2}-[0-9]{2})$~D' => [
            'GET' => ['agenda', null, 'data'],
        ],
        '~^agenda/oggi$~D' => [
            'GET' => ['appuntamenti_oggi', null],
        ],
        '~^agenda/settimana$~D' => [
            'GET' => ['agenda_sette_giorni', null],
        ],
        '~^agenda/prenotazioni/(?<valore>[1-9][0-9]{0,9})/pagamento$~D' => [
            'POST' => ['agenda', 'chiudi_appuntamento', 'id_prenotazione'],
        ],
        '~^agenda/prenotazioni/(?<valore>[1-9][0-9]{0,9})/assenza$~D' => [
            'POST' => ['agenda', 'segna_assenza', 'id_prenotazione'],
        ],
        '~^agenda/prenotazioni/(?<valore>[1-9][0-9]{0,9})/annullamento$~D' => [
            'POST' => ['agenda', 'annulla_appuntamento', 'id_prenotazione'],
        ],
        '~^incassi/oggi$~D' => [
            'GET' => ['incassi_oggi', null],
        ],
        '~^servizi$~D' => [
            'GET' => ['servizi', null],
            'POST' => ['servizi', 'aggiungi_servizio'],
        ],
        '~^servizi/(?<valore>[1-9][0-9]{0,9})$~D' => [
            'POST' => ['servizi', 'modifica_servizio', 'id_servizio'],
        ],
        '~^servizi/(?<valore>[1-9][0-9]{0,9})/attivazione$~D' => [
            'POST' => ['servizi', 'riattiva_servizio', 'id_servizio'],
        ],
        '~^servizi/(?<valore>[1-9][0-9]{0,9})/disattivazione$~D' => [
            'POST' => ['servizi', 'disattiva_servizio', 'id_servizio'],
        ],
        '~^servizi/(?<valore>[1-9][0-9]{0,9})/eliminazione$~D' => [
            'POST' => ['servizi', 'elimina_servizio', 'id_servizio'],
        ],
        '~^disponibilita$~D' => [
            'GET' => ['disponibilita', null],
        ],
        '~^disponibilita/orari-mensili$~D' => [
            'POST' => ['disponibilita', 'applica_orario_mensile'],
        ],
        '~^disponibilita/eccezioni$~D' => [
            'POST' => ['disponibilita', 'imposta_eccezione'],
        ],
        '~^disponibilita/chiusure$~D' => [
            'POST' => ['disponibilita', 'chiudi_giorno'],
        ],
        '~^statistiche$~D' => [
            'GET' => ['statistiche', null],
        ],
    ];

    public function run(): void
    {
        $richiesta = $this->creaRichiesta();

        // Verifica prima del dispatch: anche login, registrazione e logout
        // sono POST. Nessun controller viene eseguito con un token invalido.
        if (($richiesta['errore_rotta'] ?? null) === 405
            || !in_array($richiesta['method'], ['GET', 'HEAD', 'POST'], true)) {
            header('Allow: ' . ($richiesta['metodi_consentiti'] ?? 'GET, HEAD, POST'));
            http_response_code(405);
            (new VBase())->mostraErroreRichiesta('Metodo di richiesta non consentito.');
            return;
        }

        if (($richiesta['errore_rotta'] ?? null) === 404) {
            http_response_code(404);
            (new VBase())->mostraErroreRichiesta('La pagina richiesta non esiste.');
            return;
        }

        if ($richiesta['method'] === 'POST'
            && !FSessione::verificaTokenCsrf($richiesta['post']['csrf_token'] ?? null)) {
            http_response_code(403);
            (new VBase())->mostraErroreRichiesta(
                'La richiesta non è valida o la sessione è scaduta. Riapri la pagina e riprova.'
            );
            return;
        }

        if ($richiesta['method'] !== 'POST'
            && ($richiesta['get']['azione'] ?? null) === 'logout') {
            header('Allow: POST');
            http_response_code(405);
            (new VBase())->mostraErroreRichiesta('Per uscire usa il pulsante del menu.');
            return;
        }

        // Una sessione non deve restare autenticata se l'utente
        // e' stato eliminato dal database (es. pulizia dati demo).
        CAutenticazione::verificaSessioneCorrente();

        [
            $classeControl,
            $metodoControl
        ] =
            $this->risolviRotta(
                $richiesta
            );

        $control =
            new $classeControl();

        $control->$metodoControl(
            $richiesta
        );
    }


    private function creaRichiesta(): array
    {
        $richiesta = [
            'method' =>
            strtoupper(
                $_SERVER['REQUEST_METHOD']
                    ?? 'GET'
            ),

            'get' =>
            $_GET,

            'post' =>
            $_POST
        ];

        return $this->normalizzaRottaAutodescrittiva($richiesta);
    }
    /** Traduce le URL nuove senza cambiare i controlli applicativi delle rotte storiche. */
    private function normalizzaRottaAutodescrittiva(array $richiesta): array
    {
        $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $base = rtrim(str_replace('\\', '/', dirname($script)), '/.');
        $percorso = parse_url($_SERVER['REQUEST_URI'] ?? $script, PHP_URL_PATH);

        // index.php e la directory principale conservano integralmente i vecchi parametri.
        if ($percorso === $script || $percorso === $base . '/' || $percorso === $base) {
            return $richiesta;
        }

        $richiesta['errore_rotta'] = 404;
        if (!is_string($percorso) || strpos($percorso, $base . '/') !== 0) {
            return $richiesta;
        }

        $risorsa = substr($percorso, strlen($base) + 1);
        // La tabella usa nomi ASCII: niente alias percent-encoded, segmenti vuoti o traversal.
        if (strpos($risorsa, '%') !== false || strpos($risorsa, '//') !== false) {
            return $richiesta;
        }
        $risorsa = rtrim($risorsa, '/');

        foreach (self::ROTTE_AUTODESCRITTIVE as $pattern => $operazioni) {
            if (preg_match($pattern, $risorsa, $parametri) !== 1) {
                continue;
            }

            $metodiConsentiti = array_keys($operazioni);
            if (isset($operazioni['GET'])) {
                array_splice($metodiConsentiti, array_search('GET', $metodiConsentiti, true) + 1, 0, ['HEAD']);
            }
            $richiesta['metodi_consentiti'] = implode(', ', $metodiConsentiti);
            $method = $richiesta['method'] === 'HEAD' ? 'GET' : $richiesta['method'];
            if (!isset($operazioni[$method])) {
                $richiesta['errore_rotta'] = 405;
                return $richiesta;
            }

            [$pagina, $azione] = $operazioni[$method];
            // URL e metodo decidono l'operazione: pagina/azione inviati dall'utente non la cambiano.
            unset($richiesta['get']['pagina'], $richiesta['get']['azione'], $richiesta['post']['azione']);
            if ($pagina !== null) {
                $richiesta['get']['pagina'] = $pagina;
            }
            $contenitore = $method === 'POST' ? 'post' : 'get';
            if ($azione !== null) {
                $richiesta[$contenitore]['azione'] = $azione;
            }
            if (isset($operazioni[$method][2])) {
                // L'identificatore del percorso prevale su un omonimo nel body o nella query.
                $campo = $operazioni[$method][2];
                $richiesta[$contenitore][$campo] = $parametri['valore'];
                if ($campo === 'data') {
                    // L'Agenda storica accetta anche tre componenti della data: qui decide il path.
                    unset($richiesta['get']['giorno'], $richiesta['get']['mese'], $richiesta['get']['anno']);
                }
            }
            $richiesta['method'] = $method;
            unset($richiesta['errore_rotta']);
            return $richiesta;
        }

        return $richiesta;
    }



    private function risolviRotta(
        array $richiesta
    ): array {
        $method =
            $richiesta['method']
            ?? 'GET';

        $get =
            $richiesta['get']
            ?? [];

        $post =
            $richiesta['post']
            ?? [];

        $pagina =
            $get['pagina']
            ?? null;

        $azioneGet =
            $get['azione']
            ?? null;

        $azionePost =
            $post['azione']
            ?? null;


        /*
         * =====================================================
         * LOGOUT
         * =====================================================
         */

        if ($method === 'POST' && $azionePost === 'logout') {
            return [
                CAutenticazione::class,
                'gestisciLogout'
            ];
        }


        /*
         * =====================================================
         * LOGIN
         * =====================================================
         */

        if ($pagina === 'login') {
            if ($method === 'POST') {
                return [
                    CAutenticazione::class,
                    'gestisciLogin'
                ];
            }

            return [
                CAutenticazione::class,
                'mostraLogin'
            ];
        }


        /*
         * Il form di login attuale invia
         * il POST direttamente a index.php.
         */
        if (
            $pagina === null
            &&
            $method === 'POST'
        ) {
            return [
                CAutenticazione::class,
                'gestisciLogin'
            ];
        }


        /*
         * =====================================================
         * REGISTRAZIONE
         * =====================================================
         */

        if ($pagina === 'registrazione') {
            if ($method === 'POST') {
                return [
                    CRegistrazione::class,
                    'gestisciRegistrazione'
                ];
            }

            return [
                CRegistrazione::class,
                'mostraRegistrazione'
            ];
        }


        /*
         * =====================================================
         * DASHBOARD AUTENTICATA
         * =====================================================
         */

        if ($pagina === 'dashboard') {
            return [
                CGestioneDashboard::class,
                'mostraDashboard'
            ];
        }


        /*
         * =====================================================
         * AREA PROPRIETARIO
         * =====================================================
         */

        $pagineProprietario = [
            'appuntamenti_oggi' => [
                CGestioneDashboard::class,
                'mostraAppuntamentiOggi'
            ],

            'incassi_oggi' => [
                CGestioneDashboard::class,
                'mostraIncassiOggi'
            ],

            'agenda' => [
                CGestioneAgenda::class,
                'mostraAgenda'
            ],

            'agenda_sette_giorni' => [
                CGestioneAgenda::class,
                'mostraAgendaSetteGiorni'
            ],

            'servizi' => [
                CGestioneServizi::class,
                'mostraServizi'
            ],

            'clienti' => [
                CGestioneClienti::class,
                'mostraClienti'
            ],

            'statistiche' => [
                CGestioneStatistiche::class,
                'mostraStatistiche'
            ]
        ];

        $operazioniProprietario = [
            'agenda' => [
                'chiudi_appuntamento' =>
                'gestisciChiusuraAppuntamento',

                'segna_assenza' =>
                'gestisciAssenza',

                'annulla_appuntamento' =>
                'gestisciAnnullamentoProprietario'
            ],

            'servizi' => [
                'aggiungi_servizio' =>
                'gestisciAggiuntaServizio',

                'modifica_servizio' =>
                'gestisciModificaServizio',

                'disattiva_servizio' =>
                'gestisciDisattivazioneServizio',

                'riattiva_servizio' =>
                'gestisciRiattivazioneServizio',

                'elimina_servizio' =>
                'gestisciEliminazioneServizio'
            ],

            'clienti' => [
                'riabilita_cliente' =>
                'gestisciRiabilitazioneCliente'
            ]
        ];

        if (
            is_string($pagina)
            &&
            isset(
                $pagineProprietario[$pagina]
            )
        ) {
            [
                $classe,
                $metodo
            ] =
                $pagineProprietario[$pagina];

            if ($method === 'POST') {
                $metodo =
                    is_string($azionePost)
                    ?
                    (
                        $operazioniProprietario[$pagina][$azionePost]
                        ?? null
                    )
                    :
                    null;

                if ($metodo === null) {
                    http_response_code(400);

                    return [
                        CGestioneDashboard::class,
                        'mostraDashboard'
                    ];
                }
            }

            return [
                $classe,
                $metodo
            ];
        }


        /*
         * =====================================================
         * DISPONIBILITA PROPRIETARIO
         * =====================================================
         */

        if ($pagina === 'disponibilita') {

            /*
             * Operazione:
             * applicazione dell'orario
             * settimanale a tutto il mese.
             */
            if (
                $method === 'POST'
                &&
                $azionePost
                === 'applica_orario_mensile'
            ) {
                return [
                    CGestioneDisponibilita::class,
                    'gestisciApplicazioneOrarioMensile'
                ];
            }


            /*
             * Operazione:
             * modifica dell'orario
             * di una singola giornata.
             */
            if (
                $method === 'POST'
                &&
                $azionePost
                === 'imposta_eccezione'
            ) {
                return [
                    CGestioneDisponibilita::class,
                    'gestisciEccezioneGiornaliera'
                ];
            }


            /*
             * Operazione:
             * chiusura completa
             * di una singola giornata.
             */
            if (
                $method === 'POST'
                &&
                $azionePost
                === 'chiudi_giorno'
            ) {
                return [
                    CGestioneDisponibilita::class,
                    'gestisciChiusuraGiorno'
                ];
            }


            /*
             * Operazione:
             * visualizzazione della gestione
             * disponibilità del proprietario.
             */
            return [
                CGestioneDisponibilita::class,
                'mostraDisponibilita'
            ];
        }


        /*
         * =====================================================
         * COUPON, FEDELTÀ E SPESE CLIENTE
         * =====================================================
         */

        if ($pagina === 'coupon') {
            return [
                CGestioneFedelta::class,
                'mostraCouponCliente'
            ];
        }

        if ($pagina === 'spese') {
            return [
                CGestioneSpese::class,
                'mostraSpeseCliente'
            ];
        }


        /*
         * =====================================================
         * LISTA D'ATTESA CLIENTE
         * =====================================================
         */

        if ($pagina === 'lista_attesa') {
            if ($method === 'POST' && $azionePost === 'annulla_attesa') {
                return [
                    CGestioneListaAttesa::class,
                    'gestisciAnnullamentoListaAttesa'
                ];
            }

            if ($method === 'POST' && $azionePost === 'inserisci_attesa') {
                return [
                    CGestioneListaAttesa::class,
                    'gestisciInserimentoListaAttesa'
                ];
            }

            if ($method === 'GET' && $azioneGet === 'nuova') {
                return [
                    CGestioneListaAttesa::class,
                    'mostraNuovaRichiestaCliente'
                ];
            }

            return [
                CGestioneListaAttesa::class,
                'mostraListaAttesaCliente'
            ];
        }


        /*
         * =====================================================
         * PRENOTAZIONE CLIENTE
         * =====================================================
         */

        if ($pagina === 'prenota') {

            /*
             * Operazione:
             * ripetizione di un servizio
             * dallo storico.
             */
            if (
                $method === 'GET'
                &&
                $azioneGet === 'ripeti'
            ) {
                return [
                    CGestioneAppuntamenti::class,
                    'gestisciRipetiServizio'
                ];
            }


            /*
             * Operazione:
             * conferma nuova prenotazione.
             */
            if (
                $method === 'POST'
                &&
                $azionePost
                === 'conferma_prenotazione'
            ) {
                return [
                    CGestionePrenotazione::class,
                    'gestisciConfermaPrenotazione'
                ];
            }


            /*
             * Operazione:
             * ingresso in lista d'attesa.
             */
            if (
                $method === 'POST'
                &&
                $azionePost
                === 'lista_attesa'
            ) {
                return [
                    CGestioneListaAttesa::class,
                    'gestisciInserimentoListaAttesa'
                ];
            }


            /*
             * Operazione:
             * visualizzazione del wizard
             * di prenotazione.
             */
            return [
                CGestionePrenotazione::class,
                'mostraPrenotazioneCliente'
            ];
        }


        /*
         * =====================================================
         * APPUNTAMENTI CLIENTE
         * =====================================================
         */

        if ($pagina === 'prenotazioni') {

            /*
             * Operazione:
             * annullamento appuntamento.
             */
            if (
                $method === 'POST'
                &&
                $azionePost
                === 'annulla_prenotazione'
            ) {
                return [
                    CGestioneAppuntamenti::class,
                    'gestisciAnnullamentoPrenotazione'
                ];
            }


            /*
             * Operazione:
             * annullamento richiesta
             * in lista d'attesa.
             */
            if (
                $method === 'POST'
                &&
                $azionePost
                === 'annulla_attesa'
            ) {
                return [
                    CGestioneListaAttesa::class,
                    'gestisciAnnullamentoListaAttesa'
                ];
            }


            /*
             * Operazione:
             * visualizzazione appuntamenti,
             * storico e lista d'attesa.
             */
            return [
                CGestioneAppuntamenti::class,
                'mostraPrenotazioniCliente'
            ];
        }


        /*
         * =====================================================
         * HOME PUBBLICA
         * =====================================================
         */

        return [
            CHomePubblica::class,
            'mostraHome'
        ];
    }
}
