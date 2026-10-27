<!DOCTYPE html>
<html lang="it">
<head>
    <base href="{$baseApplicazione|escape:'html'}">
    <link rel="icon" type="image/svg+xml" href="images/lumea.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumèa - Registrazione</title>
    <link rel="stylesheet" href="css/interazioni.css?v=lumea-registrazione-20261026">
    <script src="js/accesso.js?v=lumea-form-20261024" defer></script>
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
            <p>Tutti i campi sono obbligatori.</p>
            <form class="form-accesso form-registrazione" method="post" action="clienti">
                <input type="hidden" name="csrf_token" value="{$csrfToken|escape:'html'}">
                <div class="campo-registrazione">
                    <label for="nome">
                        <span>Nome</span>
                        <input type="text" id="nome" name="nome" autocomplete="given-name"
                            value="{$valori.nome|default:''|escape}" required
                            aria-describedby="nome-errore" aria-invalid="{if isset($errori.nome)}true{else}false{/if}">
                    </label>
                    <p id="nome-errore" class="errore-campo-accesso" aria-live="polite"{if !isset($errori.nome)} hidden{/if}>{if isset($errori.nome)}{$errori.nome|escape}{/if}</p>
                </div>
                <div class="campo-registrazione">
                    <label for="cognome">
                        <span>Cognome</span>
                        <input type="text" id="cognome" name="cognome" autocomplete="family-name"
                            value="{$valori.cognome|default:''|escape}" required
                            aria-describedby="cognome-errore" aria-invalid="{if isset($errori.cognome)}true{else}false{/if}">
                    </label>
                    <p id="cognome-errore" class="errore-campo-accesso" aria-live="polite"{if !isset($errori.cognome)} hidden{/if}>{if isset($errori.cognome)}{$errori.cognome|escape}{/if}</p>
                </div>
                <div class="campo-registrazione">
                    <label for="email">
                        <span>Email</span>
                        <input type="email" id="email" name="email" autocomplete="email" pattern="[^@\s]+@[^@\s]+\.[^@\s]+"
                            value="{$valori.email|default:''|escape}" required
                            aria-describedby="email-errore" aria-invalid="{if isset($errori.email)}true{else}false{/if}">
                    </label>
                    <p id="email-errore" class="errore-campo-accesso" aria-live="polite"{if !isset($errori.email)} hidden{/if}>{if isset($errori.email)}{$errori.email|escape}{/if}</p>
                </div>
                <div class="campo-registrazione">
                    <label for="telefono">
                        <span>Telefono</span>
                        <input type="tel" id="telefono" name="telefono" autocomplete="tel"
                            value="{$valori.telefono|default:''|escape}" required
                            aria-describedby="telefono-aiuto telefono-errore" aria-invalid="{if isset($errori.telefono)}true{else}false{/if}">
                        <small id="telefono-aiuto">Numero di cellulare italiano</small>
                    </label>
                    <p id="telefono-errore" class="errore-campo-accesso" aria-live="polite"{if !isset($errori.telefono)} hidden{/if}>{if isset($errori.telefono)}{$errori.telefono|escape}{/if}</p>
                </div>
                <div class="campo-registrazione">
                    <label for="password"><span>Password</span><span class="campo-password">
                            <input type="password" id="password" name="password" required autocomplete="new-password"
                                aria-describedby="password-aiuto password-errore" aria-invalid="{if isset($errori.password)}true{else}false{/if}">
                            <button class="pulsante-visibilita-password" type="button" data-password-target="password"
                                aria-controls="password" aria-label="Mostra password" aria-pressed="false" title="Mostra password">
                                <svg class="icona-occhio" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="icona-occhio-chiuso" viewBox="0 0 24 24" aria-hidden="true" focusable="false" hidden><path d="m3 3 18 18M10.6 5.1A11 11 0 0 1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-3.1 3.8M6.2 6.2A21 21 0 0 0 2 12s3.5 7 10 7a12 12 0 0 0 5.8-1.8M10 10a3 3 0 0 0 4 4"/></svg>
                            </button>
                        </span>
                        <small id="password-aiuto">Minimo 8 caratteri, una maiuscola e un numero</small>
                    </label>
                    <p id="password-errore" class="errore-campo-accesso" aria-live="polite"{if !isset($errori.password)} hidden{/if}>{if isset($errori.password)}{$errori.password|escape}{/if}</p>
                </div>
                <div class="campo-registrazione">
                    <label for="conferma_password"><span>Conferma password</span><span class="campo-password">
                            <input type="password" id="conferma_password" name="conferma_password" required autocomplete="new-password"
                                aria-describedby="conferma_password-errore" aria-invalid="{if isset($errori.confermaPassword)}true{else}false{/if}">
                            <button class="pulsante-visibilita-password" type="button" data-password-target="conferma_password"
                                aria-controls="conferma_password" aria-label="Mostra conferma password" aria-pressed="false" title="Mostra conferma password">
                                <svg class="icona-occhio" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="icona-occhio-chiuso" viewBox="0 0 24 24" aria-hidden="true" focusable="false" hidden><path d="m3 3 18 18M10.6 5.1A11 11 0 0 1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-3.1 3.8M6.2 6.2A21 21 0 0 0 2 12s3.5 7 10 7a12 12 0 0 0 5.8-1.8M10 10a3 3 0 0 0 4 4"/></svg>
                            </button>
                        </span>
                    </label>
                    <p id="conferma_password-errore" class="errore-campo-accesso" aria-live="polite"{if !isset($errori.confermaPassword)} hidden{/if}>{if isset($errori.confermaPassword)}{$errori.confermaPassword|escape}{/if}</p>
                </div>
                <button class="pulsante-pubblico-primario" type="submit">Crea account</button>
            </form>
            <p class="alternativa-accesso">Hai già un account? <a href="sessione">Accedi</a></p>
        </section>
    </main>
</body>
</html>
