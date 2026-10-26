<!DOCTYPE html>
<html lang="it">
<head>
    <base href="{$baseApplicazione|escape:'html'}">
    <link rel="icon" type="image/svg+xml" href="images/lumea.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumèa - Accedi</title>
    <link rel="stylesheet" href="css/interazioni.css?v=lumea-csrf-20261023">
    <script src="js/accesso.js?v=lumea-esame-20261015" defer></script>
</head>
<body class="area-pubblica area-accesso">
    <header class="header-pubblico header-accesso">
        <a class="brand-pubblico" href="index.php">
            <span class="brand-pubblico-marchio" aria-hidden="true">L</span>
            <span><strong>Lumèa</strong><small>Prenotazioni salone</small></span>
        </a>
        <a class="link-home-pubblica" href="index.php">Torna alla home</a>
    </header>

    <main class="pagina-accesso">
        <section class="pannello-accesso">
            <div class="intro-accesso">
                <span class="sovratitolo-pubblico">Bentornato</span>
                <h1>Accedi a Lumèa</h1>
                <p>Consulta i tuoi appuntamenti, prenota un nuovo servizio; gestisci coupon e lista d’attesa dalla tua area personale.</p>
            </div>

            {if isset($errori.generale)}<p class="errore-accesso" role="alert">{$errori.generale|escape}</p>{/if}

            <form class="form-accesso" method="post" action="sessione" novalidate>
                <input type="hidden" name="csrf_token" value="{$csrfToken|escape:'html'}">
                <label for="email"><span>Email</span><input type="email" id="email" name="email" value="{$valori.email|default:''|escape}" autocomplete="email" required></label>
                {if isset($errori.email)}<p class="errore-campo-accesso">{$errori.email|escape}</p>{/if}

                <label for="password"><span>Password</span><span class="campo-password">
                        <input type="password" id="password" name="password" autocomplete="current-password" required>
                        <button class="pulsante-visibilita-password" type="button" data-password-target="password"
                            aria-controls="password" aria-label="Mostra password" aria-pressed="false" title="Mostra password">
                            <svg class="icona-occhio" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="icona-occhio-chiuso" viewBox="0 0 24 24" aria-hidden="true" focusable="false" hidden><path d="m3 3 18 18M10.6 5.1A11 11 0 0 1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-3.1 3.8M6.2 6.2A21 21 0 0 0 2 12s3.5 7 10 7a12 12 0 0 0 5.8-1.8M10 10a3 3 0 0 0 4 4"/></svg>
                        </button>
                    </span></label>
                {if isset($errori.password)}<p class="errore-campo-accesso">{$errori.password|escape}</p>{/if}

                <button class="pulsante-pubblico-primario" type="submit">Accedi</button>
            </form>

            <p class="alternativa-accesso">Non hai ancora un account? <a href="clienti/registrazione">Registrati</a></p>
        </section>
        <aside class="visual-accesso" aria-label="I vantaggi di Lumèa">
            <span class="visual-accesso-logo" aria-hidden="true">L</span>
            <strong>Il tuo tempo, organizzato meglio.</strong>
            <ul class="vantaggi-accesso">
                <li><span aria-hidden="true">◷</span><div><b>Prenotazioni online</b><small>Il tuo appuntamento, in pochi passi.</small></div></li>
                <li><span aria-hidden="true">↗</span><div><b>Lista d’attesa</b><small>Un altro orario, senza perdere quello attuale.</small></div></li>
                <li><span aria-hidden="true">✦</span><div><b>Coupon e fedeltà</b><small>Ogni visita conta.</small></div></li>
            </ul>
            <small>Prenotazioni · Attesa · Fedeltà</small>
        </aside>
    </main>
</body>
</html>
