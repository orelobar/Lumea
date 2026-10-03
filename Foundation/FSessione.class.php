<?php

class FSessione
{
    private const DURATA_CLIENTE =
    2 * 60 * 60;

    private const DURATA_PROPRIETARIO =
    12 * 60 * 60;

    private const DURATA_MASSIMA =
    self::DURATA_PROPRIETARIO;

    public static function avvia(): void
    {
        if (
            session_status()
            === PHP_SESSION_ACTIVE
        ) {
            return;
        }

        ini_set(
            'session.gc_maxlifetime',
            (string) self::DURATA_MASSIMA
        );

        session_set_cookie_params(
            self::DURATA_MASSIMA
        );

        if (!session_start()) {
            throw new RuntimeException(
                'Impossibile avviare la sessione.'
            );
        }
    }

    public static function impostaUtenteAutenticato(
        int $idUtente,
        string $ruolo = 'cliente'
    ): void {
        self::avvia();

        if ($idUtente <= 0) {
            throw new InvalidArgumentException(
                'Identificatore utente non valido.'
            );
        }

        if (
            $ruolo !== 'cliente'
            &&
            $ruolo !== 'proprietario'
        ) {
            throw new InvalidArgumentException(
                'Ruolo utente non valido.'
            );
        }

        if (self::isAutenticato()) {
            throw new LogicException(
                'È già presente un utente autenticato.'
            );
        }

        self::avvia();

        $durata =
            $ruolo === 'proprietario'
            ? self::DURATA_PROPRIETARIO
            : self::DURATA_CLIENTE;

        $_SESSION['id_utente'] =
            $idUtente;

        $_SESSION['scadenza_autenticazione'] =
            time() + $durata;

        self::aggiornaCookieSessione(
            $durata
        );
    }

    public static function getIdUtenteAutenticato(): ?int
    {
        self::avvia();

        $idUtente =
            $_SESSION['id_utente']
            ?? null;

        $scadenza =
            $_SESSION['scadenza_autenticazione']
            ?? null;

        if (
            $idUtente === null
            &&
            $scadenza === null
        ) {
            return null;
        }

        if (
            !is_int($idUtente)
            ||
            $idUtente <= 0
            ||
            !is_int($scadenza)
            ||
            $scadenza <= time()
        ) {
            self::logout();

            return null;
        }

        return $idUtente;
    }

    public static function isAutenticato(): bool
    {
        return
            self::getIdUtenteAutenticato()
            !== null;
    }

    public static function logout(): void
    {
        self::avvia();

        $nomeSessione =
            session_name();

        $parametriCookie =
            session_get_cookie_params();

        session_unset();

        session_destroy();

        setcookie(
            $nomeSessione,
            '',
            time() - 3600,
            $parametriCookie['path'],
            $parametriCookie['domain'],
            $parametriCookie['secure'],
            $parametriCookie['httponly']
        );
    }

    private static function aggiornaCookieSessione(
        int $durata
    ): void {
        $parametriCookie =
            session_get_cookie_params();

        setcookie(
            session_name(),
            session_id(),
            time() + $durata,
            $parametriCookie['path'],
            $parametriCookie['domain'],
            $parametriCookie['secure'],
            $parametriCookie['httponly']
        );
    }
}
