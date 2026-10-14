<a class="salta-contenuto" href="#contenuto-principale">Vai al contenuto</a>
<button class="pulsante-menu-cliente" type="button" aria-controls="sidebar-cliente" aria-expanded="false" aria-label="Apri il menu"><svg class="lm-icona" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg><span>Menu</span></button>
<aside class="sidebar-cliente" id="sidebar-cliente" aria-label="Area personale cliente">
  <div class="sidebar-intestazione">
    <a class="sidebar-brand-link" href="index.php?pagina=dashboard" aria-label="Lumèa, torna alla panoramica">
      <span class="marchio-cliente" aria-hidden="true">L</span>
      <span class="sidebar-brand"><strong>Lumèa</strong><small>Il mio spazio</small></span>
    </a>
    <button type="button" class="chiudi-sidebar-cliente" aria-label="Chiudi il menu"><svg class="lm-icona" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 5l14 14M19 5 5 19"/></svg></button>
  </div>
  <div class="sidebar-cliente-etichetta">AREA CLIENTE <span class="sidebar-punto" aria-hidden="true"></span></div>
  <nav aria-label="Navigazione cliente">
    <a href="index.php?pagina=dashboard" {if $paginaAttiva == 'dashboard'}aria-current="page"{/if}><svg class="lm-icona" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/></svg><span>Panoramica</span><span class="nav-trattino" aria-hidden="true"></span></a>
    <span class="etichetta-gruppo">APPUNTAMENTI</span>
    <a href="index.php?pagina=prenota" {if $paginaAttiva == 'prenota'}aria-current="page"{/if}><svg class="lm-icona" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 10h18M8 14h3"/></svg><span>Prenota appuntamento</span><span class="nav-trattino" aria-hidden="true"></span></a>
    <a href="index.php?pagina=prenotazioni" {if $paginaAttiva == 'prenotazioni'}aria-current="page"{/if}><svg class="lm-icona" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg><span>Le mie prenotazioni</span><span class="nav-trattino" aria-hidden="true"></span></a>
    <a href="index.php?pagina=lista_attesa" {if $paginaAttiva == 'lista_attesa'}aria-current="page"{/if}><svg class="lm-icona" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg><span>Lista d’attesa</span><span class="nav-trattino" aria-hidden="true"></span></a>
    <span class="etichetta-gruppo">VANTAGGI E SPESE</span>
    <a href="index.php?pagina=coupon" {if $paginaAttiva == 'coupon'}aria-current="page"{/if}><svg class="lm-icona" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="9" width="18" height="12" rx="2"/><path d="M2 9h20M12 9v12M12 9s-5-2-5-5a2.5 2.5 0 0 1 5 0c0 3 0 5 0 5zm0 0s5-2 5-5a2.5 2.5 0 0 0-5 0c0 3 0 5 0 5z"/></svg><span>Coupon e fedeltà</span><span class="nav-trattino" aria-hidden="true"></span></a>
    <a href="index.php?pagina=spese" {if $paginaAttiva == 'spese'}aria-current="page"{/if}><svg class="lm-icona" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="6" width="18" height="15" rx="2"/><path d="M3 10h18M7 3h11M16 15h2"/></svg><span>Le mie spese</span><span class="nav-trattino" aria-hidden="true"></span></a>
    <a class="js-conferma-logout js-conferma-azione voce-logout" href="index.php?azione=logout" data-confirm-title="Uscire da Lumèa?" data-confirm-message="La sessione verrà chiusa e tornerai alla pagina iniziale." data-confirm-action="Esci"><svg class="lm-icona" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4M13 8l4 4-4 4M17 12H8"/></svg><span>Esci dall'account</span></a>
  </nav>
  <div class="sidebar-cliente-fondo"><span class="sidebar-fondo-marchio" aria-hidden="true">✦</span><span>Il tuo tempo, <strong>il tuo stile.</strong></span></div>
</aside>
<button type="button" class="sfondo-sidebar-cliente" aria-label="Chiudi il menu" tabindex="-1"></button>
<script src="js/sidebar-cliente.js?v=lumea20261008" defer></script>
<script src="js/logout.js?v=lumea20261008" defer></script>
<script src="js/notifiche-cliente.js?v=lumea20261008" defer></script>
