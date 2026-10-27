<!DOCTYPE html>
<html lang="it">
<head>
    <base href="{$baseApplicazione|escape:'html'}">
    <link rel="icon" type="image/svg+xml" href="images/lumea.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumèa - Agenda</title>
    <link rel="stylesheet" href="css/interazioni.css?v=lumea-csrf-20261023">
    <link rel="stylesheet" href="css/agenda-annullamento.css?v=20261026">
</head>
<body class="area-proprietario">
    <header class="header-proprietario">
        <h1>Lumèa</h1>
        {include file='nav_proprietario.tpl' paginaAttiva='agenda'}
    </header>

    <main class="contenuto-proprietario agenda-proprietario">
        <div class="titolo-pagina">
            <div>
                <span class="sovratitolo">Organizzazione</span>
                <h2>Agenda</h2>
                <p>Consulta e aggiorna gli appuntamenti della giornata.</p>
            </div>
        </div>

        {if $messaggio}
        <p class="messaggio-esito" role="status" data-temporanea="true">{$messaggio|escape}</p>
        {/if}
        {if $errore}
        <p class="messaggio-errore" role="alert">{$errore|escape}</p>
        {/if}

        <section class="pannello-proprietario barra-filtri" aria-label="Seleziona giornata">
            {assign var=mesiAgenda value=[1=>"Gennaio",2=>"Febbraio",3=>"Marzo",4=>"Aprile",5=>"Maggio",6=>"Giugno",7=>"Luglio",8=>"Agosto",9=>"Settembre",10=>"Ottobre",11=>"Novembre",12=>"Dicembre"]}
            <form class="selettore-giornata" method="get" action="index.php" novalidate>
                <input type="hidden" name="pagina" value="agenda">
                <label>
                    <span>Giorno</span>
                    <input class="giorno-agenda" type="text" name="giorno" value="{$data|date_format:"%d"}"
                        inputmode="numeric" maxlength="2" autocomplete="off" aria-describedby="errore-giorno-agenda"
                        required>
                    <small id="errore-giorno-agenda" class="errore-agenda-lumea" aria-live="polite" hidden></small>
                </label>
                <label>
                    <span>Mese</span>
                    <select name="mese">
                        {foreach $mesiAgenda as $numero => $nome}
                        <option value="{$numero}" {if $numero == $data|date_format:"%m"}selected{/if}>
                            {$nome}</option>
                        {/foreach}
                    </select>
                </label>
                <label>
                    <span>Anno</span>
                    <input class="anno-agenda" type="text" name="anno" value="{$data|date_format:"%Y"}" inputmode="numeric"
                        maxlength="4" autocomplete="off" aria-describedby="errore-anno-agenda" required>
                    <small id="errore-anno-agenda" class="errore-agenda-lumea" aria-live="polite" hidden></small>
                </label>
                <button class="pulsante-primario">Mostra appuntamenti</button>
            </form>
        </section>

        <section class="pannello-proprietario" aria-labelledby="agenda-giornata">
            <div class="intestazione-pannello">
                <div>
                    <span class="sovratitolo">Programma giornaliero</span>
                    <h3 id="agenda-giornata">{$data|date_format:"%d/%m/%Y"}</h3>
                </div>
                <span class="contatore-righe">{$righe|@count}
                    {if $righe|@count == 1}appuntamento{else}appuntamenti{/if}</span>
            </div>

            <div class="agenda-timeline">
                {foreach $fasceAgenda as $fascia}
                <section class="agenda-fascia" aria-label="Orari {$fascia.inizio}–{$fascia.fine}">
                    <div class="agenda-fascia-testata"><strong>{$fascia.inizio} –
                            {$fascia.fine}</strong><span>Orari e appuntamenti</span></div>
                    <div class="agenda-orario"
                        style="--minuti-fascia: {$fascia.minuti}; --colonne: {$fascia.colonne}">
                        {foreach $fascia.tacche as $tacca}
                        <div class="agenda-tacca" style="--offset: {$tacca.offset}">
                            <time>{$tacca.ora}</time><span></span></div>
                        {/foreach}
                        {foreach $fascia.liberi as $libero}
                        <div class="agenda-libero"
                            style="--offset: {$libero.offset}; --durata: {$libero.durata}"
                            aria-label="Intervallo libero {$libero.inizio}–{$libero.fine}">
                            <strong>{$libero.inizio}–{$libero.fine}</strong><span>Libero</span></div>
                        {/foreach}
                        {foreach $fascia.righe as $riga}
                        {assign var=p value=$riga.prenotazione}
                        {if $p->getStato() == 'prenotata'}
                        {assign var=statoAgenda value='Da svolgere'}
                        {elseif $p->getStato() == 'completata'}
                        {assign var=statoAgenda value='Completato'}
                        {elseif $p->getStato() == 'no-show'}
                        {assign var=statoAgenda value='Non presentato'}
                        {else}
                        {assign var=statoAgenda value='Annullato'}
                        {/if}
                        <article class="agenda-card agenda-card--{$p->getStato()|replace:'-':'_'}"
                            style="--offset: {$riga.offset}; --durata: {$riga.durata_visuale}; --colonna: {$riga.colonna}">
                            <div class="agenda-card-info">
                                <h4 class="agenda-evento-orario">{$p->getOra()|date_format:"%H:%M"}–{$riga.ora_fine} ·
                                    {$p->getNomeServizio()|escape}</h4>
                                <p class="agenda-evento-cliente"><a
                                        href="index.php?pagina=clienti&amp;id_cliente={$p->getCliente()->getId()}">{$p->getCliente()->getNome()|escape}
                                        {$p->getCliente()->getCognome()|escape}</a><span> · {$p->getDurata()} min</span></p>
                            </div>
                            <span class="agenda-card-stato stato-{$p->getStato()|replace:'-':'_'}">{$statoAgenda}</span>
                            <button type="button" class="agenda-apri"
                                data-apri-agenda="agenda-dialog-{$p->getId()}" aria-haspopup="dialog"
                                aria-controls="agenda-dialog-{$p->getId()}">
                                {if $riga.puo_chiudere || $riga.puo_annullare}Gestisci{else}Dettagli{/if}
                            </button>
                            <dialog class="agenda-dialog" id="agenda-dialog-{$p->getId()}"
                                aria-labelledby="agenda-dialog-titolo-{$p->getId()}">
                                <div class="agenda-dialog-testata">
                                    <div>
                                        <span class="sovratitolo">{$data|date_format:"%d/%m/%Y"} · {$p->getOra()|date_format:"%H:%M"}</span>
                                        <h3 id="agenda-dialog-titolo-{$p->getId()}">{$p->getCliente()->getNome()|escape}
                                            {$p->getCliente()->getCognome()|escape}</h3>
                                    </div>
                                    <button type="button" class="agenda-chiudi" data-chiudi-agenda autofocus
                                        aria-label="Chiudi dettagli appuntamento">×</button>
                                </div>
                                <p class="agenda-dialog-servizio">{$p->getNomeServizio()|escape} · {$p->getDurata()} minuti
                                </p>
                                <dl class="agenda-dialog-dati">
                                    <div>
                                        <dt>Telefono</dt>
                                        <dd><a href="tel:{$p->getCliente()->getTelefono()|escape:'html'}">{$p->getCliente()->getTelefono()|escape}</a></dd>
                                    </div>
                                    <div>
                                        <dt>Stato</dt>
                                        <dd>{$statoAgenda}</dd>
                                    </div>
                                    <div>
                                        <dt>Prezzo</dt>
                                        <dd>€ {$p->getPrezzo()|string_format:"%.2f"}</dd>
                                    </div>
                                    <div>
                                        <dt>Incasso effettivo</dt>
                                        <dd>{if $riga.pagamento}€ {$riga.pagamento->getImportoPagato()|string_format:"%.2f"}{else}Non
                                            registrato{/if}</dd>
                                    </div>
                                </dl>
                                {if $riga.puo_chiudere}
                                <form class="agenda-dialog-form" method="post" action="index.php?pagina=agenda">
                                    <input type="hidden" name="csrf_token" value="{$csrfToken|escape:'html'}">
                                    <input type="hidden" name="id_prenotazione" value="{$p->getId()}">
                                    <input type="hidden" name="data" value="{$data|escape}">
                                    <label for="coupon-agenda-{$p->getId()}">Coupon facoltativo</label>
                                    <input id="coupon-agenda-{$p->getId()}" name="coupon"
                                        placeholder="Inserisci il codice" autocomplete="off">
                                    <div class="agenda-dialog-azioni">
                                        <button class="pulsante-primario" name="azione"
                                            value="chiudi_appuntamento">Segna completato</button>
                                        <button class="pulsante-secondario" name="azione" value="segna_assenza">Non
                                            presentato</button>
                                    </div>
                                </form>
                                {/if}
                                {if $riga.puo_annullare}
                                <section class="annullamento-agenda" aria-labelledby="annullamento-titolo-{$p->getId()}">
                                    <h4 id="annullamento-titolo-{$p->getId()}">Vuoi annullare questo appuntamento?</h4>
                                    <p>L’orario verrà liberato e potrà essere assegnato a un cliente in lista d’attesa.</p>
                                    <form method="post" action="index.php?pagina=agenda">
                                        <input type="hidden" name="csrf_token" value="{$csrfToken|escape:'html'}">
                                        <input type="hidden" name="id_prenotazione" value="{$p->getId()}">
                                        <input type="hidden" name="data" value="{$data|escape}">
                                        <div class="annullamento-agenda-azioni">
                                            <button type="submit" class="pulsante-secondario" formmethod="dialog"
                                                value="mantieni">Mantieni appuntamento</button>
                                            <button type="submit" class="pulsante-pericolo" name="azione"
                                                value="annulla_appuntamento">Sì, annulla</button>
                                        </div>
                                    </form>
                                </section>
                                {/if}
                            </dialog>
                        </article>
                        {/foreach}
                    </div>
                </section>
                {foreachelse}
                <div class="empty-state-proprietario empty-state-grande">
                    <span class="empty-state-icona" aria-hidden="true">◷</span>
                    <div>
                        <strong>Nessun appuntamento previsto per questa giornata</strong>
                        <p>Seleziona un altro giorno per consultare l’agenda.</p>
                    </div>
                </div>
                {/foreach}
            </div>
        </section>
    </main>
    <script src="js/agenda-proprietario.js?v=lumea-revisione-20261012" defer></script>
</body>
</html>
