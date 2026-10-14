document.addEventListener('DOMContentLoaded', function () {
    var notifiche = document.querySelectorAll(
        '.area-cliente .messaggio-cliente[data-temporanea="true"]'
    );

    notifiche.forEach(function (notifica) {
        window.setTimeout(function () {
            notifica.classList.add('messaggio-in-uscita');
            window.setTimeout(function () {
                if (notifica.parentNode) {
                    notifica.parentNode.removeChild(notifica);
                }
            }, 320);
        }, 4000);
    });

    if (notifiche.length > 0 && window.history && window.history.replaceState) {
        var indirizzo = new URL(window.location.href);
        if (indirizzo.searchParams.has('esito')) {
            indirizzo.searchParams.delete('esito');
            window.history.replaceState({}, document.title, indirizzo.toString());
        }
    }
});
