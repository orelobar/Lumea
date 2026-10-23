<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" type="image/svg+xml" href="images/lumea.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumèa - Servizi</title>
    <link rel="stylesheet" href="css/interazioni.css?v=lumea-csrf-20261023">
</head>
<body class="area-proprietario">
    <header class="header-proprietario">
        <h1>Lumèa</h1>
        {include file='nav_proprietario.tpl' paginaAttiva='servizi'}
    </header>

    <main class="contenuto-proprietario servizi-proprietario">
        <div class="titolo-pagina">
            <div>
                <span class="sovratitolo">Catalogo del salone</span>
                <h2>Servizi</h2>
                <p>Gestisci prestazioni, prezzi e durata delle nuove prenotazioni.</p>
            </div>
        </div>

        {if $messaggio}<p class="messaggio-esito" role="status" data-temporanea="true">{$messaggio|escape}</p>
        {/if}
        {if $errore}<p class="messaggio-errore" role="alert">{$errore|escape}</p>{/if}

        <section class="pannello-proprietario aggiungi-servizio" aria-labelledby="aggiungi-servizio">
            <div class="intestazione-pannello">
                <div>
                    <span class="sovratitolo">Nuova proposta</span>
                    <h3 id="aggiungi-servizio">Aggiungi servizio</h3>
                </div>
                <p class="nota-pannello">Lo storico mantiene prezzo e durata concordati.</p>
            </div>

            <form class="form-servizio form-servizio-nuovo js-form-servizio" method="post"
                action="index.php?pagina=servizi" novalidate>
                <input type="hidden" name="csrf_token" value="{$csrfToken|escape:'html'}">
                <label>
                    <span>Nome</span>
                    <input name="nome" value="{$valori.nome|default:''|escape}" placeholder="Es. Taglio classico" required>
                    <small class="errore-campo-servizio" data-errore="nome" aria-live="polite"></small>
                </label>
                <label>
                    <span>Categoria</span>
                    <select name="categoria">
                        <option>Uomo</option>
                        <option>Donna</option>
                    </select>
                    <small class="errore-campo-servizio errore-placeholder" aria-hidden="true"></small>
                </label>
                <label>
                    <span>Prezzo (€)</span>
                    <input class="js-prezzo-servizio" type="text" inputmode="decimal" name="prezzo" maxlength="6"
                        placeholder="0,00" autocomplete="off" required>
                    <small class="errore-campo-servizio" data-errore="prezzo" aria-live="polite"></small>
                </label>
                <label>
                    <span>Durata (minuti)</span>
                    <input class="js-durata-servizio" type="text" inputmode="numeric" name="durata" maxlength="3"
                        placeholder="30" autocomplete="off" required>
                    <small class="errore-campo-servizio" data-errore="durata" aria-live="polite"></small>
                </label>
                <button class="pulsante-primario" name="azione" value="aggiungi_servizio">Aggiungi servizio</button>
            </form>
        </section>

        {foreach ['Uomo', 'Donna'] as $categoria}
        <section class="pannello-proprietario categoria-servizi categoria-servizi-{$categoria|lower}"
            aria-labelledby="servizi-{$categoria|lower}">
            <div class="intestazione-pannello">
                <div>
                    <span class="sovratitolo">Categoria</span>
                    <h3 id="servizi-{$categoria|lower}">Servizi {$categoria|escape}</h3>
                </div>
                <span class="contatore-righe">{$servizi_per_categoria[$categoria]|@count} servizi</span>
            </div>

            <div class="griglia-servizi-proprietario">
                {foreach $servizi_per_categoria[$categoria] as $servizio}
                {assign var=idServizio value=$servizio->getId()}
                <article class="scheda-servizio {if !$servizio->isAttivo()}servizio-disattivato{/if}">
                    <div class="testata-appuntamento">
                        <div>
                            <span class="numero-servizio">{$servizio@iteration|string_format:"%02d"}</span>
                            <h4>{$servizio->getNome()|escape}</h4>
                        </div>
                        <span
                            class="badge-stato {if $servizio->isAttivo()}stato-attivo{else}stato-inattivo{/if}">
                            {if $servizio->isAttivo()}Prenotabile{else}Disattivato{/if}
                        </span>
                    </div>

                    <form class="form-servizio js-form-servizio" method="post" action="index.php?pagina=servizi"
                        novalidate>
                        <input type="hidden" name="csrf_token" value="{$csrfToken|escape:'html'}">
                        <input type="hidden" name="id_servizio" value="{$idServizio}">
                        <label>
                            <span>Nome</span>
                            <input name="nome" value="{$servizio->getNome()|escape}" required>
                            <small class="errore-campo-servizio" data-errore="nome" aria-live="polite"></small>
                        </label>
                        <label>
                            <span>Categoria</span>
                            <select name="categoria">
                                <option value="Uomo" {if $servizio->getCategoria() == 'Uomo'}selected{/if}>Uomo</option>
                                <option value="Donna" {if $servizio->getCategoria() == 'Donna'}selected{/if}>Donna</option>
                            </select>
                            <small class="errore-campo-servizio errore-placeholder" aria-hidden="true"></small>
                        </label>
                        <label>
                            <span>Prezzo (€)</span>
                            <input class="js-prezzo-servizio" type="text" inputmode="decimal" name="prezzo"
                                value="{$servizio->getPrezzo()|string_format:'%.2f'}" maxlength="6" autocomplete="off" required>
                            <small class="errore-campo-servizio" data-errore="prezzo" aria-live="polite"></small>
                        </label>
                        <label>
                            <span>Durata (minuti)</span>
                            <input class="js-durata-servizio" type="text" inputmode="numeric" name="durata"
                                value="{$servizio->getDurata()}" maxlength="3" autocomplete="off" required>
                            <small class="errore-campo-servizio" data-errore="durata" aria-live="polite"></small>
                        </label>

                        <div class="azioni-modifica">
                            <button class="pulsante-primario" name="azione" value="modifica_servizio">Salva
                                modifiche</button>
                            {if $servizio->isAttivo()}
                            <button class="pulsante-secondario js-conferma-azione" name="azione"
                                value="disattiva_servizio" formnovalidate data-confirm-title="Disattivare il servizio?"
                                data-confirm-message="Il servizio non sarà più prenotabile, ma resterà nello storico del salone."
                                data-confirm-action="Disattiva">Disattiva</button>
                            {else}
                            <button class="pulsante-secondario" name="azione" value="riattiva_servizio"
                                formnovalidate>Riattiva</button>
                            {/if}
                            {if $eliminabili[$idServizio]|default:false}
                            <button class="pulsante-pericolo js-conferma-azione" name="azione" value="elimina_servizio"
                                formnovalidate data-confirm-title="Eliminare il servizio?"
                                data-confirm-message="Questa operazione è definitiva. Il servizio verrà rimosso dal catalogo."
                                data-confirm-action="Elimina definitivamente">Elimina</button>
                            {else}
                            <button class="pulsante-secondario" type="button" disabled
                                title="Presente nello storico">Elimina</button>
                            {/if}
                        </div>
                    </form>

                    {if !$eliminabili[$idServizio]|default:false}
                    <p class="nota-campo">Presente nello storico: può essere disattivato, ma non eliminato.</p>
                    {/if}
                </article>
                {foreachelse}
                <div class="empty-state-proprietario">
                    <span aria-hidden="true">✂</span>
                    <div><strong>Nessun servizio {$categoria|lower}</strong>
                        <p>Aggiungi il primo servizio di questa categoria.</p>
                    </div>
                </div>
                {/foreach}
            </div>
        </section>
        {/foreach}
    </main>
    <script src="js/servizi-proprietario.js?v=lumea20261008" defer></script>
</body>
</html>
