<?php

class FSessione
{
    public static function avvia(): void
    {
        if (
            session_status()
            === PHP_SESSION_ACTIVE
        ) {
            return;
        }

        if (!session_start()) {
            throw new RuntimeException(
                'Impossibile avviare la sessione.'
            );
        }
    }

    public static function impostaUtenteAutenticato(
        int $idUtente
    ): void {
        self::avvia();

        if ($idUtente <= 0) {
            throw new InvalidArgumentException(
                'Identificatore utente non valido.'
            );
        }

        if (self::isAutenticato()) {
            throw new LogicException(
                'È già presente un utente autenticato.'
            );
        }

        $_SESSION['id_utente'] = $idUtente;
    }

    public static function getIdUtenteAutenticato(): ?int
    {
        self::avvia();

        $idUtente =
            $_SESSION['id_utente'] ?? null;

        if (
            !is_int($idUtente)
            ||
            $idUtente <= 0
        ) {
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

        $nomeSessione = session_name();
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
}
