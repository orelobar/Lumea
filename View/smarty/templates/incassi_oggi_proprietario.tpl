<!DOCTYPE html>
<html lang="it">
<head>
    <base href="{$baseApplicazione|escape:'html'}">
    <link rel="icon" type="image/svg+xml" href="images/lumea.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumèa - Incassi di oggi</title>
    <link rel="stylesheet" href="css/interazioni.css?v=lumea-csrf-20261023">
</head>
<body class="area-proprietario">
    <header class="header-proprietario">
        <h1>Lumèa</h1>
        {include file='nav_proprietario.tpl' paginaAttiva='dashboard'}
    </header>

    <main class="contenuto-proprietario incassi-oggi-proprietario">
        <div class="titolo-pagina">
            <div>
                <span class="sovratitolo">Controllo economico della giornata</span>
                <h2>Incassi di oggi</h2>
                <p>{$data_formattata|escape}</p>
            </div>
            <a class="pulsante-secondario collegamento-come-pulsante" href="index.php">Torna alla Dashboard</a>
        </div>

        <section class="riepilogo-incassi-oggi" aria-label="Riepilogo incassi">
            <article class="card-incasso card-incasso-previsto">
                <span class="sovratitolo">Previsione di oggi</span>
                <span>Incasso previsto</span>
                <strong>€ {$incasso_previsto|string_format:"%.2f"}</strong>
                <small>Prima di coupon e assenze</small>
            </article>
            <article class="card-incasso card-incasso-effettivo">
                <span class="sovratitolo">Registrato</span>
                <span>Incasso effettivo</span>
                <strong>€ {$incasso_effettivo|string_format:"%.2f"}</strong>
                <small>Pagamenti già registrati</small>
            </article>
            <article class="card-incasso card-incasso-residuo">
                <span class="sovratitolo">Da registrare</span>
                <span>Incasso ancora da registrare</span>
                <strong>€ {$incasso_da_registrare|string_format:"%.2f"}</strong>
                <small>Valore degli appuntamenti non ancora registrato</small>
            </article>
        </section>

        <section class="pannello-proprietario dettaglio-incassi-oggi" aria-labelledby="dettaglio-incassi">
            <div class="intestazione-pannello">
                <div>
                    <span class="sovratitolo">Dettaglio della giornata</span>
                    <h3 id="dettaglio-incassi">Appuntamenti e pagamenti</h3>
                </div>
                <span class="contatore-righe">{$righe|@count}</span>
            </div>

            <div class="elenco-incassi-oggi">
                <div class="intestazione-incassi" aria-hidden="true">
                    <span>Cliente / servizio</span>
                    <span>Prezzo</span>
                    <span>Coupon</span>
                    <span>Effettivo</span>
                    <span>Stato</span>
                </div>

                {foreach $righe as $riga}
                    <article class="riga-incasso-oggi">
                        <div class="identita-incasso">
                            <strong>{$riga.cliente|escape}</strong>
                            <span>{$riga.servizio|escape}</span>
                        </div>
                        <div class="dato-incasso"><small>Prezzo</small><strong>€ {$riga.prezzo|string_format:"%.2f"}</strong></div>
                        <div class="dato-incasso"><small>Coupon</small><strong>{if $riga.coupon > 0}− € {$riga.coupon|string_format:"%.2f"}{else}—{/if}</strong></div>
                        <div class="dato-incasso dato-incasso-effettivo"><small>Effettivo</small><strong>{if $riga.pagato}€ {$riga.effettivo|string_format:"%.2f"}{else}Da registrare{/if}</strong></div>
                        <span class="badge-stato stato-{$riga.stato_codice|escape}">{$riga.stato|escape}</span>
                    </article>
                {foreachelse}
                    <div class="empty-state-proprietario empty-state-grande">
                        <span class="empty-state-icona" aria-hidden="true">€</span>
                        <div><strong>Nessun movimento previsto oggi</strong><p>Gli appuntamenti della giornata compariranno qui insieme ai relativi pagamenti.</p></div>
                    </div>
                {/foreach}
            </div>
        </section>
    </main>
</body>
</html>
