document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-password-target]').forEach(function (pulsante) {
        const campo = document.getElementById(pulsante.dataset.passwordTarget);
        if (!campo) return;
        const descrizione = campo.id === 'conferma_password' ? 'conferma password' : 'password';
        pulsante.addEventListener('click', function () {
            const visibile = campo.type === 'password';
            campo.type = visibile ? 'text' : 'password';
            pulsante.setAttribute('aria-pressed', String(visibile));
            pulsante.setAttribute('aria-label', (visibile ? 'Nascondi ' : 'Mostra ') + descrizione);
            pulsante.title = pulsante.getAttribute('aria-label');
            pulsante.querySelector('.icona-occhio').hidden = visibile;
            pulsante.querySelector('.icona-occhio-chiuso').hidden = !visibile;
        });
    });
});
