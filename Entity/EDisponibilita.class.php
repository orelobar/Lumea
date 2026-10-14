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

    /**
     * Partenze ogni 30 minuti dall'apertura reale della fascia; un bordo
     * lasciato da un appuntamento genera nuove partenze ogni 30 minuti.
     * Il bordo destro permette di usare tutto il tempo prima del successivo.
     */
    public static function calcolaIniziSlot(
        int $inizioFascia,
        int $fineFascia,
        int $inizioBlocco,
        int $fineBlocco,
        int $durata
    ): array {
        if ($durata <= 0 || $durata % 15 !== 0 || $inizioBlocco >= $fineBlocco) {
            return [];
        }

        $inizi = [];
        for ($inizio = $inizioFascia; $inizio + $durata <= $fineFascia; $inizio += 30) {
            if ($inizio >= $inizioBlocco && $inizio + $durata <= $fineBlocco) {
                $inizi[$inizio] = $inizio;
            }
        }
        if ($inizioBlocco > $inizioFascia) {
            for ($inizio = $inizioBlocco; $inizio + $durata <= $fineBlocco; $inizio += 30) {
                $inizi[$inizio] = $inizio;
            }
        }
        if ($fineBlocco < $fineFascia) {
            for ($inizio = $fineBlocco - $durata; $inizio >= $inizioBlocco; $inizio -= 30) {
                $inizi[$inizio] = $inizio;
            }
        }
        ksort($inizi, SORT_NUMERIC);
        return array_values($inizi);
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
