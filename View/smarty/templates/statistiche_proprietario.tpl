<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/svg+xml" href="images/lumea.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumèa - Statistiche</title>
    <link rel="stylesheet" href="css/interazioni.css?v=lumea-csrf-20261023">
    <script src="js/statistiche-proprietario.js?v=lumea20261008" defer></script>
</head>
<body class="area-proprietario">
    <header class="header-proprietario">
        <h1>Lumèa</h1>
        {include file='nav_proprietario.tpl' paginaAttiva='statistiche'}
    </header>

    <main class="contenuto-proprietario statistiche-proprietario">
        <div class="titolo-pagina">
            <div>
                <span class="sovratitolo">Andamento del salone</span>
                <h2>Statistiche</h2>
                <p>Incassi, appuntamenti e preferenze dei clienti in un’unica lettura.</p>
            </div>
        </div>

        <section class="selettore-viste-statistiche" aria-label="Scegli il periodo">
            {foreach ['settimana', 'mese', 'anno'] as $chiave}
            <a class="card-periodo-statistiche {if $statistiche.periodo_attivo == $chiave}periodo-selezionato card-selezionata{/if}"
                href="index.php?pagina=statistiche&amp;periodo={$chiave}&amp;riferimento={$statistiche.riferimento|escape:'url'}"
                {if $statistiche.periodo_attivo == $chiave}aria-current="page" {/if}>
                <span
                    class="sovratitolo">{if $chiave == 'settimana'}Settimana{elseif $chiave == 'mese'}Mese{else}Anno{/if}</span>
                <strong>{$statistiche.etichette_periodi[$chiave]|escape}</strong>
                <span>€ {$statistiche.incassi[$chiave]|string_format:"%.2f"} · {$statistiche.appuntamenti_totali[$chiave]}
                    {if $statistiche.appuntamenti_totali[$chiave] == 1}appuntamento{else}appuntamenti{/if}</span>
            </a>
            {/foreach}
        </section>

        {if $statistiche.periodo_attivo == 'giorno'}
        <div class="giorno-selezionato-statistiche">
            <span class="sovratitolo">Dettaglio giorno</span>
            <strong>{$statistiche.etichette_periodi.giorno|escape}</strong>
        </div>
        {/if}

        <section class="pannello-proprietario navigazione-statistiche" aria-label="Navigazione periodo statistiche">
            <div class="navigazione-periodo">
                <a
                    href="index.php?pagina=statistiche&amp;periodo={$statistiche.periodo_attivo|escape:'url'}&amp;riferimento={$statistiche.navigazione_periodo.precedente|escape:'url'}">
                    <span aria-hidden="true">←</span>
                    {if $statistiche.periodo_attivo == 'giorno'}Giorno precedente{elseif $statistiche.periodo_attivo == 'settimana'}Settimana
                    precedente{elseif $statistiche.periodo_attivo == 'mese'}Mese precedente{else}Anno
                    precedente{/if}
                </a>

                {if !$statistiche.navigazione_periodo.e_corrente}
                <a class="periodo-corrente"
                    href="index.php?pagina=statistiche&amp;periodo={$statistiche.periodo_attivo|escape:'url'}&amp;riferimento={$statistiche.navigazione_periodo.corrente|escape:'url'}">
                    {if $statistiche.periodo_attivo == 'giorno'}Oggi{elseif $statistiche.periodo_attivo == 'settimana'}Settimana corrente{elseif $statistiche.periodo_attivo == 'mese'}Mese
                    corrente{else}Anno corrente{/if}
                </a>
                {/if}

                {if $statistiche.navigazione_periodo.successivo}
                <a
                    href="index.php?pagina=statistiche&amp;periodo={$statistiche.periodo_attivo|escape:'url'}&amp;riferimento={$statistiche.navigazione_periodo.successivo|escape:'url'}">
                    {if $statistiche.periodo_attivo == 'giorno'}Giorno successivo{elseif $statistiche.periodo_attivo == 'settimana'}Settimana
                    successiva{elseif $statistiche.periodo_attivo == 'mese'}Mese successivo{else}Anno
                    successivo{/if}
                    <span aria-hidden="true">→</span>
                </a>
                {/if}
            </div>

            <form method="get" action="index.php" class="selettore-periodo-statistiche">
                <input type="hidden" name="pagina" value="statistiche">
                <input type="hidden" name="periodo" value="giorno">
                <label for="riferimento-statistiche">
                    <span>Vai a un giorno preciso</span>
                    <input id="riferimento-statistiche" type="date" name="riferimento" value="{$statistiche.riferimento|escape}"
                        required>
                </label>
                <button class="pulsante-primario" type="submit">Mostra il giorno</button>
            </form>
        </section>

        <section class="griglia-metriche-statistiche" aria-label="Riepilogo del periodo">
            <article class="metrica-statistica metrica-statistica-incasso">
                <span class="metrica-icona" aria-hidden="true">€</span>
                <span>Incasso registrato</span>
                <strong>€ {$statistiche.incassi[$statistiche.periodo_attivo]|string_format:"%.2f"}</strong>
                <small>Pagamenti effettivamente registrati</small>
            </article>
            <article class="metrica-statistica metrica-statistica-appuntamenti">
                <span class="metrica-icona" aria-hidden="true">◷</span>
                <span>Appuntamenti</span>
                <strong>{$statistiche.appuntamenti_totali[$statistiche.periodo_attivo]}</strong>
                <small>Escluse le prenotazioni cancellate</small>
            </article>
            <article class="metrica-statistica metrica-statistica-completati">
                <span class="metrica-icona" aria-hidden="true">✓</span>
                <span>Completati</span>
                <strong>{$statistiche.appuntamenti_completati[$statistiche.periodo_attivo]}</strong>
                <small>Appuntamenti conclusi nel periodo</small>
            </article>
        </section>

        <section class="pannello-proprietario pannello-andamento-statistiche" aria-labelledby="andamento-periodo">
            <div class="intestazione-pannello">
                <div>
                    <h3 id="andamento-periodo">{$statistiche.etichette_periodi[$statistiche.periodo_attivo]|escape}</h3>
                    <p>{if $statistiche.periodo_attivo == 'giorno'}Incassi registrati ora per ora nel giorno
                        selezionato.{else}La linea segue gli incassi registrati nel periodo
                        selezionato.{/if} La scala si adatta agli importi.</p>
                </div>
            </div>

            <div class="grafico-canvas-contenitore grafico-linea-contenitore">
                <canvas class="grafico-canvas" aria-label="Andamento degli incassi registrati in euro"
                    role="img"></canvas>
                <div class="tooltip-grafico" role="status" aria-live="polite"></div>
                <ul class="grafico-dati">
                    {foreach $statistiche.grafici[$statistiche.periodo_attivo] as $punto}
                    <li data-etichetta="{$punto.etichetta|escape}" data-data="{$punto.data|default:''|escape}"
                        data-incasso="{$punto.incasso}" data-appuntamenti="{$punto.appuntamenti}"
                        data-completati="{$punto.completati}">
                        <span>{$punto.etichetta|escape}</span>
                        <span>€ {$punto.incasso|string_format:"%.2f"}</span>
                        <span>{$punto.appuntamenti}
                            {if $punto.appuntamenti == 1}appuntamento{else}appuntamenti{/if}</span>
                    </li>
                    {/foreach}
                </ul>
                {if !$statistiche.grafici[$statistiche.periodo_attivo]}
                <div class="empty-state-proprietario"><span aria-hidden="true">↗</span>
                    <div><strong>Nessun dato da rappresentare</strong>
                        <p>Il grafico si aggiornerà quando saranno presenti appuntamenti.</p>
                    </div>
                </div>
                {/if}
            </div>
        </section>

        <section class="pannello-proprietario servizi-richiesti-statistiche" aria-labelledby="servizi-periodo">
            <div class="intestazione-pannello">
                <div>
                    <span class="sovratitolo">Preferenze dei clienti</span>
                    <h3 id="servizi-periodo">Servizi più richiesti</h3>
                    <p>{$statistiche.etichette_periodi[$statistiche.periodo_attivo]|escape}</p>
                </div>
            </div>

            <ol class="classifica-servizi classifica-servizi-editoriale">
                {foreach $statistiche.servizi_piu_richiesti[$statistiche.periodo_attivo] as $servizio}
                <li>
                    <span class="posizione-classifica">{$servizio@iteration}</span>
                    <div class="nome-servizio-classifica">
                        <strong>{$servizio.nome_servizio|escape}</strong>
                        <span>{$servizio.percentuale}% delle richieste del periodo</span>
                    </div>
                    <span class="quantita-servizio"><strong>{$servizio.quantita}</strong>
                        {if $servizio.quantita == 1}prenotazione{else}prenotazioni{/if}</span>
                </li>
                {foreachelse}
                <li class="empty-state-proprietario"><span aria-hidden="true">◇</span>
                    <div><strong>Nessun servizio richiesto</strong>
                        <p>Non ci sono prenotazioni nel periodo selezionato.</p>
                    </div>
                </li>
                {/foreach}
            </ol>
        </section>
    </main>
</body>
</html>
