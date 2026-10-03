<?php

require_once __DIR__ . '/../Foundation/FSessione.class.php';
require_once __DIR__ . '/../Foundation/FUtente.class.php';
require_once __DIR__ . '/../Foundation/FPrenotazione.class.php';
require_once __DIR__ . '/../Foundation/FCoupon.class.php';
require_once __DIR__ . '/../Entity/EUtente.class.php';

class CGestioneFedelta
{
    public function situazioneFedeltaCliente(): ?array
    {
        $cliente =
            $this->getClienteAutenticato();

        if ($cliente === null) {
            return null;
        }

        $idCliente =
            $cliente->getId();

        if ($idCliente === null) {
            return null;
        }

        $completamenti =
            FPrenotazione::countCompletateByCliente(
                $idCliente
            );

        return [
            'cliente' =>
            $cliente,

            'appuntamenti_completati' =>
            $completamenti
        ];
    }

    public function couponCliente(): ?array
    {
        $cliente =
            $this->getClienteAutenticato();

        if ($cliente === null) {
            return null;
        }

        $idCliente =
            $cliente->getId();

        if ($idCliente === null) {
            return null;
        }

        return FCoupon::loadByCliente(
            $idCliente
        );
    }

    private function getClienteAutenticato(): ?EUtente
    {
        $idUtente =
            FSessione::getIdUtenteAutenticato();

        if ($idUtente === null) {
            return null;
        }

        $utente =
            FUtente::load(
                $idUtente
            );

        if (
            $utente === null
            ||
            !$utente->isCliente()
        ) {
            return null;
        }

        return $utente;
    }
}
