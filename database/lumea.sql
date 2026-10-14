CREATE DATABASE IF NOT EXISTS lumea CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lumea;
/* =========================================================
 UTENTI
 ========================================================= */
CREATE TABLE utenti (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    cognome VARCHAR(50) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    telefono VARCHAR(20) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    ruolo VARCHAR(20) NOT NULL DEFAULT 'cliente',
    no_show_consecutivi INT UNSIGNED NOT NULL DEFAULT 0,
    prenotazioni_online_abilitate BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT chk_ruolo CHECK (
        ruolo IN ('cliente', 'proprietario')
    )
) ENGINE = InnoDB;
/* =========================================================
 SERVIZI
 ========================================================= */
CREATE TABLE servizi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    categoria VARCHAR(20) NOT NULL,
    prezzo DECIMAL(10, 2) NOT NULL,
    durata INT UNSIGNED NOT NULL,
    attivo BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT chk_categoria CHECK (categoria IN ('Uomo', 'Donna')),
    CONSTRAINT chk_prezzo_servizio CHECK (prezzo >= 0),
    CONSTRAINT chk_durata_servizio CHECK (
        durata > 0
        AND MOD(durata, 15) = 0
    )
) ENGINE = InnoDB;
/* =========================================================
 COUPON
 ========================================================= */
CREATE TABLE coupon (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codice VARCHAR(50) NOT NULL UNIQUE,
    id_cliente INT UNSIGNED NOT NULL,
    valore_iniziale DECIMAL(10, 2) NOT NULL,
    credito_residuo DECIMAL(10, 2) NOT NULL,
    data_ora_generazione DATETIME NOT NULL,
    stato VARCHAR(20) NOT NULL DEFAULT 'disponibile',
    traguardo_completamenti INT UNSIGNED NOT NULL,
    CONSTRAINT fk_coupon_cliente FOREIGN KEY (id_cliente) REFERENCES utenti(id),
    CONSTRAINT chk_stato_coupon CHECK (
        stato IN (
            'disponibile',
            'esaurito'
        )
    ),
    CONSTRAINT chk_valore_coupon CHECK (valore_iniziale > 0),
    CONSTRAINT chk_credito_coupon CHECK (
        credito_residuo >= 0
        AND credito_residuo <= valore_iniziale
    ),
    CONSTRAINT uq_coupon_traguardo UNIQUE (
        id_cliente,
        traguardo_completamenti
    )
) ENGINE = InnoDB;
/* =========================================================
 PRENOTAZIONI: solo appuntamento e snapshot del servizio, nessun credito coupon.
 ========================================================= */
CREATE TABLE prenotazioni (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT UNSIGNED NOT NULL,
    id_servizio INT UNSIGNED NOT NULL,
    nome_servizio VARCHAR(100) NOT NULL,
    data DATE NOT NULL,
    ora TIME NOT NULL,
    durata INT UNSIGNED NOT NULL,
    prezzo DECIMAL(10, 2) NOT NULL,
    stato VARCHAR(20) NOT NULL DEFAULT 'prenotata',
    CONSTRAINT fk_prenotazione_cliente FOREIGN KEY (id_cliente) REFERENCES utenti(id),
    CONSTRAINT fk_prenotazione_servizio FOREIGN KEY (id_servizio) REFERENCES servizi(id),
    CONSTRAINT chk_stato_prenotazione CHECK (
        stato IN (
            'prenotata',
            'completata',
            'cancellata',
            'no-show'
        )
    ),
    CONSTRAINT chk_durata_prenotazione CHECK (
        durata > 0
        AND MOD(durata, 15) = 0
    ),
    CONSTRAINT chk_prezzo_prenotazione CHECK (prezzo >= 0)
) ENGINE = InnoDB;
/* =========================================================
 LISTA D'ATTESA
 ========================================================= */
CREATE TABLE lista_attesa (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT UNSIGNED NOT NULL,
    id_servizio INT UNSIGNED NOT NULL,
    data_desiderata DATE NOT NULL,
    ora_desiderata TIME NOT NULL,
    data_ora_inserimento DATETIME NOT NULL,
    stato VARCHAR(20) NOT NULL DEFAULT 'attiva',
    id_prenotazione_alternativa INT UNSIGNED NULL,
    CONSTRAINT fk_lista_cliente FOREIGN KEY (id_cliente) REFERENCES utenti(id),
    CONSTRAINT fk_lista_servizio FOREIGN KEY (id_servizio) REFERENCES servizi(id),
    CONSTRAINT fk_lista_alternativa FOREIGN KEY (id_prenotazione_alternativa) REFERENCES prenotazioni(id),
    CONSTRAINT chk_stato_lista CHECK (
        stato IN (
            'attiva',
            'soddisfatta',
            'annullata',
            'scaduta'
        )
    )
) ENGINE = InnoDB;
/* =========================================================
 PAGAMENTI: incasso reale in salone ed eventuale credito coupon utilizzato.
 ========================================================= */
CREATE TABLE pagamenti (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_prenotazione INT UNSIGNED NOT NULL UNIQUE,
    importo_pagato DECIMAL(10, 2) NOT NULL,
    id_coupon INT UNSIGNED NULL,
    importo_coupon_utilizzato DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    data_ora_pagamento DATETIME NOT NULL,
    CONSTRAINT fk_pagamento_prenotazione FOREIGN KEY (id_prenotazione) REFERENCES prenotazioni(id),
    CONSTRAINT fk_pagamento_coupon FOREIGN KEY (id_coupon) REFERENCES coupon(id),
    CONSTRAINT chk_importo_pagato CHECK (importo_pagato >= 0),
    CONSTRAINT chk_importo_coupon_pagamento CHECK (importo_coupon_utilizzato >= 0)
) ENGINE = InnoDB;
/* =========================================================
 DISPONIBILITA
 ========================================================= */
CREATE TABLE disponibilita (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    data DATE NOT NULL,
    ora_inizio TIME NOT NULL,
    ora_fine TIME NOT NULL,
    CONSTRAINT chk_fascia_disponibilita CHECK (ora_fine > ora_inizio),
    CONSTRAINT uq_fascia_disponibilita UNIQUE (
        data,
        ora_inizio,
        ora_fine
    )
) ENGINE = InnoDB;
-- Date personalizzate da preservare quando si applica l'orario del mese.
CREATE TABLE IF NOT EXISTS eccezioni_disponibilita (
    data DATE PRIMARY KEY
) ENGINE=InnoDB;
