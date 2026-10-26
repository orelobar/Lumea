<!DOCTYPE html>
<html lang="it">
<head>
    <base href="{$baseApplicazione|escape:'html'}">
    <link rel="icon" type="image/svg+xml" href="images/lumea.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumèa · La mia area</title>
    <link rel="stylesheet" href="css/interazioni.css?v=lumea-csrf-20261023">
    <link rel="stylesheet" href="css/cliente.css?v=lumea-csrf-20261023">
</head>
<body class="area-cliente pagina-dashboard-cliente">
    <header class="header-cliente">
        <h1>Lumèa <span>· Area cliente</span></h1>{include file='nav_cliente.tpl' paginaAttiva='dashboard'}
    </header>
    <main id="contenuto-principale" class="contenuto-cliente">
        <div class="intro-cliente">
            <div><span class="sovratitolo">IL TUO SPAZIO · LUMÈA</span>
                <h2>Ciao, {$utente->getNome()|escape}<span class="titolo-punto">.</span></h2>
                <p>Il tuo tempo, i tuoi appuntamenti, i tuoi vantaggi. Tutto a portata di mano.</p>
            </div><a class="azione-secondaria intro-link" href="index.php?pagina=prenotazioni">Gestisci appuntamenti
                <span aria-hidden="true">↗</span></a>
        </div>
        {if !$prenotazioniOnlineAbilitate}
        <section class="blocco-prenotazioni-sospese" role="status" aria-labelledby="sospensione-dashboard"><span
                class="icona-sospensione" aria-hidden="true">!</span>
            <div><span class="etichetta-sospensione">Accesso limitato</span>
                <h3 id="sospensione-dashboard">Prenotazioni online sospese</h3>
                <p>Non puoi prenotare né inviare nuove richieste in lista d’attesa. Puoi continuare a consultare
                    appuntamenti, coupon e spese.</p>
                <p class="nota-sospensione">No-show consecutivi: <strong>{$utente->getNoShowConsecutivi()} / 3</strong>. Contatta il
                    salone per la riabilitazione.</p>
            </div>
        </section>
        {/if}
        <div class="dashboard-griglia-hero">
            {if $prossimoAppuntamentoDettaglio !== null}
            <a class="card-prossimo-appuntamento card-collegamento" href="index.php?pagina=prenotazioni"><span
                    class="hero-corner" aria-hidden="true">✳</span><span class="eyebrow-chiaro">IL TUO PROSSIMO
                    APPUNTAMENTO <span aria-hidden="true">↗</span></span><span
                    class="appuntamento-evidenza"><strong>{$prossimoAppuntamentoDettaglio.data|escape}</strong><span>{$prossimoAppuntamentoDettaglio.ora_inizio|escape} –
                        {$prossimoAppuntamentoDettaglio.ora_fine|escape}</span></span><span class="hero-servizio">{$prossimoAppuntamentoDettaglio.servizio|escape} <i
                        aria-hidden="true">•</i> {$prossimoAppuntamentoDettaglio.durata} minuti</span><span class="hero-foot">Vedi
                    dettagli e opzioni <span aria-hidden="true">→</span></span></a>
            {elseif $appuntamentoDaChiudere}
            <section class="card-prossimo-appuntamento hero-vuoto" aria-labelledby="appuntamento-in-attesa">
                <span class="hero-corner" aria-hidden="true">✳</span>
                <span class="eyebrow-chiaro">IL TUO APPUNTAMENTO</span>
                <h3 id="appuntamento-in-attesa">In attesa di conferma del salone.</h3>
                <p>Il salone deve ancora registrare l’esito del tuo appuntamento. Puoi consultarne i dettagli nelle tue
                    prenotazioni.</p>
                <a class="btn-su-scuro" href="index.php?pagina=prenotazioni">Vedi appuntamento <span
                        aria-hidden="true">↗</span></a>
            </section>
            {else}
            <section class="card-prossimo-appuntamento hero-vuoto" aria-labelledby="prossimo-appuntamento"><span
                    class="hero-corner" aria-hidden="true">✳</span><span class="eyebrow-chiaro">IL TUO PROSSIMO
                    APPUNTAMENTO</span>
                <h3 id="prossimo-appuntamento">Il tuo momento<br>ti aspetta.</h3>
                <p>Non hai appuntamenti attivi al momento.</p>{if $prenotazioniOnlineAbilitate}<a class="btn-su-scuro"
                    href="index.php?pagina=prenota&amp;nuova=1">Scegli il tuo servizio <span
                        aria-hidden="true">↗</span></a>{else}<span class="hero-servizio">Prenotazioni
                    online sospese</span>{/if}
            </section>
            {/if}
            <section class="dashboard-scorciatoie" aria-labelledby="azioni-rapide-cliente">
                <div class="titolo-blocco"><span class="sovratitolo">TUTTO IN UN CLICK</span>
                    <h3 id="azioni-rapide-cliente">Cosa vuoi fare?</h3>
                </div>
                <nav class="azioni-rapide" aria-label="Scorciatoie cliente">
                    {if !$prenotazioniOnlineAbilitate}<span class="azione-rapida-disabilitata"><span class="rapida-icona"
                            aria-hidden="true">✳</span><span><strong>Prenotazioni sospese</strong><small>Contatta il
                                salone</small></span></span>{elseif $appuntamentoDaChiudere}<a
                        href="index.php?pagina=prenotazioni"><span class="rapida-icona"
                            aria-hidden="true">✳</span><span><strong>Appuntamento da confermare</strong><small>Consulta
                                i dettagli</small></span><b aria-hidden="true">↗</b></a>{elseif $prossimoAppuntamentoDettaglio !== null}<a
                        href="index.php?pagina=prenotazioni"><span class="rapida-icona"
                            aria-hidden="true">✳</span><span><strong>Appuntamento già confermato</strong><small>Una
                                prenotazione attiva alla volta</small></span><b
                            aria-hidden="true">↗</b></a>{else}<a class="azione-rapida-primaria"
                        href="index.php?pagina=prenota&amp;nuova=1"><span class="rapida-icona"
                            aria-hidden="true">✳</span><span><strong>Prenota un servizio</strong><small>Trova il tuo
                                prossimo orario</small></span><b aria-hidden="true">↗</b></a>{/if}
                    <a href="index.php?pagina=prenotazioni"><span class="rapida-icona"
                            aria-hidden="true">▤</span><span><strong>Le mie prenotazioni</strong><small>Dettagli e
                                storico</small></span><b aria-hidden="true">↗</b></a>
                    <a href="index.php?pagina=coupon"><span class="rapida-icona"
                            aria-hidden="true">◇</span><span><strong>I miei coupon</strong><small>Premi e credito
                                disponibile</small></span><b aria-hidden="true">↗</b></a>
                    <a href="index.php?pagina=lista_attesa"><span class="rapida-icona"
                            aria-hidden="true">◷</span><span><strong>Lista d’attesa</strong><small>Segui le tue
                                richieste</small></span><b aria-hidden="true">↗</b></a>
                    {if $ultimaPrenotazioneRipetibile !== null && $prenotazioniOnlineAbilitate && $prossimoAppuntamentoDettaglio === null}<a
                        href="index.php?pagina=prenota&amp;azione=ripeti&amp;idPrenotazione={$ultimaPrenotazioneRipetibile->getId()|escape}"><span
                            class="rapida-icona" aria-hidden="true">↻</span><span><strong>Ripeti ultimo
                                servizio</strong><small>Ritrova il tuo trattamento</small></span><b
                            aria-hidden="true">↗</b></a>{/if}
                </nav>
            </section>
        </div>
        {if $prossimoAppuntamentoDettaglio !== null && $prenotazioniOnlineAbilitate}<div class="suggerimento-cliente"><span class="suggerimento-icona"
                aria-hidden="true">↗</span>
            <div><strong>Un orario migliore per te?</strong>
                <p>Puoi richiederne un altro senza perdere l’appuntamento già confermato.</p>
            </div><a class="azione-secondaria"
                href="index.php?pagina=lista_attesa&amp;azione=nuova&amp;servizio={$prossimoAppuntamentoDettaglio.servizio_id}">Cerca un altro
                orario <span aria-hidden="true">→</span></a>
        </div>{/if}
        {if $riepilogoCliente !== null}<div class="dashboard-griglia-dati">
            <section class="pannello-cliente fedelta-sintesi" aria-labelledby="fedelta-dashboard">
                <div class="testata-pannello">
                    <div><span class="sovratitolo">PREMI FEDELTÀ</span>
                        <h3 id="fedelta-dashboard">Ogni visita conta.</h3>
                    </div><a href="index.php?pagina=coupon" aria-label="Vedi tutti i tuoi coupon">Vedi coupon ↗</a>
                </div>
                <div class="fedelta-valore"><strong>{$riepilogoCliente.fedelta.nel_ciclo}<small> /
                            {$riepilogoCliente.fedelta.obiettivo}</small></strong><span>Appuntamenti nel percorso attuale</span></div>
                <progress value="{$riepilogoCliente.fedelta.nel_ciclo}" max="{$riepilogoCliente.fedelta.obiettivo}">{$riepilogoCliente.fedelta.percentuale}%</progress>
                <p>Mancano <strong>{$riepilogoCliente.fedelta.appuntamenti_mancanti}</strong> appuntamenti per un coupon da <strong>€
                        {$riepilogoCliente.fedelta.valore_prossimo_coupon|string_format:"%.2f"}</strong>.</p>
                <div class="fedelta-footer"><span>Coupon disponibili
                        <strong>{$riepilogoCliente.coupon_disponibili}</strong></span><span>Credito <strong>€
                            {$riepilogoCliente.credito_coupon|string_format:"%.2f"}</strong></span></div>
            </section>
            <section class="pannello-cliente riepilogo-spese" aria-labelledby="spese-dashboard">
                <div class="testata-pannello">
                    <div><span class="sovratitolo">LA TUA ATTIVITÀ</span>
                        <h3 id="spese-dashboard">Le tue spese</h3>
                    </div><a href="index.php?pagina=spese">Vedi dettaglio ↗</a>
                </div>
                <div class="griglia-riepilogo compatta"><a class="dato-riepilogo card-collegamento"
                        href="index.php?pagina=spese&amp;periodo=settimana"><span>Questa settimana</span><strong>€
                            {$riepilogoCliente.spese.settimana|string_format:"%.2f"}</strong><small>Esplora <span aria-hidden="true">↗</span></small></a><a
                        class="dato-riepilogo card-collegamento"
                        href="index.php?pagina=spese&amp;periodo=mese"><span>{$riepilogoCliente.spese.mese_nome|escape}</span><strong>€
                            {$riepilogoCliente.spese.mese|string_format:"%.2f"}</strong><small>Esplora <span aria-hidden="true">↗</span></small></a><a
                        class="dato-riepilogo card-collegamento"
                        href="index.php?pagina=spese&amp;periodo=anno"><span>Anno {$riepilogoCliente.spese.anno_numero}</span><strong>€
                            {$riepilogoCliente.spese.anno|string_format:"%.2f"}</strong><small>Esplora <span aria-hidden="true">↗</span></small></a>
                </div>
            </section>
        </div>{/if}
    </main>
</body>
</html>
