document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-apri-dialog]').forEach(function (pulsante) {
        const dialog = document.getElementById(pulsante.dataset.apriDialog);
        if (!dialog) return;
        pulsante.addEventListener('click', function () {
            dialog.showModal();
            document.body.classList.add('dialog-cliente-aperto');
        });
        dialog.querySelector('[data-chiudi-dialog]').addEventListener('click', function () {
            dialog.close();
        });
        dialog.addEventListener('close', function () {
            document.body.classList.remove('dialog-cliente-aperto');
            pulsante.focus();
        });
        dialog.addEventListener('keydown', function (evento) {
            if (evento.key !== 'Tab') return;
            const campi = Array.from(dialog.querySelectorAll('button, a[href], input, [tabindex]')).filter(function (campo) {
                return !campo.disabled && campo.tabIndex >= 0 && campo.getClientRects().length > 0;
            });
            const primo = campi[0],
                ultimo = campi[campi.length - 1];
            if (evento.shiftKey && document.activeElement === primo) {
                evento.preventDefault();
                ultimo.focus();
            } else if (!evento.shiftKey && document.activeElement === ultimo) {
                evento.preventDefault();
                primo.focus();
            }
        });
    });
});
