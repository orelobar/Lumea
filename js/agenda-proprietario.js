document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.selettore-giornata');
    if (!form) {
        return;
    }

    const giorno = form.querySelector('.giorno-agenda');
    const anno = form.querySelector('.anno-agenda');
    const erroreGiorno = form.querySelector('#errore-giorno-agenda');
    const erroreAnno = form.querySelector('#errore-anno-agenda');

    function soleCifre(valore, massimo) {
        return String(valore || '').replace(/\D/g, '').slice(0, massimo);
    }

    function pulisciErrore(campo, errore) {
        campo.classList.remove('campo-agenda-errore');
        campo.removeAttribute('aria-invalid');
        errore.textContent = '';
        errore.hidden = true;
    }

    function mostraErrore(campo, errore, messaggio) {
        campo.classList.add('campo-agenda-errore');
        campo.setAttribute('aria-invalid', 'true');
        errore.textContent = messaggio;
        errore.hidden = false;
    }

    function validaGiorno() {
        const testo = giorno.value.trim();
        const numero = Number(testo);

        if (!/^\d{1,2}$/.test(testo) || numero < 1 || numero > 31) {
            mostraErrore(giorno, erroreGiorno, 'Inserisci un giorno valido da 1 a 31.');
            return false;
        }

        pulisciErrore(giorno, erroreGiorno);
        return true;
    }

    function validaAnno() {
        const testo = anno.value.trim();
        const numero = Number(testo);

        if (!/^\d{4}$/.test(testo) || numero < 2000 || numero > 2100) {
            mostraErrore(anno, erroreAnno, 'Inserisci un anno valido tra 2000 e 2100.');
            return false;
        }

        pulisciErrore(anno, erroreAnno);
        return true;
    }

    giorno.addEventListener('input', function () {
        giorno.value = soleCifre(giorno.value, 2);
        pulisciErrore(giorno, erroreGiorno);
    });

    anno.addEventListener('input', function () {
        anno.value = soleCifre(anno.value, 4);
        pulisciErrore(anno, erroreAnno);
    });

    giorno.addEventListener('blur', validaGiorno);
    anno.addEventListener('blur', validaAnno);

    form.addEventListener('submit', function (evento) {
        const giornoValido = validaGiorno();
        const annoValido = validaAnno();

        if (!giornoValido || !annoValido) {
            evento.preventDefault();
            (giornoValido ? anno : giorno).focus();
        }
    });
});

// I moduli restano nel dialog: aprirli non modifica l'altezza delle card.
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-apri-agenda]').forEach(function (pulsante) {
        const dialog = document.getElementById(pulsante.dataset.apriAgenda);
        if (!dialog) return;

        pulsante.addEventListener('click', function () {
            dialog.showModal();
            document.body.classList.add('agenda-dialog-aperto');
        });
        dialog.querySelector('[data-chiudi-agenda]').addEventListener('click', function () {
            dialog.close();
        });
        dialog.addEventListener('close', function () {
            document.body.classList.remove('agenda-dialog-aperto');
            pulsante.focus();
        });
        // Mantiene il percorso Tab nella finestra, anche ai suoi estremi.
        dialog.addEventListener('keydown', function (evento) {
            if (evento.key !== 'Tab') return;
            const campi = Array.from(dialog.querySelectorAll(
                'a[href], button, input, select, textarea, summary, [tabindex]'
            )).filter(function (campo) {
                return !campo.disabled && campo.tabIndex >= 0 && campo.getClientRects().length > 0;
            });
            const primo = campi[0];
            const ultimo = campi[campi.length - 1];
            if (evento.shiftKey && document.activeElement === primo) {
                evento.preventDefault();
                ultimo.focus();
            } else if (!evento.shiftKey && document.activeElement === ultimo) {
                evento.preventDefault();
                primo.focus();
            }
        });
        // Escape resta gestito dal dialog HTML nativo.
    });
});
