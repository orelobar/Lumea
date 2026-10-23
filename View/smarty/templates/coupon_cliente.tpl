<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/svg+xml" href="images/lumea.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumèa - Coupon e fedeltà</title>
    <link rel="stylesheet" href="css/interazioni.css?v=lumea-csrf-20261023">
    <link rel="stylesheet" href="css/cliente.css?v=lumea-csrf-20261023">
</head>
<body class="area-cliente pagina-coupon-cliente">
    <header class="header-cliente">
        <h1>Lumèa</h1>
        {include file='nav_cliente.tpl' paginaAttiva='coupon'}
    </header>

    <main id="contenuto-principale" class="contenuto-cliente">
        <div class="titolo-pagina">
            <div><span class="sovratitolo">IL TUO PROGRAMMA PREMI</span>
                <h2>Coupon e fedeltà<span class="titolo-punto">.</span></h2>
                <p>Un grazie speciale per ogni volta che ci scegli.</p>
            </div>
        </div>

        <section class="fedelta-hero" aria-labelledby="percorso-fedelta">
            <div><span class="eyebrow-chiaro">IL TUO PERCORSO</span>
                <h3 id="percorso-fedelta">Ogni appuntamento<br>è un passo avanti.</h3>
                <p><strong>{$fedelta.completati_totali}</strong> appuntamenti completati in totale</p>
                <div class="fedelta-progresso-numero"><strong>{$fedelta.nel_ciclo}</strong><span>/ {$fedelta.obiettivo}
                        verso il prossimo premio</span></div><progress value="{$fedelta.nel_ciclo}"
                    max="{$fedelta.obiettivo}">{$fedelta.percentuale}%</progress>
                <p>Ti mancano <strong>{$fedelta.appuntamenti_mancanti}</strong> appuntamenti per un coupon da <strong>€
                        {$fedelta.valore_prossimo_coupon|string_format:"%.2f"}</strong>.</p>
            </div>
            <div class="fedelta-hero-deco" aria-hidden="true"><span>✦</span><small>LUMÈA<br>REWARDS</small></div>
        </section>

        <section class="pannello-cliente" aria-labelledby="i-tuoi-coupon">
            <div class="intestazione-sezione">
                <div><span class="sovratitolo">I TUOI VANTAGGI</span>
                    <h3 id="i-tuoi-coupon">I tuoi coupon</h3>
                </div>
                <p>{$couponDisponibili} disponibili · € {$creditoCoupon|string_format:"%.2f"} di credito</p>
            </div>
            <p class="guida-contestuale">I coupon disponibili vengono utilizzati al pagamento in salone, non durante la
                prenotazione online.</p>
            {if $coupon}
            <div class="elenco-coupon">
                {foreach $coupon as $elemento}
                <article class="coupon-cliente{if !$elemento.disponibile} coupon-esaurito{/if}">
                    <div class="intestazione-sezione"><span class="coupon-id"><small>CODICE COUPON</small>
                            <h4>{$elemento.codice|escape}</h4>
                        </span><strong
                            class="badge-coupon {if $elemento.disponibile}badge-coupon-attivo{else}badge-coupon-esaurito{/if}">{$elemento.stato|escape}</strong>
                    </div>
                    <div class="coupon-valori"><span>Credito residuo <strong>€
                                {$elemento.credito_residuo|string_format:"%.2f"}</strong></span><span>Valore iniziale <strong>€
                                {$elemento.valore_iniziale|string_format:"%.2f"}</strong></span></div>
                    <p class="nota-campo">Generato il {$elemento.data_generazione|escape} · Premio fedeltà al traguardo di
                        {$elemento.traguardo} appuntamenti.</p>
                    <button type="button" class="coupon-apri" data-apri-dialog="coupon-dialog-{$elemento.id}"
                        aria-haspopup="dialog" aria-controls="coupon-dialog-{$elemento.id}"
                        aria-label="Vedi gli utilizzi del coupon {$elemento.codice|escape}"><span>Vedi utilizzi <b
                                aria-hidden="true">↗</b></span></button>
                    <dialog class="dialog-coupon" id="coupon-dialog-{$elemento.id}"
                        aria-labelledby="titolo-coupon-{$elemento.id}">
                        <div class="dialog-coupon-testata">
                            <div><span class="sovratitolo">Utilizzi del coupon</span>
                                <h3 id="titolo-coupon-{$elemento.id}">{$elemento.codice|escape}</h3>
                            </div><button type="button" data-chiudi-dialog aria-label="Chiudi storico coupon"
                                autofocus>×</button>
                        </div>
                        <p>Valore iniziale <strong>€ {$elemento.valore_iniziale|string_format:"%.2f"}</strong> · Credito residuo <strong>€
                                {$elemento.credito_residuo|string_format:"%.2f"}</strong></p>
                        <div class="utilizzi-coupon">
                            {foreach $elemento.utilizzi as $utilizzo}
                            <article class="utilizzo-coupon"><strong>{$utilizzo.data|escape}</strong>
                                <h4>{$utilizzo.servizio|escape}</h4>
                                <p>Credito utilizzato <strong>€ {$utilizzo.utilizzato|string_format:"%.2f"}</strong></p>
                                <p>Prezzo € {$utilizzo.prezzo|string_format:"%.2f"} · Pagato € {$utilizzo.pagato|string_format:"%.2f"}</p>
                            </article>
                            {foreachelse}<p>Nessun utilizzo registrato per questo coupon.</p>
                            {/foreach}
                        </div>
                        <small>Qui trovi i pagamenti con questo coupon. Le eventuali penalità sul credito non sono
                            pagamenti.</small>
                    </dialog>
                </article>
                {/foreach}
            </div>
            {else}
            <p class="empty-state">Nessun coupon disponibile.</p>
            {/if}
        </section>
    </main>
    <script src="js/dialoghi-cliente.js?v=20261012" defer></script>
</body>
</html>
