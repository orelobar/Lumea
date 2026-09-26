<?php

class EPagamento
{
    private ?int $id = null;

    private EPrenotazione $prenotazione;

    private float $importoPagato;

    private ?ECoupon $couponUtilizzato;
    private float $importoCouponUtilizzato;

    private string $dataOraPagamento;

    public function __construct(
        EPrenotazione $prenotazione,
        float $importoPagato,
        string $dataOraPagamento,
        ?int $id = null,
        ?ECoupon $couponUtilizzato = null,
        ?float $importoCouponUtilizzato = null
    ) {
        $this->id = $id;

        $this->prenotazione = $prenotazione;

        $this->importoPagato = round(
            $importoPagato,
            2
        );

        $this->couponUtilizzato =
            $couponUtilizzato ??
            $prenotazione->getCouponApplicato();

        $this->importoCouponUtilizzato = round(
            $importoCouponUtilizzato ??
                $prenotazione->getScontoCoupon(),
            2
        );

        $this->dataOraPagamento =
            $dataOraPagamento;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getPrenotazione(): EPrenotazione
    {
        return $this->prenotazione;
    }

    public function getImportoPagato(): float
    {
        return $this->importoPagato;
    }

    public function getCouponUtilizzato(): ?ECoupon
    {
        return $this->couponUtilizzato;
    }

    public function getImportoCouponUtilizzato(): float
    {
        return $this->importoCouponUtilizzato;
    }

    public function getDataOraPagamento(): string
    {
        return $this->dataOraPagamento;
    }
}
