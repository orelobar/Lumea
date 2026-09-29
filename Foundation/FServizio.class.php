<?php

require_once __DIR__ . '/FConnectionDB.class.php';
require_once __DIR__ . '/../Entity/EServizio.class.php';

class FServizio
{
    public static function exist(int $id): bool
    {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT id
            FROM servizi
            WHERE id = :id
        ";

        $statement = $connection->prepare($query);

        $statement->execute([
            ':id' => $id
        ]);

        $result = $statement->fetch();

        return $result !== false;
    }


    public static function load(int $id): ?EServizio
    {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT *
            FROM servizi
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

        return new EServizio(
            $row['nome'],
            $row['categoria'],
            (float) $row['prezzo'],
            (int) $row['durata'],
            (int) $row['id'],
            (bool) $row['attivo']
        );
    }


    public static function store(EServizio $servizio): bool
    {
        $connection = FConnectionDB::connect();

        $query = "
            INSERT INTO servizi (
                nome,
                categoria,
                prezzo,
                durata,
                attivo
            )
            VALUES (
                :nome,
                :categoria,
                :prezzo,
                :durata,
                :attivo
            )
        ";

        $statement = $connection->prepare($query);

        $result = $statement->execute([
            ':nome' => $servizio->getNome(),
            ':categoria' => $servizio->getCategoria(),
            ':prezzo' => $servizio->getPrezzo(),
            ':durata' => $servizio->getDurata(),
            ':attivo' => $servizio->isAttivo()
        ]);

        if ($result) {
            $servizio->setId(
                (int) $connection->lastInsertId()
            );
        }

        return $result;
    }


    public static function update(EServizio $servizio): bool
    {
        if ($servizio->getId() === null) {
            return false;
        }

        $connection = FConnectionDB::connect();

        $query = "
            UPDATE servizi
            SET
                nome = :nome,
                categoria = :categoria,
                prezzo = :prezzo,
                durata = :durata,
                attivo = :attivo
            WHERE id = :id
        ";

        $statement = $connection->prepare($query);

        return $statement->execute([
            ':nome' => $servizio->getNome(),
            ':categoria' => $servizio->getCategoria(),
            ':prezzo' => $servizio->getPrezzo(),
            ':durata' => $servizio->getDurata(),
            ':attivo' => $servizio->isAttivo(),
            ':id' => $servizio->getId()
        ]);
    }


    public static function delete(int $id): bool
    {
        $connection = FConnectionDB::connect();

        $query = "
            DELETE FROM servizi
            WHERE id = :id
        ";

        $statement = $connection->prepare($query);

        return $statement->execute([
            ':id' => $id
        ]);
    }


    public static function loadAll(): array
    {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT id
            FROM servizi
            ORDER BY categoria ASC, nome ASC
        ";

        $statement = $connection->prepare($query);

        $statement->execute();

        $rows = $statement->fetchAll(
            PDO::FETCH_ASSOC
        );

        $servizi = [];

        foreach ($rows as $row) {
            $servizio = self::load(
                (int) $row['id']
            );

            if ($servizio !== null) {
                $servizi[] = $servizio;
            }
        }

        return $servizi;
    }


    public static function loadAttivi(): array
    {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT id
            FROM servizi
            WHERE attivo = 1
            ORDER BY categoria ASC, nome ASC
        ";

        $statement = $connection->prepare($query);

        $statement->execute();

        $rows = $statement->fetchAll(
            PDO::FETCH_ASSOC
        );

        $servizi = [];

        foreach ($rows as $row) {
            $servizio = self::load(
                (int) $row['id']
            );

            if ($servizio !== null) {
                $servizi[] = $servizio;
            }
        }

        return $servizi;
    }


    public static function loadAttiviByCategoria(
        string $categoria
    ): array {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT id
            FROM servizi
            WHERE attivo = 1
            AND categoria = :categoria
            ORDER BY nome ASC
        ";

        $statement = $connection->prepare($query);

        $statement->execute([
            ':categoria' => $categoria
        ]);

        $rows = $statement->fetchAll(
            PDO::FETCH_ASSOC
        );

        $servizi = [];

        foreach ($rows as $row) {
            $servizio = self::load(
                (int) $row['id']
            );

            if ($servizio !== null) {
                $servizi[] = $servizio;
            }
        }

        return $servizi;
    }
}
