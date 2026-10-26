<!DOCTYPE html>
<html lang="it">
<head>
    <base href="{$baseApplicazione|escape:'html'}">
    <link rel="icon" type="image/svg+xml" href="images/lumea.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumèa - Le mie prenotazioni</title>
    <link rel="stylesheet" href="css/interazioni.css?v=lumea-csrf-20261023">
    <link rel="stylesheet" href="css/cliente.css?v=lumea-csrf-20261023">
</head>
<body class="area-cliente pagina-appuntamenti-cliente">
    <header class="header-cliente">
        <h1>Lumèa</h1>
        {include file='nav_cliente.tpl' paginaAttiva='prenotazioni'}
    </header>

    <main id="contenuto-principale" class="contenuto-cliente">
        <div class="titolo-pagina">
            <div><span class="sovratitolo">LA TUA AGENDA</span>
                <h2>Le mie prenotazioni<span class="titolo-punto">.</span></h2>
                <p>Un posto per ritrovare i tuoi appuntamenti, presenti e passati.</p>
            </div>
            {if $prenotazioniOnlineAbilitate && $appuntamentoAttivo === null}<a class="azione-principale" href="index.php?pagina=prenota&amp;nuova=1">Nuova
                prenotazione</a>{/if}
        </div>

        {if $messaggio !== null}<p class="messaggio-cliente" role="status" data-temporanea="true">{$messaggio|escape}</p>
        {/if}
        {if $errore !== null}<p class="messaggio-cliente errore" role="alert">{$errore|escape}</p>
        {/if}

        <section class="pannello-cliente" aria-labelledby="appuntamento-attivo">
            <div class="testata-pannello">
                <div><span class="sovratitolo">PROSSIMO IN PROGRAMMA</span>
                    <h3 id="appuntamento-attivo">Appuntamento attivo</h3>
                </div><span class="panel-decor" aria-hidden="true">01</span>
            </div>
            {if $appuntamentoAttivo !== null}
            <article class="appuntamento-cliente appuntamento-attivo-cliente">
                <div><strong>{$appuntamentoAttivoDettaglio.data|escape}</strong><span
                        class="badge-prenotazione badge-confermata">{$appuntamentoAttivoDettaglio.stato|escape}</span></div>
                <p><strong>{$appuntamentoAttivoDettaglio.ora_inizio|escape}–{$appuntamentoAttivoDettaglio.ora_fine|escape}</strong></p>
                <p>{$appuntamentoAttivoDettaglio.servizio|escape} · {$appuntamentoAttivoDettaglio.durata} min</p>
                <div class="azioni-cliente">
                    {if $richiestaAttesa !== null}
                    <div class="testo-lista-attesa-breve"><strong>Hai una richiesta in lista d’attesa</strong><span>Il
                            tuo appuntamento resta confermato finché non viene assegnato il nuovo orario.</span></div>
                    <a class="azione-secondaria" href="index.php?pagina=lista_attesa">Vedi la mia richiesta</a>
                    {elseif $prenotazioniOnlineAbilitate}
                    <div class="testo-lista-attesa-breve"><strong>Preferisci un altro orario?</strong><span>Il tuo
                            appuntamento rimane valido durante l’attesa.</span></div>
                    <a class="azione-principale"
                        href="index.php?pagina=lista_attesa&amp;azione=nuova&amp;servizio={$appuntamentoAttivo->getServizio()->getId()}">Cerca
                        un altro orario · Lista d’attesa</a>
                    {/if}
                    {if $puoAnnullare}
                    <form method="post" action="index.php?pagina=prenotazioni">
                        <input type="hidden" name="csrf_token" value="{$csrfToken|escape:'html'}">
                        <input type="hidden" name="azione" value="annulla_prenotazione">
                        <input type="hidden" name="id_prenotazione" value="{$appuntamentoAttivo->getId()|escape}">
                        <button class="js-conferma-azione" type="submit" data-confirm-title="Annullare la prenotazione?"
                            data-confirm-message="L’appuntamento verrà cancellato e l’orario tornerà disponibile."
                            data-confirm-action="Annulla prenotazione">Annulla prenotazione</button>
                    </form>
                    {else}
                    <span class="nota-campo">La cancellazione online è possibile solo fino a 48 ore prima
                        dell’appuntamento.</span>
                    {/if}
                </div>
            </article>
            {else}
            <p class="empty-state">Nessuna prenotazione attiva.</p>
            {/if}
        </section>

        <section class="pannello-cliente" aria-labelledby="storico-prenotazioni">
            <div class="testata-pannello">
                <div><span class="sovratitolo">NEL TEMPO</span>
                    <h3 id="storico-prenotazioni">I tuoi appuntamenti passati</h3>
                </div><span class="panel-decor" aria-hidden="true">02</span>
            </div>
            {if $storicoDettagli}
            <div class="elenco-appuntamenti-cliente">
                {foreach $storicoDettagli as $riga}
                <article class="appuntamento-cliente">
                    <div><strong>{$riga.data|escape}</strong><span
                            class="badge-prenotazione {if $riga.stato == "Completata"}badge-completata{elseif $riga.stato == "Cancellata"}badge-cancellata{elseif $riga.stato == "Non presentato"}badge-assente{else}badge-confermata{/if}">{$riga.stato|escape}</span>
                    </div>
                    <p>{$riga.servizio|escape} · {$riga.ora_inizio|escape}–{$riga.ora_fine|escape}</p>
                    {if $prenotazioniOnlineAbilitate && $appuntamentoAttivo === null && isset($ripetibili[$riga.id])}
                    <a href="index.php?pagina=prenota&amp;azione=ripeti&amp;idPrenotazione={$riga.id}">Ripeti
                        servizio</a>
                    {/if}
                </article>
                {/foreach}
            </div>
            {else}
            <p class="empty-state">Nessuna prenotazione nello storico.</p>
            {/if}
        </section>
    </main>
</body>
</html>
