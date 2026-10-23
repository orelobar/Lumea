document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-calendario]').forEach(function (calendario) {
        calendario.querySelectorAll('[data-cambia-mese]').forEach(function (pulsante) {
            pulsante.addEventListener('click', function (evento) {
                var destinazione = calendario.querySelector('[data-mese="' + pulsante.dataset.cambiaMese + '"]');
                if (!destinazione) return;
                evento.preventDefault();
                calendario.querySelectorAll('[data-mese]').forEach(function (mese) {
                    mese.hidden = mese !== destinazione;
                });
                calendario.querySelector('[data-annuncio-mese]').textContent = destinazione.getAttribute('aria-label');
                var precedente = destinazione.querySelector('[aria-label="Mese precedente"]');
                var successivo = destinazione.querySelector('[aria-label="Mese successivo"]');
                var nuovoFocus = pulsante.getAttribute('aria-label') === 'Mese successivo' ?
                    (successivo || precedente) : (precedente || successivo);
                if (nuovoFocus) nuovoFocus.focus();
            });
        });
    });

    var form = document.querySelector('.js-form-conferma-prenotazione');
    if (!form) {
        return;
    }

    form.addEventListener('submit', function (evento) {
        if (form.dataset.invio === 'in-corso') {
            evento.preventDefault();
            return;
        }

        form.dataset.invio = 'in-corso';
        var pulsante = form.querySelector('button[type="submit"]');
        if (!pulsante) {
            return;
        }

        pulsante.disabled = true;
        pulsante.setAttribute('aria-disabled', 'true');
        if (pulsante.dataset.testoAttesa) {
            pulsante.textContent = pulsante.dataset.testoAttesa;
        }
    });
});
