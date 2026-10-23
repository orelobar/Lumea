<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/svg+xml" href="images/lumea.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumèa - Clienti</title>
    <link rel="stylesheet" href="css/interazioni.css?v=lumea-csrf-20261023">
</head>
<body class="area-proprietario">
    <header class="header-proprietario">
        <h1>Lumèa</h1>
        {include file='nav_proprietario.tpl' paginaAttiva='clienti'}
    </header>

    <main class="contenuto-proprietario clienti-proprietario">
        <div class="titolo-pagina">
            <div>
                <span class="sovratitolo">Rubrica del salone</span>
                <h2>Clienti</h2>
                <p>Consulta i recapiti e lo storico degli appuntamenti.</p>
            </div>
            <span class="pill-giornata">{$clienti|@count} clienti</span>
        </div>

        {if $messaggio}<p class="messaggio-esito" role="status" data-temporanea="true">{$messaggio|escape}</p>
        {/if}
        {if $errore}<p class="messaggio-errore" role="alert">{$errore|escape}</p>{/if}

        {if $dettaglio}
        <section class="pannello-proprietario dettaglio-cliente" aria-labelledby="dettaglio-cliente">
            <div class="intestazione-pannello">
                <div>
                    <span class="sovratitolo">Scheda cliente</span>
                    <h3 id="dettaglio-cliente">{$dettaglio.cliente->getNome()|escape} {$dettaglio.cliente->getCognome()|escape}</h3>
                </div>
                <a class="link-secondario" href="index.php?pagina=clienti">Chiudi dettaglio ×</a>
            </div>

            <dl class="dati-cliente">
                <div>
                    <dt>Email</dt>
                    <dd>{$dettaglio.cliente->getEmail()|escape}</dd>
                </div>
                <div>
                    <dt>Telefono</dt>
                    <dd>{$dettaglio.cliente->getTelefono()|escape}</dd>
                </div>
                <div>
                    <dt>Prenotazioni online</dt>
                    <dd>
                        <span
                            class="badge-stato {if $dettaglio.cliente->isPrenotazioniOnlineAbilitate()}stato-attivo{else}stato-inattivo{/if}">
                            {if $dettaglio.cliente->isPrenotazioniOnlineAbilitate()}Abilitate{else}Sospese{/if}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt>No-show totali</dt>
                    <dd><strong>{$dettaglio.no_show_totali}</strong></dd>
                </div>
                <div>
                    <dt>No-show consecutivi</dt>
                    <dd><strong>{$dettaglio.no_show_consecutivi} / 3</strong></dd>
                </div>
            </dl>

            {if !$dettaglio.cliente->isPrenotazioniOnlineAbilitate()}
            <div class="avviso-sospensione-proprietario" role="status">
                <strong>Prenotazioni online sospese</strong>
                <span>La sospensione scatta automaticamente al terzo no-show consecutivo. Il salone può riabilitare il
                    cliente da questa scheda.</span>
            </div>
            {/if}

            {if !$dettaglio.cliente->isPrenotazioniOnlineAbilitate()}
            <form class="azione-riabilitazione" method="post" action="index.php?pagina=clienti">
                <input type="hidden" name="csrf_token" value="{$csrfToken|escape:'html'}">
                <input type="hidden" name="id_cliente" value="{$dettaglio.cliente->getId()}">
                <button class="pulsante-primario" name="azione" value="riabilita_cliente">Riabilita prenotazioni
                    online</button>
            </form>
            {/if}

            <div class="sottosezione-cliente sezione-coupon-proprietario" aria-labelledby="coupon-del-cliente">
                <div class="intestazione-pannello">
                    <div><span class="sovratitolo">Premi e fedeltà</span>
                        <h4 id="coupon-del-cliente">Coupon del cliente</h4>
                    </div><span class="pill-giornata">{$dettaglio.coupon_disponibili} disponibili · € {$dettaglio.credito_coupon|string_format:"%.2f"}
                        residui</span>
                </div>
                <p class="nota-coupon-proprietario">Consultazione dello storico coupon. I coupon vengono generati
                    automaticamente dalla fedeltà e utilizzati soltanto al pagamento in salone.</p>
                {if $dettaglio.coupon}
                <div class="elenco-coupon-proprietario">
                    {foreach $dettaglio.coupon as $coupon}
                    <article
                        class="scheda-coupon-proprietario{if !$coupon.disponibile} scheda-coupon-esaurita{/if}">
                        <div class="scheda-coupon-testa">
                            <div><small>CODICE COUPON</small><strong>{$coupon.codice|escape}</strong></div><span
                                class="badge-stato {if $coupon.disponibile}stato-attivo{else}stato-inattivo{/if}">{$coupon.stato|escape}</span>
                        </div>
                        <dl class="dati-coupon-proprietario">
                            <div>
                                <dt>Valore iniziale</dt>
                                <dd>€ {$coupon.valore_iniziale|string_format:"%.2f"}</dd>
                            </div>
                            <div>
                                <dt>Credito residuo</dt>
                                <dd><strong>€ {$coupon.credito_residuo|string_format:"%.2f"}</strong></dd>
                            </div>
                            <div>
                                <dt>Generato il</dt>
                                <dd>{$coupon.data_generazione|escape}</dd>
                            </div>
                            <div>
                                <dt>Premio fedeltà</dt>
                                <dd>{$coupon.traguardo} appuntamenti</dd>
                            </div>
                        </dl>
                        {if $coupon.usato_parzialmente}<p class="nota-coupon-stato">Utilizzato in parte · credito ancora
                            disponibile</p>{elseif !$coupon.disponibile}<p class="nota-coupon-stato">Credito esaurito</p>
                        {/if}
                    </article>
                    {/foreach}
                </div>
                {else}<div class="empty-state-proprietario"><span aria-hidden="true">◇</span>
                    <div><strong>Nessun coupon generato</strong>
                        <p>Il cliente non ha ancora maturato premi fedeltà.</p>
                    </div>
                </div>{/if}
            </div>

            <div class="sottosezione-cliente">
                <h4>Appuntamenti del cliente</h4>
                <div class="elenco-appuntamenti-cliente">
                    {foreach $dettaglio.appuntamenti as $appuntamento}
                    <article class="appuntamento-cliente">
                        <div>
                            <time>{$appuntamento.data|escape}</time>
                            <span class="badge-stato">{$appuntamento.stato|escape}</span>
                        </div>
                        <p><strong>{$appuntamento.servizio|escape}</strong><span>{$appuntamento.ora_inizio|escape}–{$appuntamento.ora_fine|escape}</span>
                        </p>
                    </article>
                    {foreachelse}
                    <div class="empty-state-proprietario"><span aria-hidden="true">◇</span>
                        <div><strong>Nessun appuntamento</strong>
                            <p>Lo storico del cliente è ancora vuoto.</p>
                        </div>
                    </div>
                    {/foreach}
                </div>
            </div>
        </section>
        {/if}

        <section class="pannello-proprietario" aria-labelledby="elenco-clienti">
            <div class="intestazione-pannello">
                <div>
                    <span class="sovratitolo">Anagrafica</span>
                    <h3 id="elenco-clienti">Elenco clienti</h3>
                </div>
            </div>

            <div class="elenco-clienti-proprietario">
                {foreach $clienti as $cliente}
                {assign var=idCliente value=$cliente->getId()}
                <article class="scheda-cliente">
                    <div class="avatar-cliente" aria-hidden="true">♙</div>
                    <div class="identita-cliente">
                        <strong>{$cliente->getNome()|escape} {$cliente->getCognome()|escape}</strong>
                        <span>{$cliente->getEmail()|escape}</span>
                        <span>{$cliente->getTelefono()|escape}</span>
                    </div>
                    <div class="riepilogo-no-show-cliente">
                        <span><strong>{$statistiche_clienti[$idCliente].no_show_totali|default:0}</strong> no-show totali</span>
                        <span><strong>{$statistiche_clienti[$idCliente].no_show_consecutivi|default:0}/3</strong> consecutivi</span>
                    </div>
                    <span
                        class="badge-stato {if $cliente->isPrenotazioniOnlineAbilitate()}stato-attivo{else}stato-inattivo{/if}">
                        {if $cliente->isPrenotazioniOnlineAbilitate()}Prenotazioni abilitate{else}Prenotazioni
                        sospese{/if}
                    </span>
                    <a class="link-dettaglio-cliente"
                        href="index.php?pagina=clienti&amp;id_cliente={$cliente->getId()}">Vedi dettagli <span
                            aria-hidden="true">→</span></a>
                </article>
                {foreachelse}
                <div class="empty-state-proprietario empty-state-grande"><span class="empty-state-icona"
                        aria-hidden="true">♙</span>
                    <div><strong>Nessun cliente registrato</strong>
                        <p>I clienti appariranno qui dopo la registrazione.</p>
                    </div>
                </div>
                {/foreach}
            </div>
        </section>
    </main>
</body>
</html>
