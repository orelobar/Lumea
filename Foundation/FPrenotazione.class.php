<?php

require_once __DIR__ . '/FConnectionDB.class.php';
require_once __DIR__ . '/FUtente.class.php';
require_once __DIR__ . '/FServizio.class.php';
require_once __DIR__ . '/../Entity/EPrenotazione.class.php';

class FPrenotazione
{
    public static function exist(int $id): bool
    {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT id
            FROM prenotazioni
            WHERE id = :id
        ";

        $statement = $connection->prepare($query);

        $statement->execute([
            ':id' => $id
        ]);

        $result = $statement->fetch();

        return $result !== false;
    }


    public static function load(int $id): ?EPrenotazione
    {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT *
            FROM prenotazioni
            WHERE id = :id
        ";

        $statement = $connection->prepare($query);

        $statement->execute([
            ':id' => $id
        ]);

        $row = $statement->fetch(
            PDO::FETCH_ASSOC
        );

        if ($row === false) {
            return null;
        }

        $cliente = FUtente::load(
            (int) $row['id_cliente']
        );

        $servizio = FServizio::load(
            (int) $row['id_servizio']
        );

        if ($cliente === null || $servizio === null) {
            return null;
        }

        return new EPrenotazione(
            $cliente,
            $servizio,
            $row['data'],
            $row['ora'],
            (int) $row['id'],
            $row['nome_servizio'],
            (int) $row['durata'],
            (float) $row['prezzo'],
            $row['stato']
        );
    }


