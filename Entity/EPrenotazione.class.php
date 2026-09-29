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

    private ?ECoupon $couponApplicato;
    private float $scontoCoupon;
    private float $importoDaPagare;

    public function __construct(
        EUtente $cliente,
        EServizio $servizio,
        string $data,
        string $ora,
        ?int $id = null,
        ?string $nomeServizio = null,
        ?int $durata = null,
        ?float $prezzo = null,
        string $stato = "prenotata",
        ?ECoupon $couponApplicato = null,
        float $scontoCoupon = 0.0,
        ?float $importoDaPagare = null
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
        $this->couponApplicato = $couponApplicato;
        $this->scontoCoupon = round($scontoCoupon, 2);
        $this->importoDaPagare = round(
            $importoDaPagare ?? ($this->prezzo - $this->scontoCoupon),
            2
        );
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

    public function getCouponApplicato(): ?ECoupon
    {
        return $this->couponApplicato;
    }

    public function getScontoCoupon(): float
    {
        return $this->scontoCoupon;
    }

    public function getImportoDaPagare(): float
    {
        return $this->importoDaPagare;
    }

    public function haCouponApplicato(): bool
    {
        return $this->couponApplicato !== null;
    }

    public function applicaCoupon(ECoupon $coupon): bool
    {
        if ($this->stato !== "prenotata") {
            return false;
        }

        if ($this->couponApplicato !== null) {
            return false;
        }

        if (!$coupon->appartieneA($this->cliente)) {
            return false;
        }

        if (!$coupon->isDisponibile()) {
            return false;
        }

        $sconto = min(
            $this->prezzo,
            $coupon->getCreditoResiduo()
        );

        if ($sconto <= 0) {
            return false;
        }

        $coupon->riserva();

        $this->couponApplicato = $coupon;

        $this->scontoCoupon = round(
            $sconto,
            2
        );

        $this->importoDaPagare = round(
            $this->prezzo - $this->scontoCoupon,
            2
        );

        return true;
    }

    public function rimuoviCoupon(): ?ECoupon
    {
        if (
            $this->stato !== "prenotata" ||
            $this->couponApplicato === null
        ) {
            return null;
        }

        $coupon = $this->couponApplicato;

        $coupon->libera();

        $this->couponApplicato = null;
        $this->scontoCoupon = 0.0;
        $this->importoDaPagare = $this->prezzo;

        return $coupon;
    }

    public function completa(): void
    {
        if ($this->stato === "prenotata") {

            if ($this->couponApplicato !== null) {
                $this->couponApplicato->utilizza($this->scontoCoupon);
            }

            $this->stato = "completata";
            $this->cliente->resetNoShowConsecutivi();
        }
    }

    public function cancella(): void
    {
        if ($this->stato === "prenotata") {
            if ($this->couponApplicato !== null) {
                $this->rimuoviCoupon();
            }

            $this->stato = "cancellata";
        }
    }

    public function segnaNoShow(): void
    {
        if ($this->stato === "prenotata") {
            if ($this->couponApplicato !== null) {
                $this->rimuoviCoupon();
            }

            $this->stato = "no-show";
            $this->cliente->registraNoShow();
        }
    }
}
