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

    const form = document.querySelector('.form-registrazione');
    if (!form) return;

    const campi = ['nome', 'cognome', 'email', 'telefono', 'password', 'conferma_password']
        .map(function (id) { return document.getElementById(id); });
    const password = document.getElementById('password');
    const conferma = document.getElementById('conferma_password');

    // PHP trim() e strlen() hanno semantiche diverse da trim() e length in JavaScript.
    function trimPhp(valore) {
        return valore.replace(/^[\x00\x09\x0A\x0B\x0D\x20]+|[\x00\x09\x0A\x0B\x0D\x20]+$/g, '');
    }

    function errore(campo) {
        const valore = campo.value;
        const pulito = trimPhp(valore);
        switch (campo.id) {
        case 'nome':
        case 'cognome':
            if (pulito === '') return campo.id === 'nome' ? 'Inserisci un nome.' : 'Inserisci un cognome.';
            if (Array.from(pulito).length > 50) {
                return campo.id === 'nome'
                    ? 'Il nome non può superare 50 caratteri.'
                    : 'Il cognome non può superare 50 caratteri.';
            }
            break;
        case 'email':
            if (pulito === '') return 'Inserisci un indirizzo email.';
            if (campo.validity.typeMismatch || campo.validity.patternMismatch) {
                return 'Inserisci un indirizzo email valido, ad esempio nome@dominio.it.';
            }
            if (Array.from(pulito).length > 150) return 'L’indirizzo email è troppo lungo.';
            break;
        case 'telefono': {
            let numero = pulito.replace(/[\x09-\x0D\x20\-()]/g, '');
            let nazionale = numero;
            if (numero.startsWith('0039')) nazionale = numero.slice(4);
            else if (numero.startsWith('+39')) nazionale = numero.slice(3);
            if (/^0[0-9]+$/.test(nazionale)) {
                return 'Non puoi inserire un numero fisso. Inserisci un numero di cellulare italiano.';
            }
            if (numero.startsWith('0039')) numero = '+39' + numero.slice(4);
            else if (!numero.startsWith('+39')) numero = '+39' + numero;
            if (!/^\+393[0-9]{9}$/.test(numero)) return 'Inserisci un numero di cellulare italiano valido.';
            break;
        }
        case 'password':
            if (new TextEncoder().encode(valore).length < 8 || !/[A-Z]/.test(valore) || !/[0-9]/.test(valore)) {
                return 'Usa almeno 8 byte di testo, una maiuscola A–Z e un numero 0–9.';
            }
            break;
        case 'conferma_password':
            if (valore === '') return 'Ripeti la password scelta.';
            if (valore !== password.value) return 'Le password non coincidono.';
            break;
        }
        return '';
    }

    function valida(campo) {
        campo.setCustomValidity('');
        const messaggio = errore(campo);
        const feedback = document.getElementById(campo.id + '-errore');
        campo.setCustomValidity(messaggio);
        campo.setAttribute('aria-invalid', String(messaggio !== ''));
        feedback.textContent = messaggio;
        feedback.hidden = messaggio === '';
        return messaggio === '';
    }

    campi.forEach(function (campo) {
        campo.addEventListener('input', function () {
            valida(campo);
            if (campo === password && conferma.value !== '') valida(conferma);
        });
        campo.addEventListener('blur', function () { valida(campo); });
        campo.addEventListener('invalid', function () { valida(campo); });
    });

    form.addEventListener('submit', function (evento) {
        const validi = campi.map(valida);
        const primoErrore = validi.indexOf(false);
        if (primoErrore !== -1) {
            evento.preventDefault();
            campi[primoErrore].focus();
            campi[primoErrore].reportValidity();
        }
    });
});
