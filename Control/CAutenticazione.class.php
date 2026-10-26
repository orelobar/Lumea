<?php

require_once __DIR__ . '/../Foundation/FUtente.class.php';
require_once __DIR__ . '/../Foundation/FSessione.class.php';
require_once __DIR__ . '/../View/VAutenticazione.class.php';

class CAutenticazione
{
    /**
     * Prima del dispatch, invalida le sessioni di account eliminati.
     * Non si occupa direttamente della persistenza e non accede
     * a $_SESSION: usa soltanto FUtente e FSessione.
     */
    public static function verificaSessioneCorrente(): void
    {
        $idUtente = FSessione::getIdUtenteAutenticato();

        if ($idUtente !== null && !FUtente::exist($idUtente)) {
            FSessione::logout();
        }
    }

    public function mostraLogin(array $request = []): void
    {
        if (FSessione::isAutenticato()) {
            $this->redirect('index.php');
        }

        $view = new VAutenticazione();

        $view->mostraLogin();
    }


    public function gestisciLogin(array $request): void
    {
        if (FSessione::isAutenticato()) {
            $this->redirect('index.php');
        }

        $post =
            $request['post']
            ?? [];

        // I valori HTTP non testuali seguono la validazione dei campi vuoti.
        foreach (['email', 'password'] as $campo) {
            if (!is_string($post[$campo] ?? '')) {
                $post[$campo] = '';
            }
        }

        $risultato =
            $this->login(
                $post['email'] ?? '',
                $post['password'] ?? ''
            );

        if (!$risultato['successo']) {
            $view =
                new VAutenticazione();

            $view->mostraLogin(
                $risultato['errori'],
                $risultato['valori']
            );

            return;
        }

        $this->redirect(
            'index.php?pagina=dashboard'
        );
    }


    public function gestisciLogout(
        array $request = []
    ): void {
        $this->logout();

        $this->redirect(
            'index.php'
        );
    }


    public function login(
        string $email,
        string $password
    ): array {
        if (FSessione::isAutenticato()) {
            return [
                'successo' => false,
                'errori' => [
                    'generale' =>
                    'Hai già effettuato l\'accesso.'
                ],
                'valori' => [],
                'utente' => null
            ];
        }

        $email =
            strtolower(
                trim(
                    $email
                )
            );

        $valori = [
            'email' => $email
        ];

        $errori = [];

        if ($email === '') {
            $errori['email'] =
                'Inserisci il tuo indirizzo email.';
        } elseif (
            filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            $errori['email'] =
                'Inserisci un indirizzo email valido.';
        }

        if ($password === '') {
            $errori['password'] =
                'Inserisci la password.';
        }

        if ($errori !== []) {
            return [
                'successo' => false,
                'errori' => $errori,
                'valori' => $valori,
                'utente' => null
            ];
        }

        $utente =
            FUtente::loadByEmail(
                $email
            );

        if (
            $utente === null
            ||
            !password_verify(
                $password,
                $utente->getPasswordHash()
            )
        ) {
            return [
                'successo' => false,
                'errori' => [
                    'generale' =>
                    'Email o password non corretti.'
                ],
                'valori' => $valori,
                'utente' => null
            ];
        }

        $idUtente =
            $utente->getId();

        if ($idUtente === null) {
            throw new RuntimeException(
                'Utente autenticato senza identificatore.'
            );
        }

        FSessione::impostaUtenteAutenticato(
            $idUtente,
            $utente->getRuolo()
        );

        return [
            'successo' => true,
            'errori' => [],
            'valori' => [],
            'utente' => $utente
        ];
    }


    public function logout(): void
    {
        FSessione::logout();
    }


    private function redirect(
        string $url
    ): void {
        VBase::redirect($url);

        exit;
    }
}