    /** Da chiamare come prima lettura nella transazione del Control. */
    public static function loadPerAggiornamento(int $id): ?EPrenotazione
    {
        $connection = FConnectionDB::connect();
        if (!$connection->inTransaction()) {
            throw new LogicException('Il blocco richiede una transazione attiva.');
        }

        // Il JOIN blocca anche il cliente: serializza contatori e premi fedeltà
        // quando vengono chiusi contemporaneamente appuntamenti diversi.
        $statement = $connection->prepare('
            SELECT pr.id FROM prenotazioni pr
            JOIN utenti u ON u.id = pr.id_cliente
            WHERE pr.id = :id FOR UPDATE
        ');
        $statement->execute([':id' => $id]);
        return $statement->fetchColumn() === false ? null : self::load($id);
    }

    public static function store(
        EPrenotazione $prenotazione
    ): bool {
        if ($prenotazione->getCliente()->getId() === null) {
            return false;
        }

        if ($prenotazione->getServizio()->getId() === null) {
            return false;
        }

        $connection = FConnectionDB::connect();

        $query = "
            INSERT INTO prenotazioni (
                id_cliente,
                id_servizio,
                nome_servizio,
                data,
                ora,
                durata,
                prezzo,
                stato
            )
            VALUES (
                :id_cliente,
                :id_servizio,
                :nome_servizio,
                :data,
                :ora,
                :durata,
                :prezzo,
                :stato
            )
        ";

        $statement = $connection->prepare($query);

        $result = $statement->execute([
            ':id_cliente' =>
            $prenotazione->getCliente()->getId(),

            ':id_servizio' =>
            $prenotazione->getServizio()->getId(),

            ':nome_servizio' =>
            $prenotazione->getNomeServizio(),

            ':data' =>
            $prenotazione->getData(),

            ':ora' =>
            $prenotazione->getOra(),

            ':durata' =>
            $prenotazione->getDurata(),

            ':prezzo' =>
            $prenotazione->getPrezzo(),

            ':stato' =>
            $prenotazione->getStato()
        ]);

        if ($result) {
            $prenotazione->setId(
                (int) $connection->lastInsertId()
            );
        }

        return $result;
    }


    public static function update(
        EPrenotazione $prenotazione
    ): bool {
        if ($prenotazione->getId() === null) {
            return false;
        }

        if ($prenotazione->getCliente()->getId() === null) {
            return false;
        }

        if ($prenotazione->getServizio()->getId() === null) {
            return false;
        }

        $connection = FConnectionDB::connect();

        $query = "
            UPDATE prenotazioni
            SET
                id_cliente = :id_cliente,
                id_servizio = :id_servizio,
                nome_servizio = :nome_servizio,
                data = :data,
                ora = :ora,
                durata = :durata,
                prezzo = :prezzo,
                stato = :stato
            WHERE id = :id
        ";

        $statement = $connection->prepare($query);

        return $statement->execute([
            ':id_cliente' =>
            $prenotazione->getCliente()->getId(),

            ':id_servizio' =>
            $prenotazione->getServizio()->getId(),

            ':nome_servizio' =>
            $prenotazione->getNomeServizio(),

            ':data' =>
            $prenotazione->getData(),

            ':ora' =>
            $prenotazione->getOra(),

            ':durata' =>
            $prenotazione->getDurata(),

            ':prezzo' =>
            $prenotazione->getPrezzo(),

            ':stato' =>
            $prenotazione->getStato(),

            ':id' =>
            $prenotazione->getId()
        ]);
    }


    public static function delete(int $id): bool
    {
        $connection = FConnectionDB::connect();

        $query = "
            DELETE FROM prenotazioni
            WHERE id = :id
        ";

        $statement = $connection->prepare($query);

        return $statement->execute([
            ':id' => $id
        ]);
    }


    public static function loadByCliente(
        int $idCliente
    ): array {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT id
            FROM prenotazioni
            WHERE id_cliente = :id_cliente
            ORDER BY data DESC, ora DESC
        ";

        $statement = $connection->prepare($query);

        $statement->execute([
            ':id_cliente' => $idCliente
        ]);

        $rows = $statement->fetchAll(
            PDO::FETCH_ASSOC
        );

        $prenotazioni = [];

        foreach ($rows as $row) {
            $prenotazione = self::load(
                (int) $row['id']
            );

            if ($prenotazione !== null) {
                $prenotazioni[] = $prenotazione;
            }
        }

        return $prenotazioni;
    }


    public static function loadByData(
        string $data
    ): array {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT id
            FROM prenotazioni
            WHERE data = :data
            ORDER BY ora ASC
        ";

        $statement = $connection->prepare($query);

        $statement->execute([
            ':data' => $data
        ]);

        $rows = $statement->fetchAll(
            PDO::FETCH_ASSOC
        );

        $prenotazioni = [];

        foreach ($rows as $row) {
            $prenotazione = self::load(
                (int) $row['id']
            );

            if ($prenotazione !== null) {
                $prenotazioni[] = $prenotazione;
            }
        }

        return $prenotazioni;
    }


    public static function loadByPeriodo(
        string $dataInizio,
        string $dataFine
    ): array {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT id
            FROM prenotazioni
            WHERE data >= :data_inizio
            AND data <= :data_fine
            ORDER BY data ASC, ora ASC
        ";

        $statement = $connection->prepare($query);

        $statement->execute([
            ':data_inizio' => $dataInizio,
            ':data_fine' => $dataFine
        ]);

        $rows = $statement->fetchAll(
            PDO::FETCH_ASSOC
        );

        $prenotazioni = [];

        foreach ($rows as $row) {
            $prenotazione = self::load(
                (int) $row['id']
            );

            if ($prenotazione !== null) {
                $prenotazioni[] = $prenotazione;
            }
        }

        return $prenotazioni;
    }


    public static function countCompletateByCliente(
        int $idCliente
    ): int {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT COUNT(*) AS totale
            FROM prenotazioni
            WHERE id_cliente = :id_cliente
            AND stato = 'completata'
        ";

        $statement = $connection->prepare($query);

        $statement->execute([
            ':id_cliente' => $idCliente
        ]);

        $row = $statement->fetch(
            PDO::FETCH_ASSOC
        );

        return (int) $row['totale'];
    }


    public static function esisteSovrapposizione(
        string $data,
        string $oraInizio,
        int $durata
    ): bool {
        $prenotazioni = self::loadByData($data);

        $partiNuovoInizio = explode(
            ':',
            $oraInizio
        );

        $nuovoInizioMinuti =
            ((int) $partiNuovoInizio[0] * 60) +
            (int) $partiNuovoInizio[1];

        $nuovaFineMinuti =
            $nuovoInizioMinuti + $durata;

        foreach ($prenotazioni as $prenotazione) {
            if ($prenotazione->getStato() !== "prenotata") {
                continue;
            }

            $partiOraEsistente = explode(
                ':',
                $prenotazione->getOra()
            );

            $inizioEsistenteMinuti =
                ((int) $partiOraEsistente[0] * 60) +
                (int) $partiOraEsistente[1];

            $fineEsistenteMinuti =
                $inizioEsistenteMinuti +
                $prenotazione->getDurata();

            if (
                $nuovoInizioMinuti < $fineEsistenteMinuti &&
                $nuovaFineMinuti > $inizioEsistenteMinuti
            ) {
                return true;
            }
        }

        return false;
    }
}
