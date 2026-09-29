<?php

require_once __DIR__ . '/FConnectionDB.class.php';
require_once __DIR__ . '/FUtente.class.php';
require_once __DIR__ . '/FServizio.class.php';
require_once __DIR__ . '/FPrenotazione.class.php';
require_once __DIR__ . '/../Entity/EListaAttesa.class.php';

class FListaAttesa
{
    public static function exist(int $id): bool
    {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT id
            FROM lista_attesa
            WHERE id = :id
        ";

        $statement = $connection->prepare($query);

        $statement->execute([
            ':id' => $id
        ]);

        $result = $statement->fetch();

        return $result !== false;
    }

    public static function load(int $id): ?EListaAttesa
    {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT *
            FROM lista_attesa
            WHERE id = :id
        ";

        $statement = $connection->prepare($query);

        $statement->execute([
            ':id' => $id
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        $cliente = FUtente::load((int) $row['id_cliente']);
        $servizio = FServizio::load((int) $row['id_servizio']);

        if ($cliente === null || $servizio === null) {
            return null;
        }

        $prenotazioneAlternativa = null;

        if ($row['id_prenotazione_alternativa'] !== null) {
            $prenotazioneAlternativa = FPrenotazione::load((int) $row['id_prenotazione_alternativa']);

            if ($prenotazioneAlternativa === null) {
                return null;
            }
        }

        return new EListaAttesa(
            $cliente,
            $servizio,
            $row['data_desiderata'],
            $row['ora_desiderata'],
            $row['data_ora_inserimento'],
            $prenotazioneAlternativa,
            (int) $row['id'],
            $row['stato']
        );
    }

    public static function loadAttivaByCliente(int $idCliente): ?EListaAttesa
    {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT id
            FROM lista_attesa
            WHERE id_cliente = :id_cliente
            AND stato = 'attiva'
            ORDER BY data_ora_inserimento ASC
            LIMIT 1
        ";

        $statement = $connection->prepare($query);

        $statement->execute([
            ':id_cliente' => $idCliente
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return self::load((int) $row['id']);
    }

    public static function loadAttiveByServizioDataOra(int $idServizio, string $dataDesiderata, string $oraDesiderata): array
    {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT id
            FROM lista_attesa
            WHERE id_servizio = :id_servizio
            AND data_desiderata = :data_desiderata
            AND ora_desiderata = :ora_desiderata
            AND stato = 'attiva'
            ORDER BY data_ora_inserimento ASC
        ";

        $statement = $connection->prepare($query);

        $statement->execute([
            ':id_servizio' => $idServizio,
            ':data_desiderata' => $dataDesiderata,
            ':ora_desiderata' => $oraDesiderata
        ]);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $listaAttese = [];

        foreach ($rows as $row) {
            $listaAttesa = self::load((int) $row['id']);

            if ($listaAttesa !== null) {
                $listaAttese[] = $listaAttesa;
            }
        }

        return $listaAttese;
    }

    public static function store(EListaAttesa $listaAttesa): bool
    {
        if ($listaAttesa->getCliente()->getId() === null) {
            return false;
        }

        if ($listaAttesa->getServizio()->getId() === null) {
            return false;
        }

        $idPrenotazioneAlternativa = null;

        if ($listaAttesa->getPrenotazioneAlternativa() !== null) {
            if ($listaAttesa->getPrenotazioneAlternativa()->getId() === null) {
                return false;
            }

            $idPrenotazioneAlternativa = $listaAttesa->getPrenotazioneAlternativa()->getId();
        }

        $connection = FConnectionDB::connect();

        $query = "
            INSERT INTO lista_attesa (
                id_cliente,
                id_servizio,
                data_desiderata,
                ora_desiderata,
                data_ora_inserimento,
                stato,
                id_prenotazione_alternativa
            )
            VALUES (
                :id_cliente,
                :id_servizio,
                :data_desiderata,
                :ora_desiderata,
                :data_ora_inserimento,
                :stato,
                :id_prenotazione_alternativa
            )
        ";

        $statement = $connection->prepare($query);

        $result = $statement->execute([
            ':id_cliente' => $listaAttesa->getCliente()->getId(),
            ':id_servizio' => $listaAttesa->getServizio()->getId(),
            ':data_desiderata' => $listaAttesa->getDataDesiderata(),
            ':ora_desiderata' => $listaAttesa->getOraDesiderata(),
            ':data_ora_inserimento' => $listaAttesa->getDataOraInserimento(),
            ':stato' => $listaAttesa->getStato(),
            ':id_prenotazione_alternativa' => $idPrenotazioneAlternativa
        ]);

        if ($result) {
            $listaAttesa->setId((int) $connection->lastInsertId());
        }

        return $result;
    }

    public static function update(EListaAttesa $listaAttesa): bool
    {
        if ($listaAttesa->getId() === null) {
            return false;
        }

        if ($listaAttesa->getCliente()->getId() === null) {
            return false;
        }

        if ($listaAttesa->getServizio()->getId() === null) {
            return false;
        }

        $idPrenotazioneAlternativa = null;

        if ($listaAttesa->getPrenotazioneAlternativa() !== null) {
            if ($listaAttesa->getPrenotazioneAlternativa()->getId() === null) {
                return false;
            }

            $idPrenotazioneAlternativa = $listaAttesa->getPrenotazioneAlternativa()->getId();
        }

        $connection = FConnectionDB::connect();

        $query = "
            UPDATE lista_attesa
            SET
                id_cliente = :id_cliente,
                id_servizio = :id_servizio,
                data_desiderata = :data_desiderata,
                ora_desiderata = :ora_desiderata,
                data_ora_inserimento = :data_ora_inserimento,
                stato = :stato,
                id_prenotazione_alternativa = :id_prenotazione_alternativa
            WHERE id = :id
        ";

        $statement = $connection->prepare($query);

        return $statement->execute([
            ':id_cliente' => $listaAttesa->getCliente()->getId(),
            ':id_servizio' => $listaAttesa->getServizio()->getId(),
            ':data_desiderata' => $listaAttesa->getDataDesiderata(),
            ':ora_desiderata' => $listaAttesa->getOraDesiderata(),
            ':data_ora_inserimento' => $listaAttesa->getDataOraInserimento(),
            ':stato' => $listaAttesa->getStato(),
            ':id_prenotazione_alternativa' => $idPrenotazioneAlternativa,
            ':id' => $listaAttesa->getId()
        ]);
    }

    public static function delete(int $id): bool
    {
        $connection = FConnectionDB::connect();

        $query = "
            DELETE FROM lista_attesa
            WHERE id = :id
        ";

        $statement = $connection->prepare($query);

        return $statement->execute([
            ':id' => $id
        ]);
    }
}

