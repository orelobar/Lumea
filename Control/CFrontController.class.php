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
    public function run(): void
    {
        // Una sessione non deve restare autenticata se l'utente
        // e' stato eliminato dal database (es. pulizia dati demo).
        CAutenticazione::verificaSessioneCorrente();

        $richiesta =
            $this->creaRichiesta();

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
        return [
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

        if ($azioneGet === 'logout') {
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
