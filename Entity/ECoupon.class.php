<?php

class ECoupon
{
    private ?int $id = null;
    private string $codice;
    private EUtente $cliente;
    private float $valoreIniziale;
    private float $creditoResiduo;
    private string $dataOraGenerazione;
    private string $stato;
    private int $traguardoCompletamenti;

    public function __construct(
        string $codice,
        EUtente $cliente,
        float $valoreIniziale,
        string $dataOraGenerazione,
        int $traguardoCompletamenti,
        ?int $id = null,
        ?float $creditoResiduo = null,
        string $stato = "disponibile"
    ) {
        $this->id = $id;
        $this->codice = $codice;
        $this->cliente = $cliente;
        $this->valoreIniziale = $valoreIniziale;

        $this->creditoResiduo = $creditoResiduo === null
            ? $valoreIniziale
            : $creditoResiduo;

        $this->dataOraGenerazione = $dataOraGenerazione;
        $this->stato = $stato;
        $this->traguardoCompletamenti = $traguardoCompletamenti;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCodice(): string
    {
        return $this->codice;
    }

    public function getCliente(): EUtente
    {
        return $this->cliente;
    }

    public function getValoreIniziale(): float
    {
        return $this->valoreIniziale;
    }

    public function getCreditoResiduo(): float
    {
        return $this->creditoResiduo;
    }

    public function getDataOraGenerazione(): string
    {
        return $this->dataOraGenerazione;
    }

    public function getStato(): string
    {
        return $this->stato;
    }

    public function getTraguardoCompletamenti(): int
    {
        return $this->traguardoCompletamenti;
    }

    public function isDisponibile(): bool
    {
        return $this->stato === "disponibile";
    }

    public function isRiservato(): bool
    {
        return $this->stato === "riservato";
    }

    public function isEsaurito(): bool
    {
        return $this->stato === "esaurito";
    }

    public function appartieneA(EUtente $cliente): bool
    {
        if (
            $this->cliente->getId() !== null &&
            $cliente->getId() !== null
        ) {
            return $this->cliente->getId() === $cliente->getId();
        }

        return $this->cliente === $cliente;
    }

    public function riserva(): void
    {
        if (!$this->isDisponibile()) {
            throw new LogicException(
                "Il coupon non è disponibile."
            );
        }

        $this->stato = "riservato";
    }

    public function libera(): void
    {
        if (!$this->isRiservato()) {
            throw new LogicException(
                "Il coupon non è riservato."
            );
        }

        if ($this->creditoResiduo <= 0) {
            $this->stato = "esaurito";
        } else {
            $this->stato = "disponibile";
        }
    }

    public function utilizza(float $importo): void
    {
        if (!$this->isRiservato()) {
            throw new LogicException(
                "Il coupon deve essere riservato prima di essere utilizzato."
            );
        }

        if ($importo <= 0) {
            throw new InvalidArgumentException(
                "L'importo utilizzato deve essere maggiore di zero."
            );
        }

        if ($importo > $this->creditoResiduo) {
            throw new InvalidArgumentException(
                "L'importo supera il credito residuo del coupon."
            );
        }

        $this->creditoResiduo -= $importo;

        if ($this->creditoResiduo <= 0) {
            $this->creditoResiduo = 0;
            $this->stato = "esaurito";
        } else {
            $this->stato = "disponibile";
        }
    }

    public function dimezzaCreditoResiduo(): void
    {
        if (!$this->isDisponibile()) {
            throw new LogicException(
                "La penalità può essere applicata solo a un coupon disponibile."
            );
        }

        $this->creditoResiduo = round(
            $this->creditoResiduo * 0.50,
            2
        );

        if ($this->creditoResiduo <= 0) {
            $this->creditoResiduo = 0;
            $this->stato = "esaurito";
        }
    }
}
