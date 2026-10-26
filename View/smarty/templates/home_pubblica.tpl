<!DOCTYPE html>
<html lang="it">
<head>
    <base href="{$baseApplicazione|escape:'html'}">
    <link rel="icon" type="image/svg+xml" href="images/lumea.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumèa</title>
    <link rel="stylesheet" href="css/interazioni.css?v=lumea-csrf-20261023">
</head>
<body class="area-pubblica">
    <header class="header-pubblico">
        <a class="brand-pubblico" href="index.php" aria-label="Lumèa, home">
            <span class="brand-pubblico-marchio" aria-hidden="true">L</span>
            <span><strong>Lumèa</strong><small>Prenotazioni salone</small></span>
        </a>
        <nav class="nav-pubblico" aria-label="Navigazione principale">
            <a href="{$urlCorrente|escape:'html'}#come-funziona">Come funziona</a>
            <a href="sessione">Accedi</a>
            <a class="cta-pubblica" href="clienti/registrazione">Registrati</a>
        </nav>
    </header>

    <main class="home-pubblica">
        <section class="hero-pubblico">
            <div class="hero-pubblico-testo">
                <span class="sovratitolo-pubblico">Il tuo salone, senza attese inutili</span>
                <h1>Prenota il tuo prossimo appuntamento con semplicità.</h1>
                <p>Scegli servizio, giorno e orario. Lumèa tiene insieme appuntamenti, lista d’attesa, coupon fedeltà e storico in un’unica area personale.</p>
                <div class="azioni-hero-pubblico">
                    <a class="pulsante-pubblico-primario" href="sessione">Accedi</a>
                    <a class="pulsante-pubblico-secondario" href="clienti/registrazione">Crea un account</a>
                </div>
                <div class="garanzie-pubbliche" aria-label="Funzioni principali">
                    <span><strong>01</strong> Prenotazione online</span>
                    <span><strong>02</strong> Lista d’attesa</span>
                    <span><strong>03</strong> Coupon fedeltà</span>
                </div>
            </div>
            <div class="hero-pubblico-visual" aria-hidden="true">
                <div class="card-hero card-hero-principale">
                    <span>Il tuo prossimo appuntamento</span>
                    <strong>Taglio &amp; styling</strong>
                    <small>Gestisci tutto dalla tua area cliente</small>
                </div>
                <div class="card-hero card-hero-secondaria">
                    <span>Lumèa</span>
                    <strong>Tempo ben organizzato.</strong>
                </div>
            </div>
        </section>

        <section id="come-funziona" class="sezione-pubblica">
            <div class="intestazione-sezione-pubblica">
                <span class="sovratitolo-pubblico">Come funziona</span>
                <h2>Dal servizio alla conferma in pochi passaggi.</h2>
            </div>
            <div class="passaggi-pubblici">
                <article><span>1</span><h3>Scegli il servizio</h3><p>Consulta le prestazioni disponibili e seleziona quella che preferisci.</p></article>
                <article><span>2</span><h3>Trova l’orario</h3><p>Visualizza gli slot realmente disponibili in base all’agenda del salone.</p></article>
                <article><span>3</span><h3>Gestisci tutto online</h3><p>Controlla appuntamenti, lista d’attesa, coupon e spese dalla tua area personale.</p></article>
            </div>
        </section>

        <section class="richiamo-pubblico">
            <div><span class="sovratitolo-pubblico">Lumèa</span><h2>Hai già un account?</h2><p>Accedi alla tua area per controllare il prossimo appuntamento o prenotarne uno nuovo.</p></div>
            <a class="pulsante-pubblico-primario" href="sessione">Vai al login</a>
        </section>
    </main>

    <footer class="footer-pubblico"><strong>Lumèa</strong><span>Gestione appuntamenti del salone</span></footer>
</body>
</html>
