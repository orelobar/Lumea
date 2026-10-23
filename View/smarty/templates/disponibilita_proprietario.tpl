<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/svg+xml" href="images/lumea.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumèa - Orari e disponibilità</title>
    <link rel="stylesheet" href="css/interazioni.css?v=lumea-csrf-20261023">
    <script src="js/disponibilita.js?v=lumea20261008" defer></script>
</head>
<body class="area-proprietario">
    {assign var=nomiMesi value=[1=>'Gennaio',2=>'Febbraio',3=>'Marzo',4=>'Aprile',5=>'Maggio',6=>'Giugno',7=>'Luglio',8=>'Agosto',9=>'Settembre',10=>'Ottobre',11=>'Novembre',12=>'Dicembre']}

    <header class="header-proprietario">
        <h1>Lumèa</h1>
        {include file='nav_proprietario.tpl' paginaAttiva='disponibilita'}
    </header>

    <main class="contenuto-proprietario disponibilita-proprietario">
        <div class="titolo-pagina">
            <div>
                <span class="sovratitolo">Calendario del salone</span>
                <h2>Orari e disponibilità</h2>
                <p>Imposta l’orario ricorrente e personalizza le singole giornate.</p>
            </div>
            <span class="pill-giornata">{$nomiMesi[$mese]|escape} {$anno}</span>
        </div>

        {if $messaggio}<p class="messaggio-esito" role="status" data-temporanea="true">{$messaggio|escape}</p>{/if}
        {if $errore}<p class="messaggio-errore" role="alert">{$errore|escape}</p>{/if}

        <section class="pannello-proprietario selettore-periodo selettore-mese-elegante" aria-labelledby="periodo-selezionato">
            <div class="intestazione-pannello">
                <div>
                    <span class="sovratitolo">Calendario visualizzato</span>
                    <h3 id="periodo-selezionato">{$nomiMesi[$mese]|escape} {$anno}</h3>
                </div>
                <p class="nota-pannello">Scegli un altro mese per consultarne o modificarne gli orari.</p>
            </div>

            <form class="form-mese" method="get" action="index.php">
                <input type="hidden" name="pagina" value="disponibilita">
                <label>
                    <span>Mese</span>
                    <select name="mese">
                        {foreach $nomiMesi as $numero => $nome}
                            <option value="{$numero}" {if $numero == $mese}selected{/if}>{$nome}</option>
                        {/foreach}
                    </select>
                </label>
                <label>
                    <span>Anno</span>
                    <input type="number" name="anno" value="{$anno}" min="2000" max="2100" required>
                </label>
                <button class="pulsante-primario">Mostra mese</button>
            </form>
        </section>

        <section class="pannello-proprietario orario-standard" aria-labelledby="orario-standard">
            <div class="intestazione-pannello">
                <div>
                    <span class="sovratitolo">Pianificazione ricorrente</span>
                    <h3 id="orario-standard">Orario standard · {$nomiMesi[$mese]|escape}</h3>
                </div>
                <p class="nota-pannello">I giorni già personalizzati restano invariati.</p>
            </div>

            <form class="form-orario-mensile" method="post" action="index.php?pagina=disponibilita">
                <input type="hidden" name="csrf_token" value="{$csrfToken|escape:'html'}">
                <input type="hidden" name="azione" value="applica_orario_mensile">
                <input type="hidden" name="anno" value="{$anno}">
                <input type="hidden" name="mese" value="{$mese}">

                <div class="griglia-orario-standard">
                    {foreach $standard as $numero => $giorno}
                        <fieldset class="giorno-standard">
                            <legend>{$giorno.nome}</legend>

                            <div class="switch-stato-giorno" aria-label="Stato {$giorno.nome|escape}">
                                <label>
                                    <input class="js-stato-giorno" type="radio" name="orario[{$numero}][stato]" value="aperto" {if $giorno.stato == 'aperto'}checked{/if}>
                                    <span>Aperto</span>
                                </label>
                                <label>
                                    <input class="js-stato-giorno" type="radio" name="orario[{$numero}][stato]" value="chiuso" {if $giorno.stato == 'chiuso'}checked{/if}>
                                    <span>Chiuso</span>
                                </label>
                            </div>

                            <div class="orari fasce-standard">
                                {foreach $giorno.fasce as $indice => $fascia}
                                    <div class="fascia-oraria">
                                        <strong class="titolo-fascia">{if $indice == 0}Mattina{elseif $indice == 1}Pomeriggio{else}Altro intervallo{/if}</strong>
                                        <div class="campi-orario">
                                            <label>
                                                <span>Dalle</span>
                                                <input
                                                    type="text"
                                                    class="campo-orario-hhmm"
                                                    name="orario[{$numero}][fasce][{$indice}][inizio]"
                                                    value="{$fascia.inizio|escape}"
                                                    inputmode="numeric"
                                                    autocomplete="off"
                                                    placeholder="HH:MM"
                                                    maxlength="5"
                                                >
                                            </label>
                                            <label>
                                                <span>Alle</span>
                                                <input
                                                    type="text"
                                                    class="campo-orario-hhmm"
                                                    name="orario[{$numero}][fasce][{$indice}][fine]"
                                                    value="{$fascia.fine|escape}"
                                                    inputmode="numeric"
                                                    autocomplete="off"
                                                    placeholder="HH:MM"
                                                    maxlength="5"
                                                >
                                            </label>
                                        </div>
                                    </div>
                                {/foreach}
                            </div>
                        </fieldset>
                    {/foreach}
                </div>

                <div class="barra-azione-modulo">
                    <p class="nota-campo">Per un giorno aperto compila almeno un intervallo completo. Il pomeriggio deve iniziare dopo la chiusura della mattina.</p>
                    <button class="pulsante-primario">Applica a {$nomiMesi[$mese]|escape}</button>
                </div>
            </form>
        </section>

        <section class="pannello-proprietario calendario-disponibilita" aria-labelledby="calendario-mese">
            <div class="intestazione-pannello">
                <div>
                    <span class="sovratitolo">Dettaglio quotidiano</span>
                    <h3 id="calendario-mese">{$nomiMesi[$mese]|escape} {$anno}</h3>
                    <p>Ogni giornata mostra lo stato prevalente e l’orario effettivo.</p>
                </div>
                <span class="contatore-righe">{$calendario_iniziale|@count} giorni visibili</span>
            </div>

            <div class="calendario-mese">
                {foreach $calendario_iniziale as $giorno}
                    {include file='giorno_disponibilita.tpl'}
                {/foreach}
            </div>

            {if $numero_giorni_restanti > 0}
                <details class="calendario-completo">
                    <summary>Mostra tutto il mese <span>+{$numero_giorni_restanti} giorni</span></summary>
                    <div class="calendario-mese">
                        {foreach $calendario_restante as $giorno}
                            {include file='giorno_disponibilita.tpl'}
                        {/foreach}
                    </div>
                </details>
            {/if}
        </section>
    </main>
</body>
</html>
