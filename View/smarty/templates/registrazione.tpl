<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/svg+xml" href="images/lumea.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumèa - Registrazione</title>
    <link rel="stylesheet" href="css/interazioni.css?v=lumea20261008">
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
            <form class="form-accesso form-registrazione" method="post" action="index.php?pagina=registrazione" novalidate>
                <label for="nome"><span>Nome</span><input type="text" id="nome" name="nome" value="{$valori.nome|default:''|escape}" required></label>
                {if isset($errori.nome)}<p class="errore-campo-accesso">{$errori.nome|escape}</p>{/if}
                <label for="cognome"><span>Cognome</span><input type="text" id="cognome" name="cognome" value="{$valori.cognome|default:''|escape}" required></label>
                {if isset($errori.cognome)}<p class="errore-campo-accesso">{$errori.cognome|escape}</p>{/if}
                <label for="email"><span>Email</span><input type="email" id="email" name="email" value="{$valori.email|default:''|escape}" required></label>
                {if isset($errori.email)}<p class="errore-campo-accesso">{$errori.email|escape}</p>{/if}
                <label for="telefono"><span>Telefono</span><input type="text" id="telefono" name="telefono" value="{$valori.telefono|default:''|escape}" required></label>
                {if isset($errori.telefono)}<p class="errore-campo-accesso">{$errori.telefono|escape}</p>{/if}
                <label for="password"><span>Password</span><input type="password" id="password" name="password" required></label>
                {if isset($errori.password)}<p class="errore-campo-accesso">{$errori.password|escape}</p>{/if}
                <label for="conferma_password"><span>Conferma password</span><input type="password" id="conferma_password" name="conferma_password" required></label>
                {if isset($errori.confermaPassword)}<p class="errore-campo-accesso">{$errori.confermaPassword|escape}</p>{/if}
                <button class="pulsante-pubblico-primario" type="submit">Crea account</button>
            </form>
            <p class="alternativa-accesso">Hai già un account? <a href="index.php?pagina=login">Accedi</a></p>
        </section>
    </main>
</body>
</html>
