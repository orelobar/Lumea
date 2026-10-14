<article class="giorno-calendario {if !$giorno.modificabile}giorno-trascorso{/if} {if $giorno.chiuso}giorno-chiuso{/if}">
    <div class="intestazione-giorno">
        <div>
            <h4>{$giorno.etichetta|escape}</h4>
            <div class="badge-giorno-riga">
                {if !$giorno.modificabile}
                    <span class="badge-stato stato-trascorso">Trascorso</span>
                {elseif $giorno.chiuso}
                    <span class="badge-stato stato-chiuso">Chiuso</span>
                {else}
                    <span class="badge-stato stato-aperto">Aperto</span>
                {/if}
                {if $giorno.personalizzato}<span class="badge-stato stato-personalizzato">Personalizzato</span>{/if}
            </div>
        </div>
    </div>

    <div class="riepilogo-orari-giorno">
        {if $giorno.chiuso}
            <p class="indicatore-chiuso">
                {if !$giorno.modificabile}<span class="stato-secondario-calendario">La giornata era chiusa</span>{else}<span aria-hidden="true">—</span> Nessun orario disponibile{/if}
            </p>
        {else}
            <dl class="storico-orari">
                {foreach $giorno.fasce as $fascia}
                    <div>
                        <dt>{if $fascia@first}Orario{else}<span class="solo-lettori-schermo">Secondo intervallo</span>{/if}</dt>
                        <dd>{$fascia.inizio|escape}–{$fascia.fine|escape}</dd>
                    </div>
                {/foreach}
            </dl>
        {/if}
    </div>

    {if $giorno.modificabile}
        <details class="modifica-giornata">
            <summary>Modifica orario</summary>
            <form class="form-orario-giorno" method="post" action="index.php?pagina=disponibilita">
                <input type="hidden" name="azione" value="imposta_eccezione">
                <input type="hidden" name="data" value="{$giorno.data|escape}">

                {foreach $giorno.campi as $indice => $fascia}
                    <fieldset class="fascia-oraria">
                        <legend>{if $indice == 0}Mattina{elseif $indice == 1}Pomeriggio{else}Altro intervallo{/if}</legend>
                        <div class="campi-orario">
                            <label>
                                <span>Dalle</span>
                                <input
                                    type="text"
                                    class="campo-orario-hhmm"
                                    name="fasce[{$indice}][inizio]"
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
                                    name="fasce[{$indice}][fine]"
                                    value="{$fascia.fine|escape}"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    placeholder="HH:MM"
                                    maxlength="5"
                                >
                            </label>
                        </div>
                    </fieldset>
                {/foreach}

                <button class="pulsante-primario" type="submit">Salva orario</button>
            </form>
        </details>

        {if !$giorno.chiuso}
            <form class="chiusura-giornata" method="post" action="index.php?pagina=disponibilita">
                <input type="hidden" name="data" value="{$giorno.data|escape}">
                <button class="pulsante-secondario js-conferma-azione" name="azione" value="chiudi_giorno" data-confirm-title="Chiudere questa giornata?" data-confirm-message="La giornata verrà impostata come chiusa. L’operazione resta bloccata se esistono prenotazioni attive." data-confirm-action="Imposta come chiuso">Imposta come chiuso</button>
                <small>La chiusura è bloccata se esistono prenotazioni attive.</small>
            </form>
        {/if}
    {/if}
</article>
