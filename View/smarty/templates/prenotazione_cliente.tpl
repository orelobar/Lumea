<!DOCTYPE html>
<html lang="it">
<head>
    <base href="{$baseApplicazione|escape:'html'}">
    <link rel="icon" type="image/svg+xml" href="images/lumea.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumèa - Prenota</title>
    <link rel="stylesheet" href="css/interazioni.css?v=lumea-csrf-20261023">
    <link rel="stylesheet" href="css/cliente.css?v={$versioneCSSCliente|escape}">
</head>
<body class="area-cliente pagina-prenota-cliente">
    <header class="header-cliente">
        <h1>Lumèa</h1>
        {include file='nav_cliente.tpl' paginaAttiva='prenota'}
    </header>

    <main id="contenuto-principale" class="contenuto-cliente">
        {if !$utente->isPrenotazioniOnlineAbilitate()}
        <div class="titolo-pagina">
            <div><span class="sovratitolo">LA TUA PRENOTAZIONE</span>
                <h2>Prenota il tuo momento<span class="titolo-punto">.</span></h2>
                <p>Scegli il servizio, noi penseremo al resto.</p>
            </div>
        </div>
        <section class="blocco-prenotazioni-sospese" role="alert" aria-labelledby="prenotazioni-sospese-prenota">
            <span class="icona-sospensione" aria-hidden="true">!</span>
            <div>
                <span class="etichetta-sospensione">Prenotazione non disponibile</span>
                <h3 id="prenotazioni-sospese-prenota">Le prenotazioni online sono sospese</h3>
                <p>Non puoi creare nuovi appuntamenti o richieste in lista d’attesa finché il salone non riabilita il
                    tuo account.</p>
                <p class="nota-sospensione">No-show consecutivi registrati: <strong>{$utente->getNoShowConsecutivi()} / 3</strong>.
                </p>
                <a class="azione-principale" href="index.php?pagina=prenotazioni">Consulta le mie prenotazioni</a>
            </div>
        </section>

        <section aria-labelledby="storico-prenota-sospeso">
            <div class="intestazione-sezione">
                <h3 id="storico-prenota-sospeso">Ultimi appuntamenti</h3>
                <a href="index.php?pagina=prenotazioni">Vedi tutto</a>
            </div>
            {if $storicoPrenotazioni}
            <div class="elenco-appuntamenti-cliente">
                {foreach $storicoPrenotazioni as $appuntamento}
                <article class="appuntamento-cliente">
                    <div><strong>{$appuntamento.data|escape}</strong><span
                            class="badge-prenotazione">{$appuntamento.stato|escape}</span></div>
                    <p>{$appuntamento.servizio|escape} · {$appuntamento.ora_inizio|escape}–{$appuntamento.ora_fine|escape}</p>
                </article>
                {/foreach}
            </div>
            {else}
            <p class="empty-state">Nessun appuntamento nello storico.</p>
            {/if}
        </section>
        {elseif $step == 'inizio'}
        <div class="titolo-pagina">
            <div><span class="sovratitolo">LA TUA PRENOTAZIONE</span>
                <h2>Prenota il tuo momento<span class="titolo-punto">.</span></h2>
                <p>Scegli il servizio, noi penseremo al resto.</p>
            </div>
        </div>

        <section class="prenota-intro" aria-labelledby="come-prenotare">
            <div><span class="sovratitolo">PRENOTAZIONE ONLINE</span>
                <h3 id="come-prenotare">Il tuo prossimo appuntamento,<br><em>in pochi passaggi.</em></h3>
                <p>Da una categoria al riepilogo finale. Scegli con calma, confermi soltanto alla fine.</p><a
                    class="azione-principale" href="index.php?pagina=prenota&amp;nuova=1">Inizia a prenotare <span
                        aria-hidden="true">↗</span></a>
            </div>
            <div class="prenota-illustrazione" aria-hidden="true"><span>L</span><small>LA TUA ESPERIENZA</small></div>
        </section>
        <section class="pannello-cliente" aria-label="Come funziona la prenotazione">
            <ol class="passaggi-prenotazione passaggi-statici">
                <li>Categoria</li>
                <li>Servizio</li>
                <li>Data</li>
                <li>Ora</li>
                <li>Riepilogo</li>
                <li>Conferma</li>
            </ol>
        </section>


        <section aria-labelledby="storico-prenota">
            <div class="intestazione-sezione">
                <h3 id="storico-prenota">Ultimi appuntamenti</h3>
                <a href="index.php?pagina=prenotazioni">Vedi tutto</a>
            </div>
            {if $storicoPrenotazioni}
            <div class="elenco-appuntamenti-cliente">
                {foreach $storicoPrenotazioni as $appuntamento}
                <article class="appuntamento-cliente">
                    <div><strong>{$appuntamento.data|escape}</strong><span
                            class="badge-prenotazione">{$appuntamento.stato|escape}</span></div>
                    <p>{$appuntamento.servizio|escape} · {$appuntamento.ora_inizio|escape}–{$appuntamento.ora_fine|escape}</p>
                </article>
                {/foreach}
            </div>
            {else}
            <p class="empty-state">Nessun appuntamento nello storico.</p>
            {/if}
        </section>
        {else}
        <div class="titolo-pagina">
            <div><span class="sovratitolo">PRENOTAZIONE GUIDATA</span>
                <h2>Nuova prenotazione<span class="titolo-punto">.</span></h2>
                <p>Segui i passaggi per scegliere il tuo prossimo appuntamento.</p>
            </div>
            <a class="azione-secondaria" href="index.php?pagina=prenota">Esci dalla procedura ×</a>
        </div>

        <nav class="wizard-percorso" aria-label="Avanzamento prenotazione">
            <ol class="passaggi-prenotazione">
                <li class="{if $step == 'categoria'}corrente{/if}" {if $step == 'categoria'}aria-current="step"
                    {/if}><span>01</span> Categoria</li>
                <li class="{if $step == 'servizio'}corrente{elseif $step != 'categoria'}completato{/if}"
                    {if $step == 'servizio'}aria-current="step" {/if}><span>02</span> Servizio</li>
                <li class="{if $step == 'data'}corrente{elseif $step == 'ora' || $step == 'riepilogo'}completato{/if}"
                    {if $step == 'data'}aria-current="step" {/if}><span>03</span> Data</li>
                <li class="{if $step == 'ora'}corrente{elseif $step == 'riepilogo'}completato{/if}"
                    {if $step == 'ora'}aria-current="step" {/if}><span>04</span> Ora</li>
                <li class="{if $step == 'riepilogo'}corrente{/if}" {if $step == 'riepilogo'}aria-current="step"
                    {/if}><span>05</span> Riepilogo</li>
                <li class="passo-conferma"><span>06</span> Conferma</li>
            </ol>
        </nav>
        {if $errore !== null}<p class="messaggio-cliente errore" role="alert"><strong>{$errore|escape}</strong></p>
        {/if}

        {if $appuntamentoAttivo !== null}
        <p class="messaggio-cliente" role="status">
            Hai già un appuntamento attivo il {$appuntamentoAttivo->getData()|date_format:"%d/%m/%Y"}
            alle {$appuntamentoAttivo->getOra()|date_format:"%H:%M"}.
            Puoi cercare un altro orario per lo stesso servizio nella lista d’attesa.
        </p>
        {/if}

        {if $step == 'categoria'}
        <section class="pannello-cliente wizard-contenuto">
            <span class="sovratitolo">PASSAGGIO 01 / 06</span>
            <h3>Quale categoria preferisci?</h3>
            <p>Inizia scegliendo il tipo di servizio.</p>
            <div class="scelte-prenotazione">
                <a class="scelta-categoria" href="index.php?pagina=prenota&amp;nuova=1&amp;categoria=Uomo"><span
                        class="categoria-decorazione" aria-hidden="true">01</span><strong>Uomo</strong><span>Esplora i
                        servizi <b aria-hidden="true">↗</b></span></a>
                <a class="scelta-categoria" href="index.php?pagina=prenota&amp;nuova=1&amp;categoria=Donna"><span
                        class="categoria-decorazione" aria-hidden="true">02</span><strong>Donna</strong><span>Esplora i
                        servizi <b aria-hidden="true">↗</b></span></a>
            </div>
        </section>
        {elseif $step == 'servizio'}
        <section class="pannello-cliente wizard-contenuto">
            <span class="sovratitolo">PASSAGGIO 02 / 06</span>
            <h3>Quale servizio desideri?</h3>
            <p>Categoria: <strong>{$categoria|escape}</strong></p>
            {if $servizi}
            <div class="elenco-servizi-prenotazione">
                {foreach $servizi as $servizio}
                <a class="card-collegamento selettore-servizio-card"
                    href="index.php?pagina=prenota&amp;nuova=1&amp;categoria={$categoria|escape:'url'}&amp;servizio={$servizio->getId()}">
                    <strong>{$servizio->getNome()|escape}</strong>
                    <span>{$servizio->getDurata()} minuti · € {$servizio->getPrezzo()|string_format:"%.2f"}</span>
                    <span class="indicatore-scelta">Scegli il servizio <span aria-hidden="true">→</span></span>
                </a>
                {/foreach}
            </div>
            {else}
            <p class="empty-state">Nessun servizio disponibile per questa categoria.</p>
            {/if}
            <p class="wizard-indietro"><a href="index.php?pagina=prenota&amp;nuova=1">Indietro</a></p>
        </section>
        {elseif $step == 'data'}
        {if $appuntamentoAttivo === null}
        <section class="pannello-cliente wizard-contenuto">
            <span class="sovratitolo">PASSAGGIO 03 / 06</span>
            <h3>Scegli il giorno migliore.</h3>
            <p><strong>{$servizioSelezionato->getNome()|escape}</strong> · {$servizioSelezionato->getDurata()} minuti · € {$servizioSelezionato->getPrezzo()|string_format:"%.2f"}</p>
            {if $disponibilita}
            {include file='calendario_prenotazione.tpl'}
            {else}
            <p class="empty-state">Al momento non risultano date prenotabili.</p>
            {/if}
        </section>
        {/if}

        <section class="modalita-attesa-invito">
            <span class="sovratitolo">UN'ALTERNATIVA ALLA PRENOTAZIONE</span>
            <h3>{if $appuntamentoAttivo !== null}Preferisci un altro orario?{else}Non trovi l’orario
                giusto?{/if}</h3>
            <p>La lista d’attesa è un percorso separato: scegli un orario occupato e, se si libera, potrà esserti
                assegnato. Il tuo appuntamento attuale resta confermato fino all’eventuale sostituzione.</p>
            <a class="azione-principale"
                href="index.php?pagina=lista_attesa&amp;azione=nuova&amp;servizio={$servizioSelezionato->getId()}">Scegli un altro
                orario</a>
        </section>
        <p class="wizard-indietro"><a
                href="index.php?pagina=prenota&amp;nuova=1&amp;categoria={$categoria|escape:'url'}">Indietro</a></p>
        {elseif $step == 'ora'}
        <section class="pannello-cliente wizard-contenuto">
            <span class="sovratitolo">PASSAGGIO 04 / 06</span>
            <h3>A che ora ti va bene?</h3>
            <p>{$servizioSelezionato->getNome()|escape} · <strong>{$dataSelezionata|date_format:"%d/%m/%Y"}</strong></p>
            {if $orariData}
            <div class="scelte-orari">
                {foreach $orariData as $orario}
                <a
                    href="index.php?pagina=prenota&amp;nuova=1&amp;categoria={$categoria|escape:'url'}&amp;servizio={$servizioSelezionato->getId()}&amp;data={$dataSelezionata|escape:'url'}&amp;ora={$orario.oraInizio|escape:'url'}">{$orario.oraInizio|escape}–{$orario.oraFine|escape}</a>
                {/foreach}
            </div>
            {else}
            <p class="empty-state">Non ci sono più orari disponibili per questa data.</p>
            {/if}
            <details class="cambia-giorno-prenotazione">
                <summary>Cambia giorno</summary>
                {include file='calendario_prenotazione.tpl'}
            </details>
            <p class="wizard-indietro"><a
                    href="index.php?pagina=prenota&amp;nuova=1&amp;categoria={$categoria|escape:'url'}&amp;servizio={$servizioSelezionato->getId()}&amp;mese_calendario={$meseSelezionato|escape:'url'}">Indietro</a>
            </p>
        </section>
        {elseif $step == 'riepilogo'}
        <section class="pannello-cliente wizard-contenuto">
            <span class="sovratitolo">PASSAGGIO 05–06 / 06</span>
            <h3>Un ultimo controllo.</h3>
            <p>Verifica i dati e conferma. Il pagamento avverrà in salone.</p>
            <dl class="riepilogo-prenotazione riepilogo-verticale">
                <div>
                    <dt>Categoria</dt>
                    <dd>{$categoria|escape}</dd>
                </div>
                <div>
                    <dt>Servizio</dt>
                    <dd>{$servizioSelezionato->getNome()|escape}</dd>
                </div>
                <div>
                    <dt>Data</dt>
                    <dd>{$dataRiepilogo|escape}</dd>
                </div>
                <div>
                    <dt>Orario</dt>
                    <dd>{$oraSelezionata|escape}–{$oraFineRiepilogo|escape}</dd>
                </div>
                <div>
                    <dt>Durata</dt>
                    <dd>{$servizioSelezionato->getDurata()} minuti</dd>
                </div>
                <div>
                    <dt>Prezzo</dt>
                    <dd>€ {$servizioSelezionato->getPrezzo()|string_format:"%.2f"|replace:'.':','}</dd>
                </div>
            </dl>
            {if $puoPrenotare}
            <form class="js-form-conferma-prenotazione" method="post" action="index.php?pagina=prenota">
                <input type="hidden" name="csrf_token" value="{$csrfToken|escape:'html'}">
                <input type="hidden" name="categoria" value="{$categoria|escape}">
                <input type="hidden" name="azione" value="conferma_prenotazione">
                <input type="hidden" name="id_servizio" value="{$servizioSelezionato->getId()}">
                <input type="hidden" name="data" value="{$dataSelezionata|escape}">
                <input type="hidden" name="ora" value="{$oraSelezionata|escape}">
                <button class="bottone-conferma-prenotazione" type="submit"
                    data-testo-attesa="Prenotazione in corso…">Conferma prenotazione <span
                        aria-hidden="true">↗</span></button>
            </form>
            {else}
            <p>Hai già una prenotazione attiva. Puoi cercare un altro orario nella lista d’attesa.</p>
            {/if}
            <p class="wizard-indietro"><a
                    href="index.php?pagina=prenota&amp;nuova=1&amp;categoria={$categoria|escape:'url'}&amp;servizio={$servizioSelezionato->getId()}&amp;data={$dataSelezionata|escape:'url'}">Indietro</a>
            </p>
        </section>
        {/if}
        {/if}
    </main>
    <script src="js/prenotazione-cliente.js?v={$versioneJSPrenotazione|escape}" defer></script>
</body>
</html>
