<?php

require_once __DIR__ . '/../Entity/EPrenotazione.class.php';
require_once __DIR__ . '/../Entity/ECoupon.class.php';
require_once __DIR__ . '/../Entity/EUtente.class.php';
require_once __DIR__ . '/../View/VEmail.class.php';
require_once __DIR__ . '/../Foundation/FEmail.class.php';

/** Coordina i cinque eventi con la presentazione e il trasporto delle notifiche. */
class CGestioneNotifiche
{
    public static function confermaPrenotazione(
        EPrenotazione $prenotazione
    ): bool {
        $cliente =
            $prenotazione->getCliente();

        $oggetto =
            'Conferma prenotazione Lumèa';

        $testo =
            'Ciao '
            . $cliente->getNome()
            . ",\n\n"
            . "la tua prenotazione è confermata.\n\n"
            . 'Servizio: '
            . $prenotazione->getNomeServizio()
            . "\n"
            . 'Data: '
            . self::formattaData(
                $prenotazione->getData()
            )
            . "\n"
            . 'Ora: '
            . self::formattaOra(
                $prenotazione->getOra()
            )
            . "\n"
            . 'Durata: '
            . $prenotazione->getDurata()
            . " minuti\n"
            . 'Prezzo: '
            . self::formattaEuro(
                $prenotazione->getPrezzo()
            )
            . "\n\n"
            . "Puoi annullare online fino a 48 ore prima dell'appuntamento.\n\n"
            . 'Lumèa';

        return self::invia(
            $cliente->getEmail(),
            $oggetto,
            $testo,
            self::datiAppuntamento($prenotazione, 'conferma')
        );
    }

    public static function assegnazioneListaAttesa(
        EPrenotazione $prenotazione,
        ?EPrenotazione $prenotazioneSostituita = null
    ): bool {
        $cliente = $prenotazione->getCliente();
        $testoSostituzione = $prenotazioneSostituita === null
            ? ''
            : "\nIl precedente appuntamento del "
                . self::formattaData($prenotazioneSostituita->getData())
                . ' alle '
                . self::formattaOra($prenotazioneSostituita->getOra())
                . " è stato sostituito.\n";

        $testo = 'Ciao '
            . $cliente->getNome()
            . ",\n\nl'orario richiesto in lista d'attesa si è liberato ed è stato assegnato a te.\n\n"
            . 'Servizio: ' . $prenotazione->getNomeServizio() . "\n"
            . 'Data: ' . self::formattaData($prenotazione->getData()) . "\n"
            . 'Ora: ' . self::formattaOra($prenotazione->getOra()) . "\n"
            . 'Durata: ' . $prenotazione->getDurata() . " minuti\n"
            . $testoSostituzione
            . "\nPuoi controllare la prenotazione nella tua area personale.\n\n"
            . 'Lumèa';

        return self::invia(
            $cliente->getEmail(),
            'Orario assegnato dalla lista d’attesa - Lumèa',
            $testo,
            array_merge(self::datiAppuntamento($prenotazione, 'assegnazione'), ['precedente' => $prenotazioneSostituita === null ? null : ['data' => $prenotazioneSostituita->getData(), 'ora' => $prenotazioneSostituita->getOra()]])
        );
    }


    public static function nuovoCouponFedelta(
        ECoupon $coupon
    ): bool {
        $cliente =
            $coupon->getCliente();

        $oggetto =
            'Nuovo coupon fedelta Lumèa';

        $testo =
            'Ciao '
            . $cliente->getNome()
            . ",\n\n"
            . 'hai raggiunto '
            . $coupon->getTraguardoCompletamenti()
            . " appuntamenti completati e hai ottenuto un nuovo coupon.\n\n"
            . 'Valore: '
            . self::formattaEuro(
                $coupon->getValoreIniziale()
            )
            . "\n"
            . 'Codice: '
            . $coupon->getCodice()
            . "\n"
            . 'Credito disponibile: '
            . self::formattaEuro(
                $coupon->getCreditoResiduo()
            )
            . "\n\n"
            . "Il coupon è visibile nella tua area personale e può essere utilizzato in salone al momento del pagamento.\n\n"
            . 'Lumèa';

        return self::invia(
            $cliente->getEmail(),
            $oggetto,
            $testo,
            ['tipo' => 'coupon', 'nome' => $cliente->getNome(), 'codice' => $coupon->getCodice(), 'valore' => $coupon->getValoreIniziale(), 'credito' => $coupon->getCreditoResiduo(), 'traguardo' => $coupon->getTraguardoCompletamenti()]
        );
    }

