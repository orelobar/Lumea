document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.form-lista-attesa');
    if (!form) return;

    const riepilogo = form.querySelector('.riepilogo-orario-attesa');
    const mesi = Array.from(form.querySelectorAll('details.mese-attesa'));

    function apriPercorso(campo) {
        if (!campo) return;
        const mese = campo.closest('details.mese-attesa');
        const giorno = campo.closest('details.giorno-attesa');
        if (mese) mese.open = true;
        if (giorno) giorno.open = true;
    }

    // Una sezione per volta: gli altri mesi/giorni restano compatti.
    mesi.forEach(function (mese) {
        mese.addEventListener('toggle', function () {
            if (!mese.open) return;
            mesi.forEach(function (altroMese) {
                if (altroMese !== mese) altroMese.open = false;
            });
        });
        mese.querySelectorAll('details.giorno-attesa').forEach(function (giorno) {
            giorno.addEventListener('toggle', function () {
                if (!giorno.open) return;
                mese.querySelectorAll('details.giorno-attesa').forEach(function (altroGiorno) {
                    if (altroGiorno !== giorno) altroGiorno.open = false;
                });
            });
        });
    });

    function aggiornaRiepilogo() {
        const scelta = form.querySelector('input[name="slot_attesa"]:checked');
        if (riepilogo && scelta) {
            riepilogo.hidden = false;
            riepilogo.textContent = 'Orario scelto: ' + scelta.dataset.riepilogo;
            riepilogo.classList.remove('orario-attesa-da-scegliere');
        }
        form.querySelectorAll('details.giorno-attesa, details.mese-attesa').forEach(function (dettaglio) {
            dettaglio.classList.toggle('contiene-orario-scelto', Boolean(scelta && dettaglio.contains(scelta)));
        });
        return scelta;
    }

    form.addEventListener('change', aggiornaRiepilogo);
    // La selezione obbligatoria viene controllata qui: i radio sono in pannelli richiudibili.
    form.addEventListener('submit', function (evento) {
        const scelta = aggiornaRiepilogo();
        if (scelta) return;
        evento.preventDefault();
        const primo = form.querySelector('input[name="slot_attesa"]');
        apriPercorso(primo);
        if (riepilogo) {
            riepilogo.hidden = false;
            riepilogo.textContent = 'Prima scegli un giorno e un orario.';
            riepilogo.classList.add('orario-attesa-da-scegliere');
        }
        const titoloGiorno = primo && primo.closest('details.giorno-attesa')?.querySelector('summary');
        if (titoloGiorno) titoloGiorno.focus();
    });
    window.addEventListener('pageshow', function () {
        const scelta = aggiornaRiepilogo();
        if (scelta) apriPercorso(scelta);
    });
    aggiornaRiepilogo();
});
