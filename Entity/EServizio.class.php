<?php

class EServizio
{
    private ?int $id = null;
    private string $nome;
    private string $categoria;
    private float $prezzo;
    private int $durata;
    private bool $attivo;

    public function __construct(
        string $nome,
        string $categoria,
        float $prezzo,
        int $durata,
        ?int $id = null,
        bool $attivo = true
    ) {
        $this->id = $id;
        $this->nome = $nome;
        $this->categoria = $categoria;

        if (!is_finite($prezzo) || $prezzo < 0 || $prezzo > 999.99) {
            throw new InvalidArgumentException(
                "Il prezzo del servizio deve essere compreso tra 0 e 999,99 euro."
            );
        }

        $this->prezzo = round($prezzo, 2);

        if ($durata <= 0 || $durata % 15 !== 0) {
            throw new InvalidArgumentException(
                "La durata del servizio deve essere un multiplo di 15 minuti."
            );
        }

        $this->durata = $durata;
        $this->attivo = $attivo;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function setNome(string $nome): void
    {
        $this->nome = $nome;
    }

    public function getCategoria(): string
    {
        return $this->categoria;
    }

    public function setCategoria(string $categoria): void
    {
        $this->categoria = $categoria;
    }

    public function getPrezzo(): float
    {
        return $this->prezzo;
    }

    public function setPrezzo(float $prezzo): void
    {
        if (!is_finite($prezzo) || $prezzo < 0 || $prezzo > 999.99) {
            throw new InvalidArgumentException(
                "Il prezzo del servizio deve essere compreso tra 0 e 999,99 euro."
            );
        }

        $this->prezzo = round($prezzo, 2);
    }

    public function getDurata(): int
    {
        return $this->durata;
    }

    public function setDurata(int $durata): void
    {
        if ($durata <= 0 || $durata % 15 !== 0) {
            throw new InvalidArgumentException(
                "La durata del servizio deve essere un multiplo di 15 minuti."
            );
        }

        $this->durata = $durata;
    }

    public function isAttivo(): bool
    {
        return $this->attivo;
    }

    public function disattiva(): void
    {
        $this->attivo = false;
    }

    public function attiva(): void
    {
        $this->attivo = true;
    }
}
