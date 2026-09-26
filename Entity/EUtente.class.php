<?php

class EUtente
{
    private ?int $id = null;
    private string $nome;
    private string $cognome;
    private string $email;
    private string $passwordHash;
    private string $ruolo;

    private int $noShowConsecutivi;
    private bool $prenotazioniOnlineAbilitate;

    public function __construct(
        string $nome,
        string $cognome,
        string $email,
        string $passwordHash,
        string $ruolo = "cliente",
        ?int $id = null,
        int $noShowConsecutivi = 0,
        bool $prenotazioniOnlineAbilitate = true
    ) {
        $this->id = $id;
        $this->nome = $nome;
        $this->cognome = $cognome;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->ruolo = $ruolo;

        $this->noShowConsecutivi = $noShowConsecutivi;
        $this->prenotazioniOnlineAbilitate =
            $prenotazioniOnlineAbilitate;
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

    public function getCognome(): string
    {
        return $this->cognome;
    }

    public function setCognome(string $cognome): void
    {
        $this->cognome = $cognome;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getRuolo(): string
    {
        return $this->ruolo;
    }

    public function isCliente(): bool
    {
        return $this->ruolo === "cliente";
    }

    public function isProprietario(): bool
    {
        return $this->ruolo === "proprietario";
    }

    public function getNoShowConsecutivi(): int
    {
        return $this->noShowConsecutivi;
    }

    public function isPrenotazioniOnlineAbilitate(): bool
    {
        return $this->prenotazioniOnlineAbilitate;
    }

    public function registraNoShow(): void
    {
        $this->noShowConsecutivi++;

        if ($this->noShowConsecutivi >= 3) {
            $this->prenotazioniOnlineAbilitate = false;
        }
    }

    public function resetNoShowConsecutivi(): void
    {
        $this->noShowConsecutivi = 0;
    }

    public function riabilitaPrenotazioniOnline(): void
    {
        $this->noShowConsecutivi = 0;
        $this->prenotazioniOnlineAbilitate = true;
    }
}
