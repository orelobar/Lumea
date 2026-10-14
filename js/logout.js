document.addEventListener('DOMContentLoaded', function () {
    const elementi = document.querySelectorAll('.js-conferma-azione');

    if (!elementi.length) {
        return;
    }

    const overlay = document.createElement('div');
    overlay.className = 'conferma-lumea';
    overlay.hidden = true;
    overlay.innerHTML = `
        <div class="conferma-lumea__sfondo" data-chiudi-conferma></div>
        <section class="conferma-lumea__dialogo" role="dialog" aria-modal="true" aria-labelledby="titolo-conferma-lumea" aria-describedby="testo-conferma-lumea">
            <button class="conferma-lumea__chiudi" type="button" aria-label="Chiudi" data-chiudi-conferma></button>
            <span class="conferma-lumea__icona" aria-hidden="true">L</span>
            <h2 id="titolo-conferma-lumea"></h2>
            <p id="testo-conferma-lumea"></p>
            <div class="conferma-lumea__azioni">
                <button class="conferma-lumea__annulla" type="button" data-chiudi-conferma>Annulla</button>
                <button class="conferma-lumea__conferma" type="button"></button>
            </div>
        </section>`;
    document.body.appendChild(overlay);

    const titolo = overlay.querySelector('#titolo-conferma-lumea');
    const testo = overlay.querySelector('#testo-conferma-lumea');
    const conferma = overlay.querySelector('.conferma-lumea__conferma');
    const annulla = overlay.querySelector('.conferma-lumea__annulla');
    let azioneConfermata = null;
    let elementoOrigine = null;

    function chiudi() {
        overlay.hidden = true;
        document.body.classList.remove('conferma-lumea-aperta');
        azioneConfermata = null;
        if (elementoOrigine) {
            elementoOrigine.focus();
        }
    }

    function apri(elemento, azione) {
        elementoOrigine = elemento;
        titolo.textContent = elemento.dataset.confirmTitle || 'Confermare l’operazione?';
        testo.textContent = elemento.dataset.confirmMessage || 'Vuoi procedere?';
        conferma.textContent = elemento.dataset.confirmAction || 'Conferma';
        azioneConfermata = azione;
        overlay.hidden = false;
        document.body.classList.add('conferma-lumea-aperta');
        window.setTimeout(function () { annulla.focus(); }, 0);
    }

    elementi.forEach(function (elemento) {
        elemento.addEventListener('click', function (evento) {
            evento.preventDefault();

            apri(elemento, function () {
                if (elemento.tagName === 'A') {
                    window.location.href = elemento.href;
                    return;
                }

                if (elemento.form) {
                    elemento.form.requestSubmit(elemento);
                }
            });
        });
    });

    overlay.querySelectorAll('[data-chiudi-conferma]').forEach(function (elemento) {
        elemento.addEventListener('click', chiudi);
    });

    conferma.addEventListener('click', function () {
        const azione = azioneConfermata;
        overlay.hidden = true;
        document.body.classList.remove('conferma-lumea-aperta');
        azioneConfermata = null;
        if (azione) {
            azione();
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (!overlay.hidden && evento.key === 'Escape') {
            chiudi();
        }
    });
});
