{* Un solo mese visibile; tutti gli stati arrivano dagli slot del Control. *}
<div class="calendario-prenotazione" data-calendario>
    {foreach $calendario as $indice => $mese}
        <section class="mese-prenotazione" data-mese="{$indice}" aria-label="{$mese.titolo|escape}" {if !$mese.aperto}hidden{/if}>
            <div class="navigazione-calendario">
                {if $indice > 0}
                    <a class="azione-secondaria" data-cambia-mese="{$indice-1}" aria-label="Mese precedente" href="index.php?pagina=prenota&amp;nuova=1&amp;categoria={$categoria|escape:'url'}&amp;servizio={$servizioSelezionato->getId()}&amp;mese_calendario={$calendario[$indice-1].chiave|escape:'url'}"><span aria-hidden="true">←</span><span class="testo-freccia-mese"> Precedente</span></a>
                {else}<span class="freccia-mese-disabilitata" aria-disabled="true" aria-label="Nessun mese precedente">←</span>{/if}
                <h4>{$mese.titolo|escape}</h4>
                {if $indice+1 < count($calendario)}
                    <a class="azione-secondaria" data-cambia-mese="{$indice+1}" aria-label="Mese successivo" href="index.php?pagina=prenota&amp;nuova=1&amp;categoria={$categoria|escape:'url'}&amp;servizio={$servizioSelezionato->getId()}&amp;mese_calendario={$calendario[$indice+1].chiave|escape:'url'}"><span class="testo-freccia-mese">Successivo </span><span aria-hidden="true">→</span></a>
                {else}<span class="freccia-mese-disabilitata" aria-disabled="true" aria-label="Nessun mese successivo">→</span>{/if}
            </div>
            <div class="griglia-calendario" aria-label="Giorni di {$mese.titolo|escape}">
                {foreach ['Lun','Mar','Mer','Gio','Ven','Sab','Dom'] as $giornoSettimana}<span class="giorno-settimana">{$giornoSettimana}</span>{/foreach}
                {foreach $mese.giorni as $giorno}
                    {if $giorno === null}<span class="giorno-vuoto" aria-hidden="true"></span>
                    {elseif $giorno.disponibile}
                        <a class="giorno-calendario disponibile{if $giorno.selezionato} selezionato{/if}{if $giorno.oggi} oggi{/if}" {if $giorno.selezionato}aria-current="date"{/if} aria-label="{$giorno.etichetta|escape}, prenotabile" href="index.php?pagina=prenota&amp;nuova=1&amp;categoria={$categoria|escape:'url'}&amp;servizio={$servizioSelezionato->getId()}&amp;data={$giorno.data|escape:'url'}">{$giorno.numero}</a>
                    {else}<span class="giorno-calendario non-disponibile" aria-disabled="true" aria-label="{$giorno.etichetta|escape}, non prenotabile">{$giorno.numero}</span>{/if}
                {/foreach}
            </div>
            {if $mese.numero_disponibili == 0}<p class="nota-calendario">Nessun giorno prenotabile in questo mese.</p>{/if}
        </section>
    {/foreach}
    <p class="legenda-calendario"><span class="legenda-disponibile" aria-hidden="true"></span> Prenotabile <span class="legenda-non-disponibile" aria-hidden="true"></span> Non disponibile</p>
    <span class="solo-lettori-schermo" data-annuncio-mese aria-live="polite"></span>
</div>