    public static function sospensioneTerzoNoShow(
        EUtente $cliente
    ): bool {
        $oggetto =
            'Prenotazioni online sospese - Lumèa';

        $testo =
            'Ciao '
            . $cliente->getNome()
            . ",\n\n"
            . "hai raggiunto tre mancate presentazioni consecutive.\n\n"
            . "Prenotazioni online temporaneamente sospese. "
            . "Per effettuare una prenotazione, contatta direttamente il salone.\n\n"
            . "Il credito residuo degli eventuali coupon disponibili "
            . "è stato ridotto del 50%.\n\n"
            . 'Lumèa';

        return self::invia(
            $cliente->getEmail(),
            $oggetto,
            $testo,
            ['tipo' => 'sospensione', 'nome' => $cliente->getNome()]
        );
    }

    public static function cancellazioneProprietario(
        EPrenotazione $prenotazione
    ): bool {
        $cliente =
            $prenotazione->getCliente();

        $oggetto =
            'Appuntamento annullato - Lumèa';

        $testo =
            'Ciao '
            . $cliente->getNome()
            . ",\n\n"
            . "il salone ha annullato il tuo appuntamento.\n\n"
            . 'Servizio: '
            . $prenotazione->getNomeServizio()
            . "\n"
            . 'Data: '
            . self::formattaData(
                $prenotazione->getData()
            )
            . "\n"
            . 'Ora: '
            . self::formattaOra(
                $prenotazione->getOra()
            )
            . "\n\n"
            . "Contatta il salone per concordare una nuova prenotazione.\n\n"
            . 'Lumèa';

        return self::invia(
            $cliente->getEmail(),
            $oggetto,
            $testo,
            self::datiAppuntamento($prenotazione, 'annullamento')
        );
    }

    private static function datiAppuntamento(EPrenotazione $p, string $tipo): array
    {
        return ['tipo' => $tipo, 'nome' => $p->getCliente()->getNome(),
            'servizio' => $p->getNomeServizio(), 'data' => $p->getData(), 'ora' => $p->getOra(),
            'durata' => $p->getDurata(), 'prezzo' => $p->getPrezzo()];
    }

    private static function invia(string $destinatario, string $oggetto, string $testo, array $dati): bool
    {
        if (filter_var($destinatario, FILTER_VALIDATE_EMAIL) === false || preg_match('/[\r\n]/', $destinatario)) {
            error_log('Lumèa: indirizzo email non valido.');
            return false;
        }
        try {
            $config = FTrasportoEmail::configurazione();
            $config['mode'] = getenv('LUMEA_EMAIL_MODE') ?: ($config['mode'] ?? 'disabled');
            $contenuto = (new VEmail())->prepara($dati, $testo);
            return FEmail::invia($destinatario, $oggetto, $contenuto, $config);
        } catch (Throwable $e) {
            // I trasporti non includono mai credenziali o risposte AUTH negli errori.
            error_log('Lumèa: invio email fallito - ' . $e->getMessage());
            return false;
        }
    }

    private static function formattaData(
        string $data
    ): string {
        $timestamp =
            strtotime(
                $data
            );

        if ($timestamp === false) {
            return $data;
        }

        return date(
            'd/m/Y',
            $timestamp
        );
    }

    private static function formattaOra(
        string $ora
    ): string {
        return substr(
            $ora,
            0,
            5
        );
    }

    private static function formattaEuro(
        float $importo
    ): string {
        return '€ '
            . number_format(
                $importo,
                2,
                ',',
                '.'
            );
    }
}
