<?php

require_once __DIR__ . '/FConnectionDB.class.php';
require_once __DIR__ . '/FPrenotazione.class.php';
require_once __DIR__ . '/FCoupon.class.php';
require_once __DIR__ . '/../Entity/EPagamento.class.php';

class FPagamento
{
    public static function exist(int $id): bool
    {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT id
            FROM pagamenti
            WHERE id = :id
        ";

        $statement = $connection->prepare($query);

        $statement->execute([
            ':id' => $id
        ]);

        $result = $statement->fetch();

        return $result !== false;
    }

    public static function load(int $id): ?EPagamento
    {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT *
            FROM pagamenti
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

        $prenotazione = FPrenotazione::load((int) $row['id_prenotazione']);

        if ($prenotazione === null) {
            return null;
        }

        $coupon = null;

        if ($row['id_coupon'] !== null) {
            $coupon = FCoupon::load((int) $row['id_coupon']);

            if ($coupon === null) {
                return null;
            }
        }

        return new EPagamento(
            $prenotazione,
            (float) $row['importo_pagato'],
            $row['data_ora_pagamento'],
            (int) $row['id'],
            $coupon,
            (float) $row['importo_coupon_utilizzato']
        );
    }

    public static function loadByPrenotazione(int $idPrenotazione): ?EPagamento
    {
        $statement = FConnectionDB::connect()->prepare(
            'SELECT id FROM pagamenti WHERE id_prenotazione = :id'
        );
        $statement->execute([':id' => $idPrenotazione]);
        $id = $statement->fetchColumn();
        return $id === false ? null : self::load((int) $id);
    }

