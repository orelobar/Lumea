<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/svg+xml" href="images/lumea.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumèa - Appuntamenti di oggi</title>
    <link rel="stylesheet" href="css/interazioni.css?v=lumea20261008">
</head>
<body class="area-proprietario">
    <header class="header-proprietario">
        <h1>Lumèa</h1>
        {include file='nav_proprietario.tpl' paginaAttiva='dashboard'}
    </header>

    <main class="contenuto-proprietario appuntamenti-oggi-proprietario">
        <div class="titolo-pagina">
            <div>
                <span class="sovratitolo">Operatività di oggi</span>
                <h2>Appuntamenti di oggi</h2>
                <p>{$data_formattata|escape}</p>
            </div>
            <a class="pulsante-secondario collegamento-come-pulsante" href="index.php">Torna alla Dashboard</a>
        </div>

        {if $messaggio}<p class="messaggio-esito" role="status" data-temporanea="true">{$messaggio|escape}</p>{/if}
        {if $errore}<p class="messaggio-errore" role="alert">{$errore|escape}</p>{/if}

        <section class="testata-operativa-giornata" aria-label="Riepilogo degli appuntamenti di oggi">
            <a class="totale-giornata-operativa totale-giornata-collegamento {if $filtro_attivo == 'tutti'}filtro-giornata-attivo{/if}" href="index.php?pagina=appuntamenti_oggi" aria-label="Mostra tutti gli appuntamenti di oggi">
                <span class="sovratitolo">Totale giornata</span>
                <strong>{$conteggi.prenotata + $conteggi.completata + $conteggi.no_show}</strong>
                <span>{if ($conteggi.prenotata + $conteggi.completata + $conteggi.no_show) == 1}appuntamento{else}appuntamenti{/if}</span>
            </a>

            <nav class="filtri-stato-giornata" aria-label="Filtra gli appuntamenti di oggi">
                <a class="filtro-giornata filtro-da-svolgere {if $filtro_attivo == 'da_svolgere'}filtro-giornata-attivo{/if}" href="index.php?pagina=appuntamenti_oggi&amp;filtro=da_svolgere">
                    <span>Da svolgere</span>
                    <strong>{$conteggi.prenotata}</strong>
                </a>
                <a class="filtro-giornata filtro-completati {if $filtro_attivo == 'completati'}filtro-giornata-attivo{/if}" href="index.php?pagina=appuntamenti_oggi&amp;filtro=completati">
                    <span>Completati</span>
                    <strong>{$conteggi.completata}</strong>
                </a>
                <a class="filtro-giornata filtro-assenze {if $filtro_attivo == 'assenze'}filtro-giornata-attivo{/if}" href="index.php?pagina=appuntamenti_oggi&amp;filtro=assenze">
                    <span>Assenze</span>
                    <strong>{$conteggi.no_show}</strong>
                </a>
            </nav>
        </section>

        <section class="pannello-proprietario elenco-operativo-giornata" aria-labelledby="elenco-oggi">
            <div class="intestazione-pannello">
                <div>
                    <span class="sovratitolo">Giornata operativa</span>
                    <h3 id="elenco-oggi">{$titolo_elenco|escape}</h3>
                </div>
                <div class="azioni-intestazione-giornata">
                    {if $filtro_attivo != 'tutti'}
                        <a class="link-secondario" href="index.php?pagina=appuntamenti_oggi">Mostra tutti</a>
                    {/if}
                    <span class="contatore-righe">{$appuntamenti|@count}</span>
                </div>
            </div>

            <div class="lista-appuntamenti-oggi">
                {foreach $appuntamenti as $riga}
                    <article class="scheda-appuntamento-oggi stato-card-{$riga.stato|escape}">
                        <div class="ora-appuntamento-oggi">
                            <time>{$riga.ora|escape}</time>
                            <span>Oggi</span>
                        </div>

                        <div class="corpo-appuntamento-oggi">
                            <div class="testata-appuntamento-oggi">
                                <div>
                                    <h4>{$riga.cliente|escape}</h4>
                                    <a href="tel:{$riga.telefono|escape}">{$riga.telefono|escape}</a>
                                </div>
                                <span class="badge-stato stato-{$riga.stato|escape}">{$riga.stato_etichetta|escape}</span>
                            </div>

                            <dl class="dati-appuntamento-oggi">
                                <div><dt>Servizio</dt><dd>{$riga.servizio|escape}</dd></div>
                                <div><dt>Prezzo</dt><dd>€ {$riga.prezzo|string_format:"%.2f"}</dd></div>
                            </dl>

                            {if $riga.puo_aggiornare}
                                <form class="azioni-appuntamento azioni-appuntamento-oggi" method="post" action="index.php?pagina=agenda">
                                    <input type="hidden" name="id_prenotazione" value="{$riga.id}">
                                    <input type="hidden" name="data" value="{$data|escape}">
                                    <input type="hidden" name="ritorno" value="appuntamenti_oggi">
                                    <input type="hidden" name="filtro_ritorno" value="{$filtro_attivo|escape}">
                                    <label>
                                        <span>Coupon cliente (facoltativo)</span>
                                        <input name="coupon" placeholder="Codice coupon">
                                    </label>
                                    <div class="gruppo-pulsanti">
                                        <button class="pulsante-primario" name="azione" value="chiudi_appuntamento">Completato</button>
                                        <button class="pulsante-secondario" name="azione" value="segna_assenza">Non presentato</button>
                                    </div>
                                </form>
                            {/if}
                        </div>
                    </article>
                {foreachelse}
                    <div class="empty-state-proprietario empty-state-grande">
                        <span class="empty-state-icona" aria-hidden="true">◷</span>
                        <div>
                            <strong>Nessun appuntamento in questa vista</strong>
                            <p>{if $filtro_attivo == 'tutti'}Oggi non ci sono appuntamenti.{else}Non ci sono appuntamenti con questo stato.{/if}</p>
                        </div>
                    </div>
                {/foreach}
            </div>
        </section>
    </main>
</body>
</html>
