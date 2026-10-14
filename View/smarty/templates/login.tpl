<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/svg+xml" href="images/lumea.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumèa - Accedi</title>
    <link rel="stylesheet" href="css/interazioni.css?v=lumea20261012">
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

            <form class="form-accesso" method="post" action="index.php?pagina=login" novalidate>
                <label for="email"><span>Email</span><input type="email" id="email" name="email" value="{$valori.email|default:''|escape}" autocomplete="email" required></label>
                {if isset($errori.email)}<p class="errore-campo-accesso">{$errori.email|escape}</p>{/if}

                <label for="password"><span>Password</span><input type="password" id="password" name="password" autocomplete="current-password" required></label>
                {if isset($errori.password)}<p class="errore-campo-accesso">{$errori.password|escape}</p>{/if}

                <button class="pulsante-pubblico-primario" type="submit">Accedi</button>
            </form>

            <p class="alternativa-accesso">Non hai ancora un account? <a href="index.php?pagina=registrazione">Registrati</a></p>
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
