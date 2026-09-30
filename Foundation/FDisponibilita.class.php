<?php

require_once __DIR__ . '/FConnectionDB.class.php';
require_once __DIR__ . '/../Entity/EDisponibilita.class.php';

class FDisponibilita
{
    public static function exist(int $id): bool
    {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT id
            FROM disponibilita
            WHERE id = :id
        ";

        $statement = $connection->prepare($query);

        $statement->execute([
            ':id' => $id
        ]);

        $result = $statement->fetch();

        return $result !== false;
    }

    public static function load(int $id): ?EDisponibilita
    {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT *
            FROM disponibilita
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

        return new EDisponibilita(
            $row['data'],
            $row['ora_inizio'],
            $row['ora_fine'],
            (int) $row['id']
        );
    }

    public static function loadByData(string $data): array
    {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT *
            FROM disponibilita
            WHERE data = :data
            ORDER BY ora_inizio ASC
        ";

        $statement = $connection->prepare($query);

        $statement->execute([
            ':data' => $data
        ]);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $disponibilita = [];

        foreach ($rows as $row) {
            $disponibilita[] = new EDisponibilita(
                $row['data'],
                $row['ora_inizio'],
                $row['ora_fine'],
                (int) $row['id']
            );
        }

        return $disponibilita;
    }

    public static function loadFromDate(string $dataInizio): array
    {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT *
            FROM disponibilita
            WHERE data >= :data_inizio
            ORDER BY data ASC, ora_inizio ASC
        ";

        $statement = $connection->prepare($query);

        $statement->execute([
            ':data_inizio' => $dataInizio
        ]);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $disponibilita = [];

        foreach ($rows as $row) {
            $disponibilita[] = new EDisponibilita(
                $row['data'],
                $row['ora_inizio'],
                $row['ora_fine'],
                (int) $row['id']
            );
        }

        return $disponibilita;
    }

    public static function store(
        EDisponibilita $disponibilita
    ): bool {
        $connection = FConnectionDB::connect();

        $query = "
            INSERT INTO disponibilita (
                data,
                ora_inizio,
                ora_fine
            )
            VALUES (
                :data,
                :ora_inizio,
                :ora_fine
            )
        ";

        $statement = $connection->prepare($query);

        $result = $statement->execute([
            ':data' => $disponibilita->getData(),
            ':ora_inizio' => $disponibilita->getOraInizio(),
            ':ora_fine' => $disponibilita->getOraFine()
        ]);

        if ($result) {
            $disponibilita->setId(
                (int) $connection->lastInsertId()
            );
        }

        return $result;
    }

    public static function update(
        EDisponibilita $disponibilita
    ): bool {
        if ($disponibilita->getId() === null) {
            return false;
        }

        $connection = FConnectionDB::connect();

        $query = "
            UPDATE disponibilita
            SET
                data = :data,
                ora_inizio = :ora_inizio,
                ora_fine = :ora_fine
            WHERE id = :id
        ";

        $statement = $connection->prepare($query);

        return $statement->execute([
            ':data' => $disponibilita->getData(),
            ':ora_inizio' => $disponibilita->getOraInizio(),
            ':ora_fine' => $disponibilita->getOraFine(),
            ':id' => $disponibilita->getId()
        ]);
    }

    public static function delete(int $id): bool
    {
        $connection = FConnectionDB::connect();

        $query = "
            DELETE FROM disponibilita
            WHERE id = :id
        ";

        $statement = $connection->prepare($query);

        return $statement->execute([
            ':id' => $id
        ]);
    }
}
