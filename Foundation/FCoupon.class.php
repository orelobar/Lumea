<?php

require_once __DIR__ . '/FConnectionDB.class.php';
require_once __DIR__ . '/FUtente.class.php';
require_once __DIR__ . '/../Entity/ECoupon.class.php';

class FCoupon
{
    public static function exist(int $id): bool
    {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT id
            FROM coupon
            WHERE id = :id
        ";

        $statement = $connection->prepare($query);

        $statement->execute([
            ':id' => $id
        ]);

        $result = $statement->fetch();

        return $result !== false;
    }

    public static function existByClienteAndTraguardo(int $idCliente, int $traguardoCompletamenti): bool
    {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT id
            FROM coupon
            WHERE id_cliente = :id_cliente
            AND traguardo_completamenti = :traguardo_completamenti
        ";

        $statement = $connection->prepare($query);

        $statement->execute([
            ':id_cliente' => $idCliente,
            ':traguardo_completamenti' => $traguardoCompletamenti
        ]);

        $result = $statement->fetch();

        return $result !== false;
    }

    public static function load(int $id): ?ECoupon
    {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT *
            FROM coupon
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

        if ($cliente === null) {
            return null;
        }

        return new ECoupon(
            $row['codice'],
            $cliente,
            (float) $row['valore_iniziale'],
            $row['data_ora_generazione'],
            (int) $row['traguardo_completamenti'],
            (int) $row['id'],
            (float) $row['credito_residuo'],
            $row['stato']
        );
    }

    public static function loadDisponibiliByCliente(int $idCliente): array
    {
        $cliente = FUtente::load($idCliente);

        if ($cliente === null) {
            return [];
        }

        $connection = FConnectionDB::connect();

        $query = "
            SELECT *
            FROM coupon
            WHERE id_cliente = :id_cliente
            AND stato = 'disponibile'
            AND credito_residuo > 0
            ORDER BY data_ora_generazione ASC
        ";

        $statement = $connection->prepare($query);

        $statement->execute([
            ':id_cliente' => $idCliente
        ]);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $couponDisponibili = [];

        foreach ($rows as $row) {
            $couponDisponibili[] = new ECoupon(
                $row['codice'],
                $cliente,
                (float) $row['valore_iniziale'],
                $row['data_ora_generazione'],
                (int) $row['traguardo_completamenti'],
                (int) $row['id'],
                (float) $row['credito_residuo'],
                $row['stato']
            );
        }

        return $couponDisponibili;
    }

    public static function store(ECoupon $coupon): bool
    {
        if ($coupon->getCliente()->getId() === null) {
            return false;
        }

        $connection = FConnectionDB::connect();

        $query = "
            INSERT INTO coupon (
                codice,
                id_cliente,
                valore_iniziale,
                credito_residuo,
                data_ora_generazione,
                stato,
                traguardo_completamenti
            )
            VALUES (
                :codice,
                :id_cliente,
                :valore_iniziale,
                :credito_residuo,
                :data_ora_generazione,
                :stato,
                :traguardo_completamenti
            )
        ";

        $statement = $connection->prepare($query);

        $result = $statement->execute([
            ':codice' => $coupon->getCodice(),
            ':id_cliente' => $coupon->getCliente()->getId(),
            ':valore_iniziale' => $coupon->getValoreIniziale(),
            ':credito_residuo' => $coupon->getCreditoResiduo(),
            ':data_ora_generazione' => $coupon->getDataOraGenerazione(),
            ':stato' => $coupon->getStato(),
            ':traguardo_completamenti' => $coupon->getTraguardoCompletamenti()
        ]);

        if ($result) {
            $coupon->setId((int) $connection->lastInsertId());
        }

        return $result;
    }

    public static function update(ECoupon $coupon): bool
    {
        if ($coupon->getId() === null) {
            return false;
        }

        if ($coupon->getCliente()->getId() === null) {
            return false;
        }

        $connection = FConnectionDB::connect();

        $query = "
            UPDATE coupon
            SET
                codice = :codice,
                id_cliente = :id_cliente,
                valore_iniziale = :valore_iniziale,
                credito_residuo = :credito_residuo,
                data_ora_generazione = :data_ora_generazione,
                stato = :stato,
                traguardo_completamenti = :traguardo_completamenti
            WHERE id = :id
        ";

        $statement = $connection->prepare($query);

        return $statement->execute([
            ':codice' => $coupon->getCodice(),
            ':id_cliente' => $coupon->getCliente()->getId(),
            ':valore_iniziale' => $coupon->getValoreIniziale(),
            ':credito_residuo' => $coupon->getCreditoResiduo(),
            ':data_ora_generazione' => $coupon->getDataOraGenerazione(),
            ':stato' => $coupon->getStato(),
            ':traguardo_completamenti' => $coupon->getTraguardoCompletamenti(),
            ':id' => $coupon->getId()
        ]);
    }

    public static function delete(int $id): bool
    {
        $connection = FConnectionDB::connect();

        $query = "
            DELETE FROM coupon
            WHERE id = :id
        ";

        $statement = $connection->prepare($query);

        return $statement->execute([
            ':id' => $id
        ]);
    }
}

