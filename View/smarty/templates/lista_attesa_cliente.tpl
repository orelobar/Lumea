<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/svg+xml" href="images/lumea.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumèa - Lista d’attesa</title>
    <link rel="stylesheet" href="css/interazioni.css?v=lumea20261012">
    <link rel="stylesheet" href="css/cliente.css?v={$versioneCSSCliente|escape}">
</head>
<body class="area-cliente pagina-attesa-cliente">
    <header class="header-cliente">
        <h1>Lumèa</h1>
        {include file='nav_cliente.tpl' paginaAttiva='lista_attesa'}
    </header>

    <main id="contenuto-principale" class="contenuto-cliente">
        <div class="titolo-pagina"><div><span class="sovratitolo">ORARI DESIDERATI</span><h2>Lista d’attesa<span class="titolo-punto">.</span></h2><p>Le tue preferenze e lo stato delle richieste, in un unico posto.</p></div></div>
        <section class="guida-lista-attesa" aria-labelledby="come-funziona-attesa">
            <h3 id="come-funziona-attesa">Come funziona</h3>
            <ol><li><strong>Scegli l’orario</strong><span>Indica una fascia occupata che preferisci, a più di 48 ore.</span></li><li><strong>Attendi che si liberi</strong><span>La richiesta segue l’ordine della lista.</span></li><li><strong>Ricevi il nuovo appuntamento</strong><span>Se arriva il tuo turno e la richiesta è ancora valida, Lumèa può assegnartelo automaticamente.</span></li></ol>
            <p>Il tuo appuntamento rimane confermato fino all’assegnazione del nuovo orario.</p>
        </section>
        {if $messaggio !== null}<p class="messaggio-cliente" role="status" data-temporanea="true">{$messaggio|escape}</p>{/if}
        {if $errore !== null}<p class="messaggio-cliente errore" role="alert">{$errore|escape}</p>{/if}

        <div class="testata-richieste"><span class="sovratitolo">I TUOI ORARI DESIDERATI</span><h3 class="lista-intestazione">Le tue richieste</h3><p>Controlla lo stato degli orari richiesti.</p></div>
        {if $richieste}
            <div class="elenco-lista-attesa">
                {foreach $gruppiRichieste as $stato => $gruppo}
                    {if $gruppo.richieste}
                    <section class="gruppo-richieste" aria-labelledby="richieste-{$stato|escape}">
                        <h4 id="richieste-{$stato|escape}">{$gruppo.titolo|escape} <span>{count($gruppo.richieste)}</span></h4>
                        {foreach $gruppo.richieste as $richiesta}
                    <article class="richiesta-attesa stato-{$richiesta.stato|escape}">
                        <div class="intestazione-sezione">
                            <h3>{$richiesta.servizio|escape}</h3>
                            <strong>{$richiesta.stato_etichetta|escape}</strong>
                        </div>
                        <dl class="dettagli-attesa">
                            <div><dt>Data desiderata</dt><dd>{$richiesta.data|escape}</dd></div>
                            <div><dt>Ora desiderata</dt><dd>{$richiesta.ora|escape}</dd></div>
                        </dl>
                        <p>{$richiesta.stato_descrizione|escape}</p>
                        {if $richiesta.alternativa !== null && $richiesta.attiva}
                            <p class="appuntamento-mantenuto">
                                Appuntamento mantenuto: <strong>{$richiesta.alternativa.data|escape} alle {$richiesta.alternativa.ora|escape}</strong>.
                                Resta confermato finché l’orario richiesto non viene assegnato.
                            </p>
                        {/if}
                        {if $richiesta.attiva}
                            <form method="post" action="index.php?pagina=lista_attesa">
                                <input type="hidden" name="azione" value="annulla_attesa">
                                <input type="hidden" name="id_richiesta" value="{$richiesta.id}">
                                <button class="js-conferma-azione" type="submit" data-confirm-title="Annullare la richiesta?" data-confirm-message="La richiesta verrà rimossa dalla lista d’attesa." data-confirm-action="Annulla richiesta">Annulla richiesta</button>
                            </form>
                        {/if}
                    </article>
                        {/foreach}
                    </section>
                    {/if}
                {/foreach}
            </div>
        {else}
            <p class="empty-state">Non hai ancora richieste in lista d’attesa. Per richiedere una fascia occupata, scegli un servizio durante la prenotazione oppure, se hai già un appuntamento, utilizza la sezione “Le mie prenotazioni”.</p>
        {/if}
    </main>
</body>
</html>
