document.addEventListener('DOMContentLoaded', function () {
    var forms = document.querySelectorAll('.js-form-servizio');

    function normalizzaPrezzo(input) {
        var valore = input.value.replace(/[^0-9,.]/g, '').replace(',', '.');
        var parti = valore.split('.');
        var intero = (parti[0] || '').slice(0, 3);
        var decimali = parti.length > 1 ? parti.slice(1).join('').slice(0, 2) : '';
        input.value = decimali !== '' ? intero + ',' + decimali : intero;
    }

    function normalizzaDurata(input) {
        input.value = input.value.replace(/\D/g, '').slice(0, 3);
    }

    function mostraErrore(form, nome, messaggio) {
        var nodo = form.querySelector('[data-errore="' + nome + '"]');
        if (nodo) nodo.textContent = messaggio || '';
        var campo = form.elements[nome];
        if (campo) campo.classList.toggle('campo-servizio-errore', Boolean(messaggio));
    }

    function valida(form) {
        var nomeInput = form.elements.nome;
        var prezzoInput = form.elements.prezzo;
        var durataInput = form.elements.durata;
        var prezzoTesto = (prezzoInput.value || '').trim().replace(',', '.');
        var durataTesto = (durataInput.value || '').trim();
        var ok = true;

        if (!nomeInput || nomeInput.value.trim() === '') {
            mostraErrore(form, 'nome', 'Inserisci il nome del servizio.');
            ok = false;
        } else {
            mostraErrore(form, 'nome', '');
        }

        if (!/^\d{1,3}(?:\.\d{1,2})?$/.test(prezzoTesto) || Number(prezzoTesto) > 999.99) {
            mostraErrore(form, 'prezzo', 'Inserisci un prezzo tra 0 e 999,99 €, con massimo due decimali.');
            ok = false;
        } else {
            mostraErrore(form, 'prezzo', '');
        }

        var durata = Number(durataTesto);
        if (!/^\d{1,3}$/.test(durataTesto) || durata < 15 || durata % 15 !== 0) {
            mostraErrore(form, 'durata', 'La durata deve essere un multiplo di 15 minuti (15, 30, 45, 60…).');
            ok = false;
        } else {
            mostraErrore(form, 'durata', '');
        }

        return ok;
    }

    forms.forEach(function (form) {
        var prezzo = form.querySelector('.js-prezzo-servizio');
        var durata = form.querySelector('.js-durata-servizio');

        if (prezzo) {
            prezzo.addEventListener('input', function () {
                normalizzaPrezzo(prezzo);
                mostraErrore(form, 'prezzo', '');
            });
        }

        if (durata) {
            durata.addEventListener('input', function () {
                normalizzaDurata(durata);
                mostraErrore(form, 'durata', '');
            });
        }

        form.addEventListener('submit', function (event) {
            var submitter = event.submitter;
            if (submitter && submitter.hasAttribute('formnovalidate')) return;
            if (!valida(form)) {
                event.preventDefault();
                var primoErrore = form.querySelector('.campo-servizio-errore');
                if (primoErrore) primoErrore.focus();
            }
        });
    });
});
