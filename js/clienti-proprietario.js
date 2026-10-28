(() => {
    'use strict';

    const ricerca = document.querySelector('[data-ricerca-clienti]');
    const elenco = document.querySelector('.elenco-clienti-proprietario');
    if (!ricerca || !elenco) return;

    const campo = ricerca.querySelector('input[type="search"]');
    const cancella = ricerca.querySelector('[data-cancella-ricerca]');
    const conteggio = ricerca.querySelector('[data-conteggio-clienti]');
    const nessunRisultato = document.querySelector('[data-clienti-non-trovati]');
    const collatore = new Intl.Collator('it', { sensitivity: 'base' });
    const normalizza = (valore) => (valore || '').normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('it').trim();

    // Ordinamento di presentazione: la Foundation fornisce già cognome e nome A–Z.
    // I cognomi vuoti seguono quelli compilati; a parità si confronta il nome.
    const clienti = Array.from(elenco.querySelectorAll('[data-cliente]'))
        .sort((a, b) => {
            const cognomeA = a.dataset.cognome.trim();
            const cognomeB = b.dataset.cognome.trim();
            return Number(!cognomeA) - Number(!cognomeB)
                || collatore.compare(cognomeA, cognomeB)
                || collatore.compare(a.dataset.nome.trim(), b.dataset.nome.trim());
        })
        .map((scheda) => {
            elenco.appendChild(scheda);
            return {
                scheda,
                testo: normalizza([
                    scheda.dataset.nome, scheda.dataset.cognome,
                    scheda.dataset.email, scheda.dataset.telefono
                ].join(' ')),
                telefono: (scheda.dataset.telefono || '').replace(/\D/g, '')
            };
        });

    const filtra = () => {
        const testo = normalizza(campo.value);
        const parole = testo.split(/\s+/).filter(Boolean);
        const cercaTelefono = /^[+\d\s().-]+$/.test(testo) && /\d/.test(testo);
        const cifre = testo.replace(/\D/g, '');
        let visibili = 0;

        clienti.forEach(({ scheda, testo: anagrafica, telefono }) => {
            const corrisponde = cercaTelefono
                ? telefono.includes(cifre)
                : parole.every((parola) => anagrafica.includes(parola));
            scheda.hidden = !corrisponde;
            if (corrisponde) visibili++;
        });

        conteggio.textContent = testo
            ? `${visibili} di ${clienti.length} clienti`
            : `${clienti.length} clienti · Cognome e nome A–Z`;
        nessunRisultato.hidden = visibili !== 0;
        cancella.disabled = campo.value.length === 0;
    };

    campo.addEventListener('input', filtra);
    campo.addEventListener('search', filtra);
    campo.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape') {
            campo.value = '';
            filtra();
        }
    });
    cancella.addEventListener('click', () => {
        campo.value = '';
        filtra();
        campo.focus();
    });

    ricerca.hidden = false;
    filtra();
})();
