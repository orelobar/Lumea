<?php

class EPrenotazione
{
    private ?int $id = null;

    private EUtente $cliente;
    private EServizio $servizio;

    private string $nomeServizio;
    private string $data;
    private string $ora;

    private int $durata;
    private float $prezzo;

    private string $stato;

    public function __construct(
        EUtente $cliente,
        EServizio $servizio,
        string $data,
        string $ora,
        ?int $id = null,
        ?string $nomeServizio = null,
        ?int $durata = null,
        ?float $prezzo = null,
        string $stato = "prenotata"
    ) {
        $this->id = $id;

        $this->cliente = $cliente;
        $this->servizio = $servizio;

        $this->nomeServizio = $nomeServizio ?? $servizio->getNome();
        $this->data = $data;
        $this->ora = $ora;
        $this->durata = $durata ?? $servizio->getDurata();
        $this->prezzo = round($prezzo ?? $servizio->getPrezzo(), 2);
        $this->stato = $stato;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCliente(): EUtente
    {
        return $this->cliente;
    }

    public function getServizio(): EServizio
    {
        return $this->servizio;
    }

    public function getNomeServizio(): string
    {
        return $this->nomeServizio;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getOra(): string
    {
        return $this->ora;
    }

    public function getDurata(): int
    {
        return $this->durata;
    }

    public function getPrezzo(): float
    {
        return $this->prezzo;
    }

    public function getStato(): string
    {
        return $this->stato;
    }

    public function completa(): void
    {
        if ($this->stato === "prenotata") {
            $this->stato = "completata";
            $this->cliente->resetNoShowConsecutivi();
        }
    }

    public function cancella(): void
    {
        if ($this->stato === "prenotata") {
            $this->stato = "cancellata";
        }
    }

    public function segnaNoShow(): void
    {
        if ($this->stato === "prenotata") {
            $this->stato = "no-show";
            $this->cliente->registraNoShow();
        }
    }
}
