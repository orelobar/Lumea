<?php

require_once __DIR__ . '/VBase.class.php';

/** Presentazione delle notifiche: stesso Smarty e stessa separazione dati/template del sito. */
class VEmail extends VBase
{
    public function prepara(array $dati, string $testo): array
    {
        $titoli = [
            'conferma' => [
                'Il tuo appuntamento è confermato',
                'PRENOTAZIONE CONFERMATA',
                'La tua prenotazione è stata registrata correttamente.'
            ],
            'assegnazione' => [
                'Il tuo nuovo orario è disponibile',
                'LISTA D’ATTESA SODDISFATTA',
                'L’orario che desideravi si è liberato ed è stato assegnato a te.'
            ],
            'coupon' => [
                'Hai ricevuto un nuovo premio!',
                'IL TUO PREMIO FEDELTÀ',
                'Grazie per continuare a scegliere Lumèa. Hai raggiunto un nuovo traguardo e ricevuto un coupon.'
            ],
            'sospensione' => [
                'Informazioni sul tuo account',
                'AGGIORNAMENTO ACCOUNT',
                'Ti scriviamo per informarti di un aggiornamento alle prenotazioni online del tuo account.'
            ],
            'annullamento' => [
                'Aggiornamento sul tuo appuntamento',
                'APPUNTAMENTO ANNULLATO',
                'Il salone ha annullato il tuo appuntamento. Di seguito trovi i dettagli della prenotazione interessata.'
            ],
        ];
        if (!isset($titoli[$dati['tipo']])) {
            throw new InvalidArgumentException('Tipo di notifica non valido.');
        }
        [$titolo, $etichetta, $introduzione] = $titoli[$dati['tipo']];
        $righe = [];
        if (isset($dati['servizio'])) {
            $righe[] = ['etichetta' => 'Servizio', 'valore' => $dati['servizio']];
            $righe[] = ['etichetta' => $dati['tipo'] === 'assegnazione' ? 'Nuova data' : 'Data', 'valore' => self::dataItaliana($dati['data'])];
            $inizio = new DateTimeImmutable($dati['data'] . ' ' . $dati['ora']);
            $fine = $inizio->modify('+' . (int) $dati['durata'] . ' minutes');
            $righe[] = ['etichetta' => $dati['tipo'] === 'assegnazione' ? 'Nuovo orario' : 'Orario', 'valore' => $inizio->format('H:i') . ' – ' . $fine->format('H:i')];
            if ($dati['tipo'] !== 'annullamento') {
                $righe[] = ['etichetta' => 'Durata', 'valore' => $dati['durata'] . ' minuti'];
            }
            if ($dati['tipo'] === 'conferma') {
                $righe[] = ['etichetta' => 'Prezzo previsto', 'valore' => self::euro($dati['prezzo'])];
            }
            if ($dati['tipo'] === 'annullamento') {
                $righe[] = ['etichetta' => 'Stato', 'valore' => 'Appuntamento annullato'];
            }
        } elseif ($dati['tipo'] === 'coupon') {
            $righe = [
                ['etichetta' => 'Valore del coupon', 'valore' => self::euro($dati['valore'])],
                ['etichetta' => 'Credito disponibile', 'valore' => self::euro($dati['credito'])],
                ['etichetta' => 'Traguardo fedeltà', 'valore' => $dati['traguardo'] . ' appuntamenti completati'],
            ];
        } else {
            $righe = [
                ['etichetta' => 'Motivo', 'valore' => 'Tre mancate presentazioni consecutive'],
                ['etichetta' => 'Prenotazioni online', 'valore' => 'Temporaneamente sospese'],
                ['etichetta' => 'Credito coupon disponibile', 'valore' => 'Ridotto del 50%, se presente'],
            ];
        }
        $note = [
            'conferma' => 'Puoi annullare online fino a 48 ore prima dell’appuntamento.',
            'assegnazione' => 'Puoi controllare la nuova prenotazione nella tua area personale.',
            'coupon' => 'Il coupon è visibile nella tua area personale e può essere utilizzato in salone al momento del pagamento.',
            'sospensione' => 'Per effettuare una prenotazione, contatta direttamente il salone.',
            'annullamento' => 'Contatta il salone per concordare una nuova prenotazione.',
        ];
        $precedente = '';
        if (!empty($dati['precedente'])) {
            $precedente = self::dataItaliana($dati['precedente']['data']) . ' alle ' . substr($dati['precedente']['ora'], 0, 5);
        }
        $this->assign('email', array_merge($dati, [
            'titolo' => $titolo,
            'etichetta' => $etichetta,
            'introduzione' => $introduzione,
            'righe' => $righe,
            'nota' => $note[$dati['tipo']],
            'precedente' => $precedente,
        ]));
        return ['html' => $this->fetch('emails/notifica.tpl'), 'testo' => $testo];
    }

    private static function dataItaliana(string $data): string
    {
        $giorni = ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];
        $d = new DateTimeImmutable($data);
        return $giorni[(int) $d->format('w')] . ' ' . $d->format('d/m/Y');
    }

    private static function euro(float $valore): string
    {
        return '€ ' . number_format($valore, 2, ',', '.');
    }
}
