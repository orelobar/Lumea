<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/svg+xml" href="images/lumea.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumèa - Scegli un altro orario</title>
    <link rel="stylesheet" href="css/interazioni.css?v=lumea-csrf-20261023">
    <link rel="stylesheet" href="css/cliente.css?v=lumea-csrf-20261023">
    <script src="js/scelta-attesa-cliente.js?v=lumea20261012" defer></script>
</head>
<body class="area-cliente modalita-lista-attesa">
    <header class="header-cliente">
        <h1>Lumèa</h1>
        {include file='nav_cliente.tpl' paginaAttiva='lista_attesa'}
    </header>

    <main id="contenuto-principale" class="contenuto-cliente">
        <div class="titolo-pagina">
            <div><span class="sovratitolo">PERCORSO SEPARATO</span><h2>Trova un altro orario<span class="titolo-punto">.</span></h2><p>Scegli quando preferiresti venire in salone, senza modificare subito l’appuntamento attuale.</p></div>
            <a class="azione-secondaria" href="index.php?pagina=lista_attesa">Le mie richieste ↗</a>
        </div>

        {if $errore !== null}<p class="messaggio-cliente errore" role="alert">{$errore|escape}</p>{/if}

        {if $appuntamentoAttuale !== null}
            <section class="appuntamento-attuale-attesa" aria-labelledby="appuntamento-mantenuto">
                <h3 id="appuntamento-mantenuto">Il tuo appuntamento attuale</h3>
                <dl class="riepilogo-prenotazione appuntamento-unificato">
                    <div><dt>Servizio</dt><dd>{$appuntamentoAttuale.servizio|escape}</dd></div>
                    <div><dt>Data</dt><dd>{$appuntamentoAttuale.data|escape}</dd></div>
                    <div><dt>Ora</dt><dd>{$appuntamentoAttuale.ora_inizio|escape}–{$appuntamentoAttuale.ora_fine|escape}</dd></div>
                </dl>
            </section>
        {/if}

        <section class="spiegazione-attesa" aria-labelledby="come-funziona-attesa"><div class="attesa-icona" aria-hidden="true">◷</div><div>
            <h3 id="come-funziona-attesa">Come funziona</h3>
            <p>
                Gli orari mostrati sono già occupati. Scegli quello che preferisci: se si libera, Lumèa te lo assegna automaticamente.
                {if $appuntamentoAttuale !== null}
                    Fino ad allora il tuo appuntamento resta confermato; solo all’assegnazione viene sostituito.
                {/if}
            </p>
            <p class="nota-campo">Sono disponibili soltanto orari per richieste valide a più di 48 ore.</p></div></section>

        <section class="pannello-cliente" aria-labelledby="orari-lista-attesa">
            <h3 id="orari-lista-attesa">Scegli l’orario che preferisci</h3>
            {if $servizio !== null}
                <p><strong>{$servizio->getNome()|escape}</strong> · {$servizio->getDurata()} minuti</p>
            {/if}
            <p class="guida-contestuale">Gli orari sono occupati: puoi indicarne uno come preferenza, senza cancellare la tua prenotazione attuale.</p>

            {if $richiestaAttiva !== null}
                <p role="status">Hai già una richiesta attiva. Puoi controllarla o annullarla nella pagina Lista d’attesa.</p>
                <a href="index.php?pagina=lista_attesa">Vai alla lista d’attesa</a>
            {elseif !$puoAttendere}
                <p class="empty-state">Non puoi inserire una nuova richiesta in questo momento.</p>
            {elseif $servizio === null}
                <form method="get" action="index.php" class="scelta-servizio-attesa">
                    <input type="hidden" name="pagina" value="lista_attesa">
                    <input type="hidden" name="azione" value="nuova">
                    <label for="servizio-attesa">Quale servizio desideri?</label>
                    <select name="servizio" id="servizio-attesa" required>
                        <option value="">Scegli un servizio</option>
                        {foreach $servizi as $opzione}
                            <option value="{$opzione->getId()}">{$opzione->getCategoria()|escape} · {$opzione->getNome()|escape} · {$opzione->getDurata()} minuti</option>
                        {/foreach}
                    </select>
                    <button class="azione-principale" type="submit">Mostra gli orari occupati</button>
                </form>
            {elseif $slotAttesa}
                <form class="form-lista-attesa" method="post" action="index.php?pagina=lista_attesa">
                    <input type="hidden" name="csrf_token" value="{$csrfToken|escape:'html'}">
                    <input type="hidden" name="azione" value="inserisci_attesa">
                    <input type="hidden" name="id_servizio" value="{$servizio->getId()}">
                    <fieldset class="scelte-slot-attesa scelte-attesa-mensili">
                        <legend>Quando vorresti venire?</legend>
                        <div class="percorso-scelta-attesa" aria-hidden="true">
                            <span><b>1</b> Scegli il mese</span>
                            <span class="percorso-freccia-attesa">›</span>
                            <span><b>2</b> Apri il giorno</span>
                            <span class="percorso-freccia-attesa">›</span>
                            <span><b>3</b> Seleziona l’orario</span>
                        </div>
                        <div class="elenco-mesi-attesa">
                            {foreach $mesiAttesa as $mese}
                                <details class="mese-attesa"{if $mese@first} open{/if}>
                                    <summary>
                                        <span class="mese-attesa-marchio" aria-hidden="true">{$mese@iteration|string_format:'%02d'}</span>
                                        <span class="mese-attesa-intestazione">
                                            <span class="mese-attesa-titolo">{$mese.titolo|escape}</span>
                                            <span class="mese-attesa-descrizione">Esplora i giorni di questo mese</span>
                                        </span>
                                        <span class="mese-attesa-conteggio">{$mese.totale_orari} {if $mese.totale_orari == 1}orario{else}orari{/if}</span>
                                        <span class="mese-attesa-indicatore" aria-hidden="true">+</span>
                                    </summary>
                                    <div class="giorni-attesa">
                                        <p class="guida-giorno-attesa">Seleziona un giorno per scoprire gli orari che puoi richiedere.</p>
                                        {foreach $mese.giorni as $giorno}
                                            <details class="giorno-attesa">
                                                <summary>
                                                    <span class="giorno-attesa-calendario" aria-hidden="true">{$giorno.data_iso|date_format:'%d'}</span>
                                                    <span class="giorno-attesa-intestazione">
                                                        <span class="giorno-attesa-data">{$giorno.titolo|escape}</span>
                                                        <span class="giorno-attesa-aiuto">Mostra gli orari</span>
                                                    </span>
                                                    <span class="giorno-attesa-indicatore" aria-hidden="true">⌄</span>
                                                </summary>
                                                <div class="orari-giorno-attesa" role="group" aria-label="Orari per {$giorno.titolo|escape}">
                                                    {foreach $giorno.slot as $slot}
                                                        <label class="slot-attesa-card">
                                                            <input type="radio" name="slot_attesa" value="{$slot.valore|escape}"
                                                                aria-label="{$giorno.titolo|escape}, {$slot.ora_inizio|escape}–{$slot.ora_fine|escape}"
                                                                data-riepilogo="{$slot.data|escape} · {$slot.ora_inizio|escape}–{$slot.ora_fine|escape}">
                                                            <span>{$slot.ora_inizio|escape}<span class="separatore-orario"> – </span>{$slot.ora_fine|escape}</span>
                                                        </label>
                                                    {/foreach}
                                                </div>
                                            </details>
                                        {/foreach}
                                    </div>
                                </details>
                            {/foreach}
                        </div>
                    </fieldset>
                    <div class="conferma-orario-attesa">
                        <p class="riepilogo-orario-attesa" role="status" aria-live="polite" hidden></p>
                        <button class="pulsante-attesa" type="submit">Conferma richiesta in lista d’attesa</button>
                    </div>
                </form>
            {else}
                <p class="empty-state">Al momento non ci sono orari occupati validi per la lista d’attesa.</p>
            {/if}
        </section>
    </main>
</body>
</html>
