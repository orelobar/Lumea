<?php

require_once __DIR__ . '/FConnectionDB.class.php';
require_once __DIR__ . '/../Entity/EUtente.class.php';

class FUtente
{
    public static function exist(int $id): bool
    {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT id
            FROM utenti
            WHERE id = :id
        ";

        $statement = $connection->prepare($query);

        $statement->execute([
            ':id' => $id
        ]);

        $result = $statement->fetch();

        return $result !== false;
    }


    public static function load(int $id): ?EUtente
    {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT *
            FROM utenti
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

        return new EUtente(
            $row['nome'],
            $row['cognome'],
            $row['email'],
            $row['telefono'],
            $row['password_hash'],
            $row['ruolo'],
            (int) $row['id'],
            (int) $row['no_show_consecutivi'],
            (bool) $row['prenotazioni_online_abilitate']
        );
    }


    public static function loadByEmail(
        string $email
    ): ?EUtente {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT *
            FROM utenti
            WHERE email = :email
        ";

        $statement = $connection->prepare($query);

        $statement->execute([
            ':email' => $email
        ]);

        $row = $statement->fetch(
            PDO::FETCH_ASSOC
        );

        if ($row === false) {
            return null;
        }

        return new EUtente(
            $row['nome'],
            $row['cognome'],
            $row['email'],
            $row['telefono'],
            $row['password_hash'],
            $row['ruolo'],
            (int) $row['id'],
            (int) $row['no_show_consecutivi'],
            (bool) $row['prenotazioni_online_abilitate']
        );
    }


    public static function loadByTelefono(
        string $telefono
    ): ?EUtente {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT *
            FROM utenti
            WHERE telefono = :telefono
        ";

        $statement = $connection->prepare($query);

        $statement->execute([
            ':telefono' => $telefono
        ]);

        $row = $statement->fetch(
            PDO::FETCH_ASSOC
        );

        if ($row === false) {
            return null;
        }

        return new EUtente(
            $row['nome'],
            $row['cognome'],
            $row['email'],
            $row['telefono'],
            $row['password_hash'],
            $row['ruolo'],
            (int) $row['id'],
            (int) $row['no_show_consecutivi'],
            (bool) $row['prenotazioni_online_abilitate']
        );
    }


    public static function store(
        EUtente $utente
    ): bool {
        $connection = FConnectionDB::connect();

        $query = "
            INSERT INTO utenti (
                nome,
                cognome,
                email,
                telefono,
                password_hash,
                ruolo,
                no_show_consecutivi,
                prenotazioni_online_abilitate
            )
            VALUES (
                :nome,
                :cognome,
                :email,
                :telefono,
                :password_hash,
                :ruolo,
                :no_show_consecutivi,
                :prenotazioni_online_abilitate
            )
        ";

        $statement = $connection->prepare($query);

        $result = $statement->execute([
            ':nome' =>
            $utente->getNome(),

            ':cognome' =>
            $utente->getCognome(),

            ':email' =>
            $utente->getEmail(),

            ':telefono' =>
            $utente->getTelefono(),

            ':password_hash' =>
            $utente->getPasswordHash(),

            ':ruolo' =>
            $utente->getRuolo(),

            ':no_show_consecutivi' =>
            $utente->getNoShowConsecutivi(),

            ':prenotazioni_online_abilitate' =>
            (int) $utente->isPrenotazioniOnlineAbilitate()
        ]);

        if ($result) {
            $utente->setId(
                (int) $connection->lastInsertId()
            );
        }

        return $result;
    }


    public static function update(
        EUtente $utente
    ): bool {
        if ($utente->getId() === null) {
            return false;
        }

        $connection = FConnectionDB::connect();

        $query = "
            UPDATE utenti
            SET
                nome = :nome,
                cognome = :cognome,
                email = :email,
                telefono = :telefono,
                password_hash = :password_hash,
                ruolo = :ruolo,
                no_show_consecutivi = :no_show_consecutivi,
                prenotazioni_online_abilitate =
                    :prenotazioni_online_abilitate
            WHERE id = :id
        ";

        $statement = $connection->prepare($query);

        return $statement->execute([
            ':nome' =>
            $utente->getNome(),

            ':cognome' =>
            $utente->getCognome(),

            ':email' =>
            $utente->getEmail(),

            ':telefono' =>
            $utente->getTelefono(),

            ':password_hash' =>
            $utente->getPasswordHash(),

            ':ruolo' =>
            $utente->getRuolo(),

            ':no_show_consecutivi' =>
            $utente->getNoShowConsecutivi(),

            ':prenotazioni_online_abilitate' =>
            (int) $utente->isPrenotazioniOnlineAbilitate(),

            ':id' =>
            $utente->getId()
        ]);
    }


    public static function delete(
        int $id
    ): bool {
        $connection = FConnectionDB::connect();

        $query = "
            DELETE FROM utenti
            WHERE id = :id
        ";

        $statement = $connection->prepare($query);

        return $statement->execute([
            ':id' => $id
        ]);
    }


    public static function loadClienti(): array
    {
        $connection = FConnectionDB::connect();

        $query = "
            SELECT id
            FROM utenti
            WHERE ruolo = 'cliente'
            ORDER BY cognome ASC, nome ASC
        ";

        $statement = $connection->prepare($query);

        $statement->execute();

        $rows = $statement->fetchAll(
            PDO::FETCH_ASSOC
        );

        $clienti = [];

        foreach ($rows as $row) {
            $cliente = self::load(
                (int) $row['id']
            );

            if ($cliente !== null) {
                $clienti[] = $cliente;
            }
        }

        return $clienti;
    }
}
