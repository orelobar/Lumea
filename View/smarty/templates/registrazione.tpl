<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/svg+xml" href="images/lumea.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumèa - Registrazione</title>
    <link rel="stylesheet" href="css/interazioni.css?v=lumea-csrf-20261023">
    <script src="js/accesso.js?v=lumea-esame-20261015" defer></script>
</head>
<body class="area-pubblica area-accesso">
    <header class="header-pubblico header-accesso">
        <a class="brand-pubblico" href="index.php"><span class="brand-pubblico-marchio" aria-hidden="true">L</span><span><strong>Lumèa</strong><small>Prenotazioni salone</small></span></a>
        <a class="link-home-pubblica" href="index.php">Torna alla home</a>
    </header>

    <main class="pagina-accesso pagina-registrazione">
        <section class="pannello-accesso">
            <div class="intro-accesso"><span class="sovratitolo-pubblico">Nuovo account</span><h1>Registrati a Lumèa</h1><p>Crea la tua area personale per prenotare e gestire gli appuntamenti.</p></div>
            {if isset($errori.generale)}<p class="errore-accesso" role="alert">{$errori.generale|escape}</p>{/if}
            <form class="form-accesso form-registrazione" method="post" action="index.php?pagina=registrazione">
                <input type="hidden" name="csrf_token" value="{$csrfToken|escape:'html'}">
                <label for="nome"><span>Nome</span><input type="text" id="nome" name="nome" value="{$valori.nome|default:''|escape}" required></label>
                {if isset($errori.nome)}<p class="errore-campo-accesso">{$errori.nome|escape}</p>{/if}
                <label for="cognome"><span>Cognome</span><input type="text" id="cognome" name="cognome" value="{$valori.cognome|default:''|escape}" required></label>
                {if isset($errori.cognome)}<p class="errore-campo-accesso">{$errori.cognome|escape}</p>{/if}
                <label for="email"><span>Email</span><input type="email" id="email" name="email" pattern="[^@\s]+@[^@\s]+\.[^@\s]+" title="Usa un indirizzo completo, ad esempio nome@dominio.it. Sono ammessi anche gli alias con +." autocomplete="email" value="{$valori.email|default:''|escape}" required></label>
                {if isset($errori.email)}<p class="errore-campo-accesso">{$errori.email|escape}</p>{/if}
                <label for="telefono"><span>Telefono</span><input type="text" id="telefono" name="telefono" value="{$valori.telefono|default:''|escape}" required></label>
                {if isset($errori.telefono)}<p class="errore-campo-accesso">{$errori.telefono|escape}</p>{/if}
                <label for="password"><span>Password</span><span class="campo-password">
                        <input type="password" id="password" name="password" required autocomplete="new-password">
                        <button class="pulsante-visibilita-password" type="button" data-password-target="password"
                            aria-controls="password" aria-label="Mostra password" aria-pressed="false" title="Mostra password">
                            <svg class="icona-occhio" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="icona-occhio-chiuso" viewBox="0 0 24 24" aria-hidden="true" focusable="false" hidden><path d="m3 3 18 18M10.6 5.1A11 11 0 0 1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-3.1 3.8M6.2 6.2A21 21 0 0 0 2 12s3.5 7 10 7a12 12 0 0 0 5.8-1.8M10 10a3 3 0 0 0 4 4"/></svg>
                        </button>
                    </span></label>
                {if isset($errori.password)}<p class="errore-campo-accesso">{$errori.password|escape}</p>{/if}
                <label for="conferma_password"><span>Conferma password</span><span class="campo-password">
                        <input type="password" id="conferma_password" name="conferma_password" required autocomplete="new-password">
                        <button class="pulsante-visibilita-password" type="button" data-password-target="conferma_password"
                            aria-controls="conferma_password" aria-label="Mostra conferma password" aria-pressed="false" title="Mostra conferma password">
                            <svg class="icona-occhio" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="icona-occhio-chiuso" viewBox="0 0 24 24" aria-hidden="true" focusable="false" hidden><path d="m3 3 18 18M10.6 5.1A11 11 0 0 1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-3.1 3.8M6.2 6.2A21 21 0 0 0 2 12s3.5 7 10 7a12 12 0 0 0 5.8-1.8M10 10a3 3 0 0 0 4 4"/></svg>
                        </button>
                    </span></label>
                {if isset($errori.confermaPassword)}<p class="errore-campo-accesso">{$errori.confermaPassword|escape}</p>{/if}
                <button class="pulsante-pubblico-primario" type="submit">Crea account</button>
            </form>
            <p class="alternativa-accesso">Hai già un account? <a href="index.php?pagina=login">Accedi</a></p>
        </section>
    </main>
</body>
</html>
