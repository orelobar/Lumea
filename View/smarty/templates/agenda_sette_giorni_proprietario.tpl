<!DOCTYPE html>
<html lang="it">
<head>
    <base href="{$baseApplicazione|escape:'html'}">
    <link rel="icon" type="image/svg+xml" href="images/lumea.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumèa - Prossime 7 giornate di apertura</title>
    <link rel="stylesheet" href="css/interazioni.css?v=lumea-csrf-20261023">
</head>
<body class="area-proprietario">
    <header class="header-proprietario">
        <h1>Lumèa</h1>
        {include file='nav_proprietario.tpl' paginaAttiva='agenda'}
    </header>

    <main class="contenuto-proprietario agenda-sette-giorni-proprietario">
        <div class="titolo-pagina">
            <div>
                <span class="sovratitolo">Programmazione del salone</span>
                <h2>Prossime 7 giornate di apertura</h2>
                <p>Dal {$data_inizio|date_format:"%d/%m/%Y"} al {$data_fine|date_format:"%d/%m/%Y"} · {$totale_appuntamenti} {if $totale_appuntamenti == 1}appuntamento{else}appuntamenti{/if}</p>
            </div>
            <a class="pulsante-secondario collegamento-come-pulsante" href="index.php?pagina=agenda&amp;data={$data_inizio|escape:'url'}">Agenda di oggi</a>
        </div>

        <section class="settimana-operativa" aria-label="Agenda delle prossime sette giornate di apertura">
            {foreach $giorni as $giorno}
                <article class="giorno-settimana-operativa {if $giorno.oggi}giorno-settimana-oggi{/if}">
                    <header class="testata-giorno-settimana">
                        <div>
                            <span class="sovratitolo">{if $giorno.oggi}Oggi{else}{$giorno.data|date_format:"%d/%m"}{/if}</span>
                            <h3>{$giorno.etichetta|escape}</h3>
                        </div>
                    </header>

                    <div class="lista-settimana-operativa">
                        {foreach $giorno.righe as $riga}
                            {assign var=p value=$riga.prenotazione}
                            <div class="appuntamento-settimana-operativa appuntamento-settimana-{$p->getStato()|replace:'-':'_'}">
                                <time datetime="{$p->getData()|escape}T{$p->getOra()|escape}">{$p->getOra()|date_format:"%H:%M"}</time>
                                <div>
                                    <strong>{$p->getCliente()->getNome()|escape} {$p->getCliente()->getCognome()|escape}</strong>
                                    <span>{$p->getNomeServizio()|escape}</span>
                                </div>
                                <span class="badge-stato stato-{$p->getStato()|replace:'-':'_'}">
                                    {if $p->getStato() == 'prenotata'}Da svolgere{elseif $p->getStato() == 'completata'}Completato{elseif $p->getStato() == 'no-show'}Non presentato{else}{$p->getStato()|escape}{/if}
                                </span>
                            </div>
                        {foreachelse}
                            <div class="giorno-libero-settimana">
                                <span aria-hidden="true">◇</span>
                                <p>Nessun appuntamento</p>
                            </div>
                        {/foreach}
                    </div>

                    <a class="link-secondario apri-giornata-settimana" href="index.php?pagina=agenda&amp;data={$giorno.data|escape:'url'}">Apri giornata <span aria-hidden="true">→</span></a>
                </article>
            {/foreach}
        </section>
    </main>
</body>
</html>
