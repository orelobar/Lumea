<?php
require_once __DIR__ . '/VBase.class.php';

class VAgendaProprietario extends VBase
{
    public function mostraAgenda(array $dati): void
    {
        foreach ($dati as $nome => $valore) {
            $this->setDataIntoTemplate($nome, $valore);
        }
        $this->setDataIntoTemplate('fasceAgenda', $this->preparaFasce($dati));
        $this->setTemplate('agenda_proprietario.tpl');
    }

    /** Coordinate di presentazione: non genera slot né modifica prenotazioni. */
    private function preparaFasce(array $dati): array
    {
        $intervalli = [];
        foreach ($dati['fasce'] ?? [] as $fascia) {
            $intervalli[] = [$this->minuti($fascia->getOraInizio()), $this->minuti($fascia->getOraFine())];
        }
        // Conserva anche appuntamenti storici fuori dall'orario oggi salvato.
        foreach ($dati['righe'] as $riga) {
            $p = $riga['prenotazione'];
            $inizio = $this->minuti($p->getOra());
            $intervalli[] = [$inizio, $inizio + $p->getDurata()];
        }
        usort($intervalli, static fn($a, $b) => $a[0] <=> $b[0]);
        $uniti = [];
        foreach ($intervalli as [$inizio, $fine]) {
            if ($fine <= $inizio) continue;
            $ultimo = count($uniti) - 1;
            if ($ultimo >= 0 && $inizio <= $uniti[$ultimo][1]) {
                $uniti[$ultimo][1] = max($uniti[$ultimo][1], $fine);
            } else {
                $uniti[] = [$inizio, $fine];
            }
        }
        $fasce = [];
        foreach ($uniti as [$inizio, $fine]) {
            $righe = [];
            $fineColonne = [];
            foreach ($dati['righe'] as $riga) {
                $p = $riga['prenotazione'];
                $ora = $this->minuti($p->getOra());
                if ($ora < $inizio || $ora >= $fine) continue;
                $colonna = 0;
                while (isset($fineColonne[$colonna]) && $fineColonne[$colonna] > $ora) $colonna++;
                $fineColonne[$colonna] = $ora + $p->getDurata();
                $riga['offset'] = $ora - $inizio;
                $riga['durata_visuale'] = $p->getDurata();
                $riga['colonna'] = $colonna;
                $riga['ora_fine'] = $this->ora($ora + $p->getDurata());
                $righe[] = $riga;
            }
            $liberi = [];
            foreach ($dati['fasce'] ?? [] as $apertura) {
                $da = max($inizio, $this->minuti($apertura->getOraInizio()));
                $a = min($fine, $this->minuti($apertura->getOraFine()));
                if ($da >= $a) continue;
                $cursore = $da;
                foreach ($righe as $riga) {
                    $p = $riga['prenotazione'];
                    $inizioP = $this->minuti($p->getOra());
                    $fineP = $inizioP + $p->getDurata();
                    if ($fineP <= $cursore || $inizioP >= $a) continue;
                    if ($inizioP > $cursore) {
                        $liberi[] = ['offset' => $cursore - $inizio, 'durata' => $inizioP - $cursore,
                            'inizio' => $this->ora($cursore), 'fine' => $this->ora($inizioP)];
                    }
                    $cursore = max($cursore, min($a, $fineP));
                }
                if ($cursore < $a) {
                    $liberi[] = ['offset' => $cursore - $inizio, 'durata' => $a - $cursore,
                        'inizio' => $this->ora($cursore), 'fine' => $this->ora($a)];
                }
            }
            $tacche = [];
            for ($ora = $inizio; $ora < $fine; $ora += 30) {
                $tacche[] = ['ora' => $this->ora($ora), 'offset' => $ora - $inizio];
            }
            $tacche[] = ['ora' => $this->ora($fine), 'offset' => $fine - $inizio];
            $fasce[] = [
                'inizio' => $this->ora($inizio), 'fine' => $this->ora($fine),
                'liberi' => $liberi, 'minuti' => $fine - $inizio, 'tacche' => $tacche, 'righe' => $righe,
                'colonne' => max(1, count($fineColonne))
            ];
        }
        return $fasce;
    }

    private function minuti(string $ora): int
    {
        return (int) substr($ora, 0, 2) * 60 + (int) substr($ora, 3, 2);
    }

    private function ora(int $minuti): string
    {
        return sprintf('%02d:%02d', intdiv($minuti, 60), $minuti % 60);
    }
}
