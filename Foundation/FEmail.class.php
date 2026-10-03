<?php

require_once __DIR__ . '/../Entity/EPrenotazione.class.php';
require_once __DIR__ . '/../Entity/ECoupon.class.php';
require_once __DIR__ . '/../Entity/EUtente.class.php';

class FEmail
{
    public static function confermaPrenotazione(
        EPrenotazione $prenotazione
    ): bool {
        $cliente =
            $prenotazione->getCliente();

        $oggetto =
            'Conferma prenotazione HairBooking';

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
            . 'HairBooking';

        return self::invia(
            $cliente->getEmail(),
            $oggetto,
            $testo
        );
    }

    public static function nuovoCouponFedelta(
        ECoupon $coupon
    ): bool {
        $cliente =
            $coupon->getCliente();

        $oggetto =
            'Nuovo coupon fedelta HairBooking';

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
            . 'HairBooking';

        return self::invia(
            $cliente->getEmail(),
            $oggetto,
            $testo
        );
    }

    public static function sospensioneTerzoNoShow(
        EUtente $cliente
    ): bool {
        $oggetto =
            'Prenotazioni online sospese - HairBooking';

        $testo =
            'Ciao '
            . $cliente->getNome()
            . ",\n\n"
            . "hai raggiunto tre mancate presentazioni consecutive.\n\n"
            . "Prenotazioni online temporaneamente sospese. "
            . "Per effettuare una prenotazione, contatta direttamente il salone.\n\n"
            . "Il credito residuo degli eventuali coupon disponibili "
            . "è stato ridotto del 50%.\n\n"
            . 'HairBooking';

        return self::invia(
            $cliente->getEmail(),
            $oggetto,
            $testo
        );
    }

    public static function cancellazioneProprietario(
        EPrenotazione $prenotazione
    ): bool {
        $cliente =
            $prenotazione->getCliente();

        $oggetto =
            'Appuntamento annullato - HairBooking';

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
            . 'HairBooking';

        return self::invia(
            $cliente->getEmail(),
            $oggetto,
            $testo
        );
    }

    private static function invia(
        string $destinatario,
        string $oggetto,
        string $testo
    ): bool {
        if (
            filter_var(
                $destinatario,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            error_log(
                'HairBooking: indirizzo email non valido: '
                    . $destinatario
            );

            return false;
        }

        $headers =
            "MIME-Version: 1.0\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n";

        // Il mittente sarà configurato sull'hosting con il dominio definitivo.
        try {
            $inviata = @mail(
                $destinatario,
                $oggetto,
                $testo,
                $headers
            );
        } catch (Throwable $e) {
            error_log(
                'HairBooking: errore invio email - '
                    . $oggetto
                    . ': '
                    . $e->getMessage()
            );

            return false;
        }

        if (!$inviata) {
            error_log(
                'HairBooking: invio email fallito verso '
                    . $destinatario
                    . ' - '
                    . $oggetto
            );
        }

        return $inviata;
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
