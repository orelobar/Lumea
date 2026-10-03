<?php

require_once __DIR__ . '/../Foundation/FUtente.class.php';
require_once __DIR__ . '/../Foundation/FPrenotazione.class.php';
require_once __DIR__ . '/../Foundation/FSessione.class.php';

class CGestioneClienti
{
    public function elencoClienti(): ?array
    {
        if (!$this->isProprietarioAutenticato()) {
            return null;
        }

        return FUtente::loadClienti();
    }

    public function dettaglioCliente(
        int $idCliente
    ): ?array {
        if (!$this->isProprietarioAutenticato()) {
            return null;
        }

        $cliente =
            FUtente::load(
                $idCliente
            );

        if (
            $cliente === null
            ||
            !$cliente->isCliente()
        ) {
            return null;
        }

        $storico =
            FPrenotazione::loadByCliente(
                $idCliente
            );

        $ultimiAppuntamenti =
            array_slice(
                $storico,
                0,
                10
            );

        return [
            'cliente' =>
            $cliente,

            'ultimi_appuntamenti' =>
            $ultimiAppuntamenti,

            'totale_appuntamenti' =>
            count($storico)
        ];
    }

    public function storicoCliente(
        int $idCliente
    ): ?array {
        if (!$this->isProprietarioAutenticato()) {
            return null;
        }

        $cliente =
            FUtente::load(
                $idCliente
            );

        if (
            $cliente === null
            ||
            !$cliente->isCliente()
        ) {
            return null;
        }

        return [
            'cliente' =>
            $cliente,

            'appuntamenti' =>
            FPrenotazione::loadByCliente(
                $idCliente
            )
        ];
    }

    public function riabilitaPrenotazioniOnline(
        int $idCliente
    ): bool {
        if (!$this->isProprietarioAutenticato()) {
            return false;
        }

        $cliente =
            FUtente::load(
                $idCliente
            );

        if (
            $cliente === null
            ||
            !$cliente->isCliente()
        ) {
            return false;
        }

        if (
            $cliente
            ->isPrenotazioniOnlineAbilitate()
        ) {
            return false;
        }

        $cliente
            ->riabilitaPrenotazioniOnline();

        return FUtente::update(
            $cliente
        );
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

        if ($utente === null) {
            return false;
        }

        return $utente->isProprietario();
    }
}
