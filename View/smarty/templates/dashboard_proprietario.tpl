<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/svg+xml" href="images/lumea.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumèa - Dashboard proprietario</title>
    <link rel="stylesheet" href="css/interazioni.css?v=lumea-csrf-20261023">
</head>
<body class="area-proprietario">
    <header class="header-proprietario">
        <h1>Lumèa</h1>
        {include file='nav_proprietario.tpl' paginaAttiva='dashboard'}
    </header>

    <main class="contenuto-proprietario dashboard-proprietario">
        <div class="titolo-pagina titolo-dashboard">
            <div>
                <span class="sovratitolo">Panoramica del salone</span>
                <h2>{$dashboard.saluto|escape}</h2>
                <p>{$dashboard.data_formattata|escape}</p>
            </div>
            <span class="pill-giornata">Oggi</span>
        </div>

        <section class="pannello-proprietario pannello-riepilogo" aria-labelledby="riepilogo-oggi">
            <div class="intestazione-pannello">
                <div>
                    <span class="sovratitolo">In primo piano</span>
                    <h3 id="riepilogo-oggi">Riepilogo di oggi</h3>
                </div>
            </div>

            <div class="griglia-metriche-proprietario">
                <a class="metrica-proprietario metrica-principale card-collegamento" href="index.php?pagina=appuntamenti_oggi">
                    <span class="metrica-icona" aria-hidden="true">◷</span>
                    <span class="metrica-etichetta">Appuntamenti di oggi</span>
                    <strong>{$dashboard.numero_appuntamenti_oggi}</strong>
                    <span class="metrica-link">Vedi la giornata <span aria-hidden="true">→</span></span>
                </a>

                <a class="metrica-proprietario card-collegamento" href="index.php?pagina=incassi_oggi">
                    <span class="metrica-icona" aria-hidden="true">€</span>
                    <span class="metrica-etichetta">Incasso previsto</span>
                    <strong>€ {$dashboard.incasso_previsto_oggi|string_format:"%.2f"}</strong>
                    <span class="metrica-nota">Prima di coupon e assenze</span>
                </a>

                <a class="metrica-proprietario metrica-completati card-collegamento" href="index.php?pagina=appuntamenti_oggi&amp;filtro=completati">
                    <span class="metrica-icona" aria-hidden="true">✓</span>
                    <span class="metrica-etichetta">Completati</span>
                    <strong>{$dashboard.numero_completati_oggi}</strong>
                    <span class="metrica-link metrica-link-scuro">Vedi i conclusi <span aria-hidden="true">→</span></span>
                </a>

                <a class="metrica-proprietario metrica-da-svolgere card-collegamento" href="index.php?pagina=appuntamenti_oggi&amp;filtro=da_svolgere">
                    <span class="metrica-icona" aria-hidden="true">→</span>
                    <span class="metrica-etichetta">Ancora da svolgere</span>
                    <strong>{$dashboard.numero_prenotati_oggi}</strong>
                    <span class="metrica-link metrica-link-scuro">Gestisci gli appuntamenti <span aria-hidden="true">→</span></span>
                </a>
            </div>
        </section>

        <a
            class="pannello-proprietario prossimi-oggi-card card-collegamento"
            href="index.php?pagina=agenda&amp;data={$dashboard.data|escape:'url'}"
            aria-labelledby="prossimi-oggi"
        >
            <div class="intestazione-pannello">
                <div>
                    <span class="sovratitolo">Agenda immediata</span>
                    <h3 id="prossimi-oggi">Prossimi appuntamenti di oggi</h3>
                </div>
                <span class="collegamento-pannello">Apri la giornata <span aria-hidden="true">→</span></span>
            </div>

            <div class="lista-prossimi-oggi">
                {foreach $dashboard.prossimi_appuntamenti as $appuntamento}
                    <article class="riga-prossimo-oggi">
                        <time datetime="{$appuntamento.data|escape}T{$appuntamento.ora_inizio|escape}">
                            {$appuntamento.ora_inizio|escape}
                        </time>
                        <div>
                            <strong>{$appuntamento.cliente|escape}</strong>
                            <span>{$appuntamento.servizio|escape}</span>
                        </div>
                        <span class="durata-appuntamento">
                            fino alle {$appuntamento.ora_fine|escape}
                        </span>
                    </article>
                {foreachelse}
                    <div class="empty-state-proprietario">
                        <span aria-hidden="true">✓</span>
                        <div>
                            <strong>Nessun altro appuntamento da svolgere oggi</strong>
                            <p>La giornata non ha altri impegni in programma.</p>
                        </div>
                    </div>
                {/foreach}
            </div>
        </a>

        <section class="pannello-proprietario prossimi-sette-giorni" aria-labelledby="prossimi-giorni">
            <div class="intestazione-pannello">
                <div>
                    <span class="sovratitolo">Programmazione</span>
                    <h3 id="prossimi-giorni">Prossime 7 giornate di apertura</h3>
                </div>
                <a class="link-secondario" href="index.php?pagina=agenda_sette_giorni&amp;data={$dashboard.data|escape:'url'}">Vedi le giornate <span aria-hidden="true">→</span></a>
            </div>

            <div class="riepilogo-giorni-dashboard" aria-label="Riepilogo delle prossime sette giornate di apertura">
                {foreach $dashboard.giorni_sette_giorni as $giorno}
                    <a class="card-giorno-dashboard {if $giorno.oggi}card-giorno-dashboard-oggi{/if}" href="index.php?pagina=agenda&amp;data={$giorno.data|escape:'url'}">
                        <span class="sovratitolo">{if $giorno.oggi}Oggi{else}{$giorno.giorno_breve|escape}{/if}</span>
                        <strong>{$giorno.data_formattata|escape}</strong>
                        <span class="numero-appuntamenti-giorno">{$giorno.numero} {if $giorno.numero == 1}appuntamento{else}appuntamenti{/if}</span>
                        <span class="apri-giorno-dashboard">Apri giornata <span aria-hidden="true">→</span></span>
                    </a>
                {/foreach}
            </div>
        </section>
    </main>
</body>
</html>
