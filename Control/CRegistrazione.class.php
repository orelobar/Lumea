<?php

require_once __DIR__ . '/../Foundation/FUtente.class.php';
require_once __DIR__ . '/../Foundation/FSessione.class.php';
require_once __DIR__ . '/../Entity/EUtente.class.php';
require_once __DIR__ . '/../View/VRegistrazione.class.php';

class CRegistrazione
{
    public function mostraRegistrazione(
        array $request = []
    ): void {
        if (FSessione::isAutenticato()) {
            $this->redirect(
                'index.php'
            );
        }

        $view =
            new VRegistrazione();

        $view->mostraRegistrazione();
    }


    public function gestisciRegistrazione(
        array $request
    ): void {
        if (FSessione::isAutenticato()) {
            $this->redirect(
                'index.php'
            );
        }

        $post =
            $request['post']
            ?? [];

        $risultato =
            $this->registra(
                $post['nome'] ?? '',
                $post['cognome'] ?? '',
                $post['email'] ?? '',
                $post['telefono'] ?? '',
                $post['password'] ?? '',
                $post['conferma_password'] ?? ''
            );

        if (!$risultato['successo']) {
            $view =
                new VRegistrazione();

            $view->mostraRegistrazione(
                $risultato['errori'],
                $risultato['valori']
            );

            return;
        }

        $this->redirect(
            'index.php'
        );
    }


    public function registra(
        string $nome,
        string $cognome,
        string $email,
        string $telefono,
        string $password,
        string $confermaPassword
    ): array {
        if (FSessione::isAutenticato()) {
            return [
                'successo' => false,
                'errori' => [
                    'generale' =>
                    'Hai già effettuato l\'accesso.'
                ],
                'valori' => [],
                'utente' => null
            ];
        }

        $nome =
            trim(
                $nome
            );

        $cognome =
            trim(
                $cognome
            );

        $email =
            strtolower(
                trim(
                    $email
                )
            );

        $valori = [
            'nome' => $nome,
            'cognome' => $cognome,
            'email' => $email,
            'telefono' => trim($telefono)
        ];

        $errori = [];

        if ($nome === '') {
            $errori['nome'] =
                'Inserisci un nome.';
        } elseif (
            mb_strlen(
                $nome
            ) > 50
        ) {
            $errori['nome'] =
                'Il nome non può superare 50 caratteri.';
        }

        if ($cognome === '') {
            $errori['cognome'] =
                'Inserisci un cognome.';
        } elseif (
            mb_strlen(
                $cognome
            ) > 50
        ) {
            $errori['cognome'] =
                'Il cognome non può superare 50 caratteri.';
        }

        if ($email === '') {
            $errori['email'] =
                'Inserisci un indirizzo email.';
        } elseif (
            filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            $errori['email'] =
                'Inserisci un indirizzo email valido.';
        } elseif (
            mb_strlen(
                $email
            ) > 150
        ) {
            $errori['email'] =
                'L\'indirizzo email è troppo lungo.';
        }

        $telefonoNormalizzato =
            null;

        if (
            $this->isNumeroFissoItaliano(
                $telefono
            )
        ) {
            $errori['telefono'] =
                'Non puoi inserire un numero fisso. Inserisci un numero di cellulare italiano.';
        } else {
            $telefonoNormalizzato =
                $this->normalizzaTelefono(
                    $telefono
                );

            if (
                $telefonoNormalizzato
                === null
            ) {
                $errori['telefono'] =
                    'Inserisci un numero di cellulare italiano valido.';
            }
        }

        if (
            strlen(
                $password
            ) < 8
        ) {
            $errori['password'] =
                'La password deve contenere almeno 8 caratteri, una maiuscola e un numero.';
        } elseif (
            preg_match(
                '/[A-Z]/',
                $password
            ) !== 1
            ||
            preg_match(
                '/[0-9]/',
                $password
            ) !== 1
        ) {
            $errori['password'] =
                'La password deve contenere almeno 8 caratteri, una maiuscola e un numero.';
        }

        if (
            $password
            !==
            $confermaPassword
        ) {
            $errori['confermaPassword'] =
                'Le password non coincidono.';
        }

        if (
            !isset(
                $errori['email']
            )
        ) {
            if (
                FUtente::loadByEmail(
                    $email
                )
                !== null
            ) {
                $errori['email'] =
                    'Questa email è già associata a un account. Effettua il login.';
            }
        }

        if (
            !isset(
                $errori['telefono']
            )
            &&
            $telefonoNormalizzato
            !== null
        ) {
            if (
                FUtente::loadByTelefono(
                    $telefonoNormalizzato
                )
                !== null
            ) {
                $errori['telefono'] =
                    'Questo numero è già associato a un account. Effettua il login.';
            }
        }

        if ($errori !== []) {
            return [
                'successo' => false,
                'errori' => $errori,
                'valori' => $valori,
                'utente' => null
            ];
        }

        $passwordHash =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );

        $utente =
            new EUtente(
                $nome,
                $cognome,
                $email,
                $telefonoNormalizzato,
                $passwordHash,
                'cliente'
            );

        if (
            !FUtente::store(
                $utente
            )
        ) {
            return [
                'successo' => false,
                'errori' => [
                    'generale' =>
                    'Non è stato possibile completare la registrazione.'
                ],
                'valori' => $valori,
                'utente' => null
            ];
        }

        $idUtente =
            $utente->getId();

        if ($idUtente === null) {
            throw new RuntimeException(
                'Utente registrato senza identificatore.'
            );
        }

        FSessione::impostaUtenteAutenticato(
            $idUtente
        );

        return [
            'successo' => true,
            'errori' => [],
            'valori' => [],
            'utente' => $utente
        ];
    }


    private function isNumeroFissoItaliano(
        string $telefono
    ): bool {
        $telefonoPulito =
            $this->pulisciTelefono(
                $telefono
            );

        if (
            substr(
                $telefonoPulito,
                0,
                4
            )
            === '0039'
        ) {
            $telefonoPulito =
                substr(
                    $telefonoPulito,
                    4
                );
        } elseif (
            substr(
                $telefonoPulito,
                0,
                3
            )
            === '+39'
        ) {
            $telefonoPulito =
                substr(
                    $telefonoPulito,
                    3
                );
        }

        return
            preg_match(
                '/^0[0-9]+$/',
                $telefonoPulito
            ) === 1;
    }


    private function normalizzaTelefono(
        string $telefono
    ): ?string {
        $telefono =
            $this->pulisciTelefono(
                $telefono
            );

        if ($telefono === '') {
            return null;
        }

        if (
            substr(
                $telefono,
                0,
                4
            )
            === '0039'
        ) {
            $telefono =
                '+39'
                .
                substr(
                    $telefono,
                    4
                );
        } elseif (
            substr(
                $telefono,
                0,
                3
            )
            !== '+39'
        ) {
            $telefono =
                '+39'
                .
                $telefono;
        }

        if (
            preg_match(
                '/^\+393[0-9]{9}$/',
                $telefono
            ) !== 1
        ) {
            return null;
        }

        return $telefono;
    }


    private function pulisciTelefono(
        string $telefono
    ): string {
        $telefono =
            trim(
                $telefono
            );

        $telefonoPulito =
            preg_replace(
                '/[\s\-\(\)]/',
                '',
                $telefono
            );

        if (
            $telefonoPulito
            === null
        ) {
            return '';
        }

        return $telefonoPulito;
    }


    private function redirect(
        string $url
    ): void {
        header(
            'Location: ' . $url
        );

        exit;
    }
}
