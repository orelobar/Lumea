<?php

require_once __DIR__ . '/FConnectionDB.class.php';
require_once __DIR__ . '/../Entity/EDisponibilita.class.php';

class FDisponibilita
{
    /** Date aperte effettivamente configurate, incluse le giornate personalizzate. */
    public static function loadDateAperte(string $dataInizio, int $numero = 7): array
    {
        $statement = FConnectionDB::connect()->prepare(
            'SELECT DISTINCT data FROM disponibilita WHERE data >= :inizio AND ora_fine > ora_inizio ORDER BY data LIMIT :numero'
        );
        $statement->bindValue(':inizio', $dataInizio, PDO::PARAM_STR);
        $statement->bindValue(':numero', max(1, min(100, $numero)), PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

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


    public static function load(
        int $id
    ): ?EDisponibilita {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT *
            FROM disponibilita
            WHERE id = :id
        ";

        $statement = $connection->prepare(
            $query
        );

        $statement->execute([
            ':id' => $id
        ]);

        $row =
            $statement->fetch(
                PDO::FETCH_ASSOC
            );

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


    public static function loadByData(
        string $data
    ): array {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT *
            FROM disponibilita
            WHERE data = :data
            ORDER BY ora_inizio ASC
        ";

        $statement = $connection->prepare(
            $query
        );

        $statement->execute([
            ':data' => $data
        ]);

        $rows =
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );

        return self::creaListaDisponibilita(
            $rows
        );
    }


    public static function loadFromDate(
        string $dataInizio
    ): array {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT *
            FROM disponibilita
            WHERE data >= :data_inizio
            ORDER BY data ASC, ora_inizio ASC
        ";

        $statement = $connection->prepare(
            $query
        );

        $statement->execute([
            ':data_inizio' =>
            $dataInizio
        ]);

        $rows =
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );

        return self::creaListaDisponibilita(
            $rows
        );
    }


    public static function loadByPeriodo(
        string $dataInizio,
        string $dataFine
    ): array {
        $connection =
            FConnectionDB::connect();

        $query = "
            SELECT *
            FROM disponibilita
            WHERE data >= :data_inizio
              AND data <= :data_fine
            ORDER BY data ASC, ora_inizio ASC
        ";

        $statement =
            $connection->prepare(
                $query
            );

        $statement->execute([
            ':data_inizio' =>
            $dataInizio,

            ':data_fine' =>
            $dataFine
        ]);

        $rows =
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );

        return self::creaListaDisponibilita(
            $rows
        );
    }


    public static function store(
        EDisponibilita $disponibilita
    ): bool {
        $connection =
            FConnectionDB::connect();

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

        $statement =
            $connection->prepare(
                $query
            );

        $result =
            $statement->execute([
                ':data' =>
                $disponibilita->getData(),

                ':ora_inizio' =>
                $disponibilita->getOraInizio(),

                ':ora_fine' =>
                $disponibilita->getOraFine()
            ]);

        if ($result) {
            $disponibilita->setId(
                (int)
                $connection->lastInsertId()
            );
        }

        return $result;
    }


    public static function update(
        EDisponibilita $disponibilita
    ): bool {
        if (
            $disponibilita->getId()
            === null
        ) {
            return false;
        }

        $connection =
            FConnectionDB::connect();

        $query = "
            UPDATE disponibilita
            SET
                data = :data,
                ora_inizio = :ora_inizio,
                ora_fine = :ora_fine
            WHERE id = :id
        ";

        $statement =
            $connection->prepare(
                $query
            );

        return
            $statement->execute([
                ':data' =>
                $disponibilita->getData(),

                ':ora_inizio' =>
                $disponibilita->getOraInizio(),

                ':ora_fine' =>
                $disponibilita->getOraFine(),

                ':id' =>
                $disponibilita->getId()
            ]);
    }


    public static function delete(
        int $id
    ): bool {
        $connection =
            FConnectionDB::connect();

        $query = "
            DELETE FROM disponibilita
            WHERE id = :id
        ";

        $statement =
            $connection->prepare(
                $query
            );

        return
            $statement->execute([
                ':id' => $id
            ]);
    }


    /*
     * Elimina tutte le fasce di una
     * determinata giornata.
     *
     * Il Control deve avere già verificato
     * che l'operazione non lasci
     * prenotazioni attive senza copertura.
     */
    public static function deleteByData(
        string $data
    ): bool {
        $connection =
            FConnectionDB::connect();

        $query = "
            DELETE FROM disponibilita
            WHERE data = :data
        ";

        $statement =
            $connection->prepare(
                $query
            );

        return
            $statement->execute([
                ':data' => $data
            ]);
    }


    /*
     * =====================================================
     * ECCEZIONI GIORNALIERE
     * =====================================================
     *
     * Una data presente nella tabella
     * eccezioni_disponibilita non deve
     * essere sovrascritta quando viene
     * riapplicato l'orario mensile.
     */


    public static function isEccezione(
        string $data
    ): bool {
        $connection =
            FConnectionDB::connect();

        $query = "
            SELECT data
            FROM eccezioni_disponibilita
            WHERE data = :data
        ";

        $statement =
            $connection->prepare(
                $query
            );

        $statement->execute([
            ':data' => $data
        ]);

        return
            $statement->fetch()
            !== false;
    }


    public static function storeEccezione(
        string $data
    ): bool {
        $connection =
            FConnectionDB::connect();

        /*
         * La data è chiave primaria.
         * INSERT IGNORE rende l'operazione
         * idempotente: se l'eccezione
         * esiste già non viene duplicata.
         */
        $query = "
            INSERT IGNORE
            INTO eccezioni_disponibilita (
                data
            )
            VALUES (
                :data
            )
        ";

        $statement =
            $connection->prepare(
                $query
            );

        return
            $statement->execute([
                ':data' => $data
            ]);
    }


    public static function deleteEccezione(
        string $data
    ): bool {
        $connection =
            FConnectionDB::connect();

        $query = "
            DELETE
            FROM eccezioni_disponibilita
            WHERE data = :data
        ";

        $statement =
            $connection->prepare(
                $query
            );

        return
            $statement->execute([
                ':data' => $data
            ]);
    }


    public static function loadEccezioniByPeriodo(
        string $dataInizio,
        string $dataFine
    ): array {
        $connection =
            FConnectionDB::connect();

        $query = "
            SELECT data
            FROM eccezioni_disponibilita
            WHERE data >= :data_inizio
              AND data <= :data_fine
            ORDER BY data ASC
        ";

        $statement =
            $connection->prepare(
                $query
            );

        $statement->execute([
            ':data_inizio' =>
            $dataInizio,

            ':data_fine' =>
            $dataFine
        ]);

        $rows =
            $statement->fetchAll(
                PDO::FETCH_ASSOC
            );

        $date = [];

        foreach (
            $rows
            as $row
        ) {
            $date[] =
                $row['data'];
        }

        return $date;
    }


    /*
     * =====================================================
     * RICOSTRUZIONE ENTITY
     * =====================================================
     */

    private static function creaListaDisponibilita(
        array $rows
    ): array {
        $disponibilita = [];

        foreach (
            $rows
            as $row
        ) {
            $disponibilita[] =
                new EDisponibilita(
                    $row['data'],
                    $row['ora_inizio'],
                    $row['ora_fine'],
                    (int) $row['id']
                );
        }

        return $disponibilita;
    }
}
