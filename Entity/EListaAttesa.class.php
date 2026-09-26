<?php

class EListaAttesa
{
    private ?int $id = null;

    private EUtente $cliente;
    private EServizio $servizio;

    private string $dataDesiderata;
    private string $oraDesiderata;

    private string $dataOraInserimento;
    private string $stato;

    private ?EPrenotazione $prenotazioneAlternativa;

    public function __construct(
        EUtente $cliente,
        EServizio $servizio,
        string $dataDesiderata,
        string $oraDesiderata,
        string $dataOraInserimento,
        ?EPrenotazione $prenotazioneAlternativa = null,
        ?int $id = null,
        string $stato = "attiva"
    ) {
        $this->id = $id;

        $this->cliente = $cliente;
        $this->servizio = $servizio;

        $this->dataDesiderata = $dataDesiderata;
        $this->oraDesiderata = $oraDesiderata;

        $this->dataOraInserimento =
            $dataOraInserimento;

        $this->prenotazioneAlternativa =
            $prenotazioneAlternativa;

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

    public function getDataDesiderata(): string
    {
        return $this->dataDesiderata;
    }

    public function getOraDesiderata(): string
    {
        return $this->oraDesiderata;
    }

    public function getDataOraInserimento(): string
    {
        return $this->dataOraInserimento;
    }

    public function getStato(): string
    {
        return $this->stato;
    }

    public function getPrenotazioneAlternativa(): ?EPrenotazione
    {
        return $this->prenotazioneAlternativa;
    }

    public function isAttiva(): bool
    {
        return $this->stato === "attiva";
    }

    public function collegaPrenotazioneAlternativa(
        EPrenotazione $prenotazione
    ): bool {
        if (!$this->isAttiva()) {
            return false;
        }

        if ($this->prenotazioneAlternativa !== null) {
            return false;
        }

        $this->prenotazioneAlternativa =
            $prenotazione;

        return true;
    }

    public function soddisfa(): void
    {
        if ($this->stato === "attiva") {
            $this->stato = "soddisfatta";
        }
    }

    public function annulla(): void
    {
        if ($this->stato === "attiva") {
            $this->stato = "annullata";
        }
    }

    public function scadi(): void
    {
        if ($this->stato === "attiva") {
            $this->stato = "scaduta";
        }
    }
}
