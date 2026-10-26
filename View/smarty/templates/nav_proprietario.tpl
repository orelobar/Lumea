<button
    class="pulsante-menu-proprietario"
    type="button"
    aria-controls="sidebar-proprietario"
    aria-expanded="false"
>
    <span class="icona-menu" aria-hidden="true">☰</span>
    <span>Menu</span>
</button>

<aside id="sidebar-proprietario" class="sidebar-proprietario" aria-label="Area proprietario">
    <div class="sidebar-intestazione">
        <a class="sidebar-brand" href="dashboard" aria-label="Lumèa, torna alla Dashboard">
            <span class="sidebar-monogramma" aria-hidden="true">L</span>
            <span class="sidebar-brand-testo">
                <strong>Lumèa</strong>
                <small>Gestione salone</small>
            </span>
        </a>
        <button class="chiudi-sidebar" type="button" aria-label="Chiudi menu">×</button>
    </div>

    <nav class="navigazione-proprietario">
        <div class="gruppo-dashboard">
            <a class="voce-navigazione" href="dashboard" {if $paginaAttiva == 'dashboard'}aria-current="page"{/if}>
                <span class="nav-icon" aria-hidden="true">⌂</span>
                <span>Dashboard</span>
            </a>
        </div>

        <div class="gruppo-gestione" aria-label="Gestione salone">
            <span class="etichetta-gruppo">Gestione</span>
            <a class="voce-navigazione" href="agenda" {if $paginaAttiva == 'agenda'}aria-current="page"{/if}>
                <span class="nav-icon nav-icon-agenda" aria-hidden="true"></span>
                <span>Agenda</span>
            </a>
            <a class="voce-navigazione" href="servizi" {if $paginaAttiva == 'servizi'}aria-current="page"{/if}>
                <span class="nav-icon" aria-hidden="true">✂</span>
                <span>Servizi</span>
            </a>
            <a class="voce-navigazione" href="disponibilita" {if $paginaAttiva == 'disponibilita'}aria-current="page"{/if}>
                <span class="nav-icon" aria-hidden="true">◴</span>
                <span>Orari e disponibilità</span>
            </a>
            <a class="voce-navigazione" href="clienti" {if $paginaAttiva == 'clienti'}aria-current="page"{/if}>
                <span class="nav-icon" aria-hidden="true">♙</span>
                <span>Clienti</span>
            </a>
            <a class="voce-navigazione" href="statistiche" {if $paginaAttiva == 'statistiche'}aria-current="page"{/if}>
                <span class="nav-icon" aria-hidden="true">↗</span>
                <span>Statistiche</span>
            </a>
        </div>

        <form class="form-logout" method="post" action="sessione/uscita">
            <input type="hidden" name="csrf_token" value="{$csrfToken|escape:'html'}">
            <input type="hidden" name="azione" value="logout">
            <button type="submit" class="js-conferma-logout js-conferma-azione voce-logout" data-confirm-title="Uscire da Lumèa?" data-confirm-message="La sessione verrà chiusa e tornerai alla pagina iniziale." data-confirm-action="Esci">
            <span class="nav-icon" aria-hidden="true">↪</span>
            <span>Logout</span>
        </button>
        </form>
    </nav>
</aside>

<button class="sfondo-sidebar" type="button" aria-label="Chiudi menu" tabindex="-1"></button>
<script src="js/sidebar-proprietario.js?v=lumea20261008" defer></script>
<script src="js/logout.js?v=lumea20261008" defer></script>
<script src="js/notifiche-proprietario.js?v=lumea20261008" defer></script>
