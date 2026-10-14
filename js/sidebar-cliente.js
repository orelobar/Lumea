document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;
    const apri = document.querySelector('.pulsante-menu-cliente');
    const sidebar = document.querySelector('#sidebar-cliente');
    const chiudi = document.querySelector('.chiudi-sidebar-cliente');
    const sfondo = document.querySelector('.sfondo-sidebar-cliente');
    const mobile = window.matchMedia('(max-width: 52rem)');
    if (!apri || !sidebar || !chiudi || !sfondo) return;

    function impostaSidebar(aperta, riportaFocus) {
        if (!mobile.matches) aperta = false;
        body.classList.toggle('sidebar-cliente-aperta', aperta);
        apri.setAttribute('aria-expanded', String(aperta));
        sidebar.inert = mobile.matches && !aperta;
        if (aperta) {
            chiudi.focus();
        } else if (riportaFocus && mobile.matches) {
            apri.focus();
        }
    }

    apri.addEventListener('click', function () {
        impostaSidebar(!body.classList.contains('sidebar-cliente-aperta'), true);
    });
    chiudi.addEventListener('click', function () { impostaSidebar(false, true); });
    sfondo.addEventListener('click', function () { impostaSidebar(false, true); });
    sidebar.querySelectorAll('nav a').forEach(function (link) {
        link.addEventListener('click', function () { impostaSidebar(false, false); });
    });
    document.addEventListener('keydown', function (evento) {
        if (!mobile.matches || !body.classList.contains('sidebar-cliente-aperta')) return;
        if (evento.key === 'Escape') {
            evento.preventDefault();
            impostaSidebar(false, true);
        }
        if (evento.key !== 'Tab') return;
        const focusabili = Array.from(sidebar.querySelectorAll('a[href], button:not([disabled])'));
        if (!focusabili.length) return;
        const primo = focusabili[0];
        const ultimo = focusabili[focusabili.length - 1];
        if (evento.shiftKey && document.activeElement === primo) {
            evento.preventDefault();
            ultimo.focus();
        } else if (!evento.shiftKey && document.activeElement === ultimo) {
            evento.preventDefault();
            primo.focus();
        }
    });
    mobile.addEventListener('change', function () { impostaSidebar(false, false); });
    impostaSidebar(false, false);
});
