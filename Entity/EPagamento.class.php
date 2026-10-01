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

        $this->importoPagato = round($importoPagato, 2);
        $this->couponUtilizzato = $couponUtilizzato;
        $this->importoCouponUtilizzato = round(
            $importoCouponUtilizzato ?? 0.0,
            2
        );
        if (!is_finite($importoPagato)
            || !is_finite($importoCouponUtilizzato ?? 0.0)
            || $importoPagato < 0 || ($importoCouponUtilizzato ?? 0.0) < 0
            || $this->importoCouponUtilizzato > $prenotazione->getPrezzo()
            || round($this->importoPagato + $this->importoCouponUtilizzato, 2)
                !== round($prenotazione->getPrezzo(), 2)
            || ($couponUtilizzato === null && $this->importoCouponUtilizzato !== 0.0)
            || ($couponUtilizzato !== null && !$couponUtilizzato->appartieneA($prenotazione->getCliente()))
        ) {
            throw new InvalidArgumentException('Importi o cliente del pagamento non coerenti.');
        }
        // Non confrontare con il residuo attuale: questo è uno storico di incasso.
        $this->dataOraPagamento = $dataOraPagamento;
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
