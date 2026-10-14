document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;
    const apri = document.querySelector('.pulsante-menu-proprietario');
    const chiudi = document.querySelector('.chiudi-sidebar');
    const sfondo = document.querySelector('.sfondo-sidebar');

    if (!apri || !chiudi || !sfondo) {
        return;
    }

    function impostaSidebar(aperta) {
        body.classList.toggle('sidebar-aperta', aperta);
        apri.setAttribute('aria-expanded', aperta ? 'true' : 'false');
        if (aperta) {
            chiudi.focus();
        } else {
            apri.focus();
        }
    }

    apri.addEventListener('click', function () {
        impostaSidebar(!body.classList.contains('sidebar-aperta'));
    });
    chiudi.addEventListener('click', function () { impostaSidebar(false); });
    sfondo.addEventListener('click', function () { impostaSidebar(false); });
    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape' && body.classList.contains('sidebar-aperta')) {
            impostaSidebar(false);
        }
    });
});