    public static function loadByCliente(int $idCliente): array
    {
        $statement = FConnectionDB::connect()->prepare('
            SELECT p.id FROM pagamenti p
            JOIN prenotazioni pr ON pr.id = p.id_prenotazione
            WHERE pr.id_cliente = :id ORDER BY p.data_ora_pagamento DESC, p.id DESC
        ');
        $statement->execute([':id' => $idCliente]);
        return array_values(array_filter(array_map(
            static fn($id) => self::load((int) $id),
            $statement->fetchAll(PDO::FETCH_COLUMN)
        )));
    }

    public static function loadByClientePeriodo(int $idCliente, string $inizio, string $fine): array
    {
        $statement = FConnectionDB::connect()->prepare('
            SELECT p.id FROM pagamenti p
            JOIN prenotazioni pr ON pr.id = p.id_prenotazione
            WHERE pr.id_cliente = :cliente
            AND p.data_ora_pagamento >= :inizio AND p.data_ora_pagamento < :fine
            ORDER BY p.data_ora_pagamento DESC, p.id DESC
        ');
        $statement->execute([':cliente' => $idCliente, ':inizio' => $inizio, ':fine' => $fine]);
        return array_values(array_filter(array_map(
            static fn($id) => self::load((int) $id),
            $statement->fetchAll(PDO::FETCH_COLUMN)
        )));
    }

    public static function loadByPeriodo(string $dataInizio, string $dataFine): array
    {
        $statement = FConnectionDB::connect()->prepare('
            SELECT id FROM pagamenti
            WHERE data_ora_pagamento >= :inizio AND data_ora_pagamento < :fine
            ORDER BY data_ora_pagamento DESC, id DESC
        ');
        $statement->execute([':inizio' => $dataInizio, ':fine' => $dataFine]);
        return array_values(array_filter(array_map(
            static fn($id) => self::load((int) $id),
            $statement->fetchAll(PDO::FETCH_COLUMN)
        )));
    }

    public static function store(EPagamento $pagamento): bool
    {
        if ($pagamento->getPrenotazione()->getId() === null) {
            return false;
        }

        $idCoupon = null;

        if ($pagamento->getCouponUtilizzato() !== null) {
            if ($pagamento->getCouponUtilizzato()->getId() === null) {
                return false;
            }

            $idCoupon = $pagamento->getCouponUtilizzato()->getId();
        }

        $connection = FConnectionDB::connect();

        $query = "
            INSERT INTO pagamenti (
                id_prenotazione,
                importo_pagato,
                id_coupon,
                importo_coupon_utilizzato,
                data_ora_pagamento
            )
            VALUES (
                :id_prenotazione,
                :importo_pagato,
                :id_coupon,
                :importo_coupon_utilizzato,
                :data_ora_pagamento
            )
        ";

        $statement = $connection->prepare($query);

        $result = $statement->execute([
            ':id_prenotazione' => $pagamento->getPrenotazione()->getId(),
            ':importo_pagato' => $pagamento->getImportoPagato(),
            ':id_coupon' => $idCoupon,
            ':importo_coupon_utilizzato' => $pagamento->getImportoCouponUtilizzato(),
            ':data_ora_pagamento' => $pagamento->getDataOraPagamento()
        ]);

        if ($result) {
            $pagamento->setId((int) $connection->lastInsertId());
        }

        return $result;
    }

    public static function update(EPagamento $pagamento): bool
    {
        if ($pagamento->getId() === null) {
            return false;
        }

        if ($pagamento->getPrenotazione()->getId() === null) {
            return false;
        }

        $idCoupon = null;

        if ($pagamento->getCouponUtilizzato() !== null) {
            if ($pagamento->getCouponUtilizzato()->getId() === null) {
                return false;
            }

            $idCoupon = $pagamento->getCouponUtilizzato()->getId();
        }

        $connection = FConnectionDB::connect();

        $query = "
            UPDATE pagamenti
            SET
                id_prenotazione = :id_prenotazione,
                importo_pagato = :importo_pagato,
                id_coupon = :id_coupon,
                importo_coupon_utilizzato = :importo_coupon_utilizzato,
                data_ora_pagamento = :data_ora_pagamento
            WHERE id = :id
        ";

        $statement = $connection->prepare($query);

        return $statement->execute([
            ':id_prenotazione' => $pagamento->getPrenotazione()->getId(),
            ':importo_pagato' => $pagamento->getImportoPagato(),
            ':id_coupon' => $idCoupon,
            ':importo_coupon_utilizzato' => $pagamento->getImportoCouponUtilizzato(),
            ':data_ora_pagamento' => $pagamento->getDataOraPagamento(),
            ':id' => $pagamento->getId()
        ]);
    }

    public static function delete(int $id): bool
    {
        $connection = FConnectionDB::connect();

        $query = "
            DELETE FROM pagamenti
            WHERE id = :id
        ";

        $statement = $connection->prepare($query);

        return $statement->execute([
            ':id' => $id
        ]);
    }

    public static function totaleIncassiPeriodo(string $dataInizio, string $dataFine): float
    {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT SUM(importo_pagato) AS totale
            FROM pagamenti
            WHERE data_ora_pagamento >= :data_inizio
            AND data_ora_pagamento < :data_fine
        ";


        $statement = $connection->prepare($query);

        $statement->execute([
            ':data_inizio' => $dataInizio,
            ':data_fine' => $dataFine
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row['totale'] !== null ? (float) $row['totale'] : 0.0;
    }

    public static function totaleClientePeriodo(int $idCliente, string $dataInizio, string $dataFine): float
    {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT SUM(p.importo_pagato) AS totale
            FROM pagamenti p
            JOIN prenotazioni pr
            ON p.id_prenotazione = pr.id
            WHERE pr.id_cliente = :id_cliente
            AND p.data_ora_pagamento >= :data_inizio
            AND p.data_ora_pagamento < :data_fine
        ";

        $statement = $connection->prepare($query);

        $statement->execute([
            ':id_cliente' => $idCliente,
            ':data_inizio' => $dataInizio,
            ':data_fine' => $dataFine
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row['totale'] !== null ? (float) $row['totale'] : 0.0;
    }
}
