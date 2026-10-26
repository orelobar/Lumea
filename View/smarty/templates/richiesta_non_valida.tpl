<!DOCTYPE html>
<html lang="it">
<head>
    <base href="{$baseApplicazione|escape:'html'}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumèa - Richiesta non valida</title>
    <link rel="icon" type="image/svg+xml" href="images/lumea.svg">
    <link rel="stylesheet" href="css/interazioni.css?v=lumea-csrf-20261023">
</head>
<body class="area-pubblica area-accesso">
    <header class="header-pubblico header-accesso">
        <a class="brand-pubblico" href="index.php"><span class="brand-pubblico-marchio" aria-hidden="true">L</span><span><strong>Lumèa</strong><small>Prenotazioni salone</small></span></a>
    </header>
    <main class="pagina-accesso">
        <section class="pannello-accesso">
            <h1>Richiesta non valida</h1>
            <p class="errore-accesso" role="alert">{$messaggio|escape}</p>
            <p><a class="pulsante-pubblico-primario" href="index.php">Torna a Lumèa</a></p>
        </section>
    </main>
</body>
</html>
