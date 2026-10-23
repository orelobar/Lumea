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
        ini_set('session.use_strict_mode', '1');

        // Il cookie non e' accessibile da JavaScript e usa SameSite=Lax.
        $parametri = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => self::DURATA_MASSIMA,
            'path' => $parametri['path'],
            'domain' => $parametri['domain'],
            'secure' => $parametri['secure'] || (
                !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
            ),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        if (!session_start()) {
            throw new RuntimeException(
                'Impossibile avviare la sessione.'
            );
        }
    }

    /** Token riutilizzabile nei form della stessa sessione, mai negli URL. */
    public static function tokenCsrf(): string
    {
        self::avvia();

        if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function verificaTokenCsrf($token): bool
    {
        self::avvia();
        $atteso = $_SESSION['csrf_token'] ?? null;

        return is_string($token)
            && is_string($atteso)
            && strlen($token) === 64
            && hash_equals($atteso, $token);
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

        // Nuovo ID dopo il login: impedisce di riutilizzare il vecchio ID.
        if (!session_regenerate_id(true)) {
            throw new RuntimeException('Impossibile aggiornare la sessione.');
        }

        // Il token anonimo non deve restare valido dopo il cambio di identità.
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

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

        setcookie($nomeSessione, '', [
            'expires' => time() - 3600,
            'path' => $parametriCookie['path'],
            'domain' => $parametriCookie['domain'],
            'secure' => $parametriCookie['secure'],
            'httponly' => $parametriCookie['httponly'],
            'samesite' => $parametriCookie['samesite'] ?: 'Lax'
        ]);
    }

    private static function aggiornaCookieSessione(
        int $durata
    ): void {
        $parametriCookie =
            session_get_cookie_params();

        setcookie(session_name(), session_id(), [
            'expires' => time() + $durata,
            'path' => $parametriCookie['path'],
            'domain' => $parametriCookie['domain'],
            'secure' => $parametriCookie['secure'],
            'httponly' => $parametriCookie['httponly'],
            'samesite' => $parametriCookie['samesite'] ?: 'Lax'
        ]);
    }
}
