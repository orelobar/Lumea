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
            $testo
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
            $testo
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
            $testo
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
            $testo
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
                'Lumèa: indirizzo email non valido: '
                    . $destinatario
            );

            return false;
        }

        /*
         * In QA le email vengono registrate in locale. Senza la variabile
         * d'ambiente il comportamento dell'applicazione resta invariato.
         */
        if (getenv('LUMEA_EMAIL_MODE') === 'log') {
            $percorsoLog = getenv('LUMEA_EMAIL_LOG')
                ?: sys_get_temp_dir() . '/lumea_email.log';
            $contenutoLog = '[' . date('c') . "]\n"
                . 'A: ' . $destinatario . "\n"
                . 'Oggetto: ' . $oggetto . "\n\n"
                . $testo . "\n---\n";

            if (@file_put_contents($percorsoLog, $contenutoLog, FILE_APPEND | LOCK_EX) === false) {
                error_log(
                    'Lumèa: scrittura log email QA non riuscita - '
                        . $oggetto
                );

                return false;
            }

            return true;
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
                'Lumèa: errore invio email - '
                    . $oggetto
                    . ': '
                    . $e->getMessage()
            );

            return false;
        }

        if (!$inviata) {
            error_log(
                'Lumèa: invio email fallito verso '
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
