<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/svg+xml" href="images/lumea.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumèa - Le mie spese</title>
    <link rel="stylesheet" href="css/interazioni.css?v=lumea-csrf-20261023">
    <link rel="stylesheet" href="css/cliente.css?v={$versioneCSSCliente|escape}">
</head>
<body class="area-cliente pagina-spese-cliente">
    <header class="header-cliente">
        <h1>Lumèa</h1>
        {include file='nav_cliente.tpl' paginaAttiva='spese'}
    </header>

    <main id="contenuto-principale" class="contenuto-cliente">
        <div class="titolo-pagina">
            <div><span class="sovratitolo">RIEPILOGO PAGAMENTI</span>
                <h2>Le mie spese<span class="titolo-punto">.</span></h2>
                <p>Una panoramica trasparente di quanto hai pagato in salone.</p>
            </div>
        </div>

        <div class="griglia-riepilogo griglia-periodi-spesa" aria-label="Scegli il periodo">
            {foreach $periodi as $chiave => $periodo}
            <a class="dato-riepilogo card-collegamento{if $periodo.selezionato} card-selezionata{/if}"
                href="index.php?pagina=spese&amp;periodo={$chiave|escape:'url'}" {if $periodo.selezionato}aria-current="true"
                {/if}>
                <span>{$periodo.etichetta|escape}</span><strong>€
                    {$periodo.totale|string_format:"%.2f"}</strong><small>{if $periodo.selezionato}Periodo
                    selezionato{else}Visualizza dettagli ↗{/if}</small>
            </a>
            {/foreach}
        </div>

        <section class="pannello-cliente" aria-labelledby="dettaglio-spese">
            <div class="testata-pannello">
                <div><span class="sovratitolo">MOVIMENTI REGISTRATI</span>
                    <h3 id="dettaglio-spese">{$titoloPeriodo|escape}</h3>
                </div>
            </div>
            <nav class="barra-periodo-spese" aria-label="Navigazione {$periodoSelezionato|escape}">
                <a href="index.php?pagina=spese&amp;periodo={$periodoSelezionato|escape:'url'}&amp;data={$navigazione.precedente|escape:'url'}"
                    aria-label="{if $periodoSelezionato == 'settimana'}Settimana{elseif $periodoSelezionato == 'mese'}Mese{else}Anno{/if} precedente"><span
                        aria-hidden="true">←</span> Precedente</a>
                <a class="periodo-corrente{if $navigazione.corrente} selezionato{/if}"
                    href="index.php?pagina=spese&amp;periodo={$periodoSelezionato|escape:'url'}"
                    {if $navigazione.corrente}aria-current="true" {/if}>{if $periodoSelezionato == 'settimana'}Questa
                    settimana{elseif $periodoSelezionato == 'mese'}Mese corrente{else}Anno
                    corrente{/if}</a>
                {if $navigazione.successivo !== null}<a
                    href="index.php?pagina=spese&amp;periodo={$periodoSelezionato|escape:'url'}&amp;data={$navigazione.successivo|escape:'url'}"
                    aria-label="{if $periodoSelezionato == 'settimana'}Settimana{elseif $periodoSelezionato == 'mese'}Mese{else}Anno{/if} successivo">Successivo
                    <span aria-hidden="true">→</span></a>
                {else}<span class="periodo-non-disponibile" aria-disabled="true">Successivo <span
                        aria-hidden="true">→</span></span>{/if}
            </nav>
            <p class="totale-periodo-spese">Totale del periodo <strong>€ {$totaleSelezionato|string_format:"%.2f"|replace:'.':','}</strong></p>
            <p class="nota-spese">I totali si riferiscono alla data del pagamento; nella tabella trovi la data
                dell’appuntamento.</p>
            {if $righe}
            <div class="tabella-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Data appuntamento</th>
                            <th>Servizio</th>
                            <th>Prezzo originario</th>
                            <th>Coupon</th>
                            <th>Pagato</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $righe as $riga}
                        <tr>
                            <td data-etichetta="Data appuntamento">{$riga.data|escape}</td>
                            <td data-etichetta="Servizio">{$riga.servizio|escape}</td>
                            <td data-etichetta="Prezzo originario">€ {$riga.prezzo_originario|string_format:"%.2f"}</td>
                            <td data-etichetta="Coupon">
                                <div>{if $riga.sconto > 0}<strong>{$riga.coupon|escape}</strong><br><span>Credito
                                        utilizzato: € {$riga.sconto|string_format:"%.2f"}</span>{else}<span
                                        class="coupon-non-usato">Non utilizzato</span>{/if}</div>
                            </td>
                            <td data-etichetta="Pagato"><strong>€ {$riga.importo_pagato|string_format:"%.2f"}</strong></td>
                        </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
            {else}
            <p class="empty-state">Nessuna spesa registrata in questo periodo.</p>
            {/if}
        </section>
    </main>
</body>
</html>
