document.addEventListener('DOMContentLoaded', function () {
    const campiOrario = Array.from(document.querySelectorAll('.campo-orario-hhmm'));

    function soleCifre(valore) {
        return String(valore || '').replace(/\D/g, '').slice(0, 4);
    }

    function formattaOrarioParziale(valore) {
        const cifre = soleCifre(valore);
        if (!cifre) return '';
        if (cifre.length === 1) {
            return Number(cifre) > 2 ? '0' + cifre + ':' : cifre;
        }
        return cifre.slice(0, 2) + ':' + cifre.slice(2);
    }

    function normalizzaOrarioCompleto(valore) {
        let cifre = soleCifre(valore);
        if (cifre.length === 3) cifre = '0' + cifre;
        if (cifre.length !== 4) return formattaOrarioParziale(valore);

        const ore = Number(cifre.slice(0, 2));
        const minuti = Number(cifre.slice(2, 4));
        if (ore > 23 || minuti > 59) {
            return cifre.slice(0, 2) + ':' + cifre.slice(2, 4);
        }
        return String(ore).padStart(2, '0') + ':' + String(minuti).padStart(2, '0');
    }

    function orarioValido(valore) {
        return /^([01][0-9]|2[0-3]):[0-5][0-9]$/.test(valore);
    }

    function elementoErrore(campo) {
        let errore = campo.parentElement.querySelector('.errore-orario-lumea');
        if (!errore) {
            errore = document.createElement('small');
            errore.className = 'errore-orario-lumea';
            errore.setAttribute('aria-live', 'polite');
            campo.insertAdjacentElement('afterend', errore);
        }
        return errore;
    }

    function pulisciErrore(campo) {
        campo.classList.remove('campo-orario-errore');
        campo.removeAttribute('aria-invalid');
        const errore = elementoErrore(campo);
        errore.textContent = '';
        errore.hidden = true;
    }

    function mostraErrore(campo) {
        campo.classList.add('campo-orario-errore');
        campo.setAttribute('aria-invalid', 'true');
        const errore = elementoErrore(campo);
        errore.textContent = 'Orario non valido. Usa un valore tra 00:00 e 23:59.';
        errore.hidden = false;
    }

    function validaCampo(campo) {
        if (campo.disabled || campo.value.trim() === '') {
            pulisciErrore(campo);
            return true;
        }
        campo.value = normalizzaOrarioCompleto(campo.value);
        if (!orarioValido(campo.value)) {
            mostraErrore(campo);
            return false;
        }
        pulisciErrore(campo);
        return true;
    }

    campiOrario.forEach(function (campo) {
        pulisciErrore(campo);
        campo.addEventListener('input', function () {
            campo.value = formattaOrarioParziale(campo.value);
            pulisciErrore(campo);
            const fine = campo.value.length;
            campo.setSelectionRange(fine, fine);
        });
        campo.addEventListener('blur', function () {
            campo.value = normalizzaOrarioCompleto(campo.value);
            validaCampo(campo);
        });
    });

    document.querySelectorAll('.form-orario-giorno, .form-orario-mensile').forEach(function (form) {
        form.setAttribute('novalidate', 'novalidate');
        form.addEventListener('submit', function (evento) {
            let primoErrore = null;
            form.querySelectorAll('.campo-orario-hhmm').forEach(function (campo) {
                if (!validaCampo(campo) && primoErrore === null) {
                    primoErrore = campo;
                }
            });
            if (primoErrore) {
                evento.preventDefault();
                primoErrore.focus();
            }
        });
    });

    const formMensile = document.querySelector('.form-orario-mensile');
    if (formMensile) {
        formMensile.querySelectorAll('.campo-orario-hhmm').forEach(function (campo) {
            campo.addEventListener('keydown', function (evento) {
                if (evento.key === 'Enter') evento.preventDefault();
            });
        });
    }

    document.querySelectorAll('.giorno-standard').forEach(function (giorno) {
        const controlliStato = Array.from(giorno.querySelectorAll('.js-stato-giorno'));
        const contenitoreOrari = giorno.querySelector('.fasce-standard');
        if (!controlliStato.length || !contenitoreOrari) return;

        const aggiornaStatoOrari = function () {
            const selezionato = controlliStato.find(function (controllo) { return controllo.checked; });
            const chiuso = selezionato ? selezionato.value === 'chiuso' : false;
            contenitoreOrari.querySelectorAll('.campo-orario-hhmm').forEach(function (campo) {
                campo.disabled = chiuso;
                if (chiuso) pulisciErrore(campo);
            });
            giorno.classList.toggle('giorno-standard-chiuso', chiuso);
            contenitoreOrari.setAttribute('aria-disabled', String(chiuso));
        };

        controlliStato.forEach(function (controllo) {
            controllo.addEventListener('change', aggiornaStatoOrari);
        });
        aggiornaStatoOrari();
    });
});
