<?php

class EDisponibilita
{
    private ?int $id = null;
    private string $data;
    private string $oraInizio;
    private string $oraFine;

    public function __construct(
        string $data,
        string $oraInizio,
        string $oraFine,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->data = $data;
        $this->oraInizio = $oraInizio;
        $this->oraFine = $oraFine;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getOraInizio(): string
    {
        return $this->oraInizio;
    }

    public function getOraFine(): string
    {
        return $this->oraFine;
    }

    public function modifica(
        string $data,
        string $oraInizio,
        string $oraFine
    ): void {
        $this->data = $data;
        $this->oraInizio = $oraInizio;
        $this->oraFine = $oraFine;
    }
}
