<?php

require_once __DIR__ . '/../Foundation/FUtente.class.php';
require_once __DIR__ . '/../Foundation/FSessione.class.php';

class CAutenticazione
{
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

        $email = strtolower(
            trim($email)
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
}
