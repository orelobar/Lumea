document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.grafico-linea-contenitore').forEach(function (contenitore) {
        const canvas = contenitore.querySelector('.grafico-canvas');
        const tooltip = contenitore.querySelector('.tooltip-grafico');
        const dati = Array.from(contenitore.querySelectorAll('.grafico-dati li')).map(function (riga) {
            return {
                etichetta: riga.dataset.etichetta || '',
                data: riga.dataset.data || '',
                incasso: Number(riga.dataset.incasso) || 0,
                appuntamenti: Number(riga.dataset.appuntamenti) || 0,
                completati: Number(riga.dataset.completati) || 0
            };
        });

        if (!canvas || !dati.length) return;
        let punti = [];
        const euro = new Intl.NumberFormat('it-IT', {
            style: 'currency',
            currency: 'EUR'
        });

        function scala(massimo) {
            if (massimo <= 0) return {
                passo: 10,
                cima: 50
            };
            const grezzo = massimo / 5;
            const potenza = Math.pow(10, Math.floor(Math.log10(grezzo)));
            const multiplo = [1, 2, 5, 10].find(function (numero) {
                return numero * potenza >= grezzo;
            });
            const passo = multiplo * potenza;
            return {
                passo: passo,
                cima: Math.ceil(massimo / passo) * passo
            };
        }

        function disegna() {
            const larghezza = Math.max(contenitore.clientWidth - 20, dati.length > 18 ? 850 : 250);
            const altezza = larghezza < 500 ? 300 : 355;
            const rapporto = window.devicePixelRatio || 1;
            const ctx = canvas.getContext('2d');
            const margine = {
                sinistra: 68,
                destra: 25,
                alto: 25,
                basso: 55
            };
            const areaX = larghezza - margine.sinistra - margine.destra;
            const areaY = altezza - margine.alto - margine.basso;
            const asse = scala(Math.max.apply(null, dati.map(function (riga) {
                return riga.incasso;
            })));
            const divisioni = Math.round(asse.cima / asse.passo);

            canvas.width = Math.round(larghezza * rapporto);
            canvas.height = Math.round(altezza * rapporto);
            canvas.style.width = larghezza + 'px';
            canvas.style.height = altezza + 'px';
            ctx.setTransform(rapporto, 0, 0, rapporto, 0, 0);
            ctx.clearRect(0, 0, larghezza, altezza);
            ctx.font = '12px system-ui, sans-serif';
            ctx.textBaseline = 'middle';

            for (let indice = 0; indice <= divisioni; indice++) {
                const valore = indice * asse.passo;
                const y = margine.alto + areaY * (1 - valore / asse.cima);
                ctx.beginPath();
                ctx.moveTo(margine.sinistra, y);
                ctx.lineTo(larghezza - margine.destra, y);
                ctx.strokeStyle = indice === 0 ? '#a7b9ae' : '#dbe4dd';
                ctx.lineWidth = 1;
                ctx.stroke();
                ctx.fillStyle = '#50665c';
                ctx.textAlign = 'right';
                ctx.fillText('€ ' + new Intl.NumberFormat('it-IT', {
                    maximumFractionDigits: asse.passo < 1 ? 2 : 0
                }).format(valore), margine.sinistra - 10, y);
            }

            punti = dati.map(function (riga, indice) {
                return {
                    x: margine.sinistra + (dati.length === 1 ? areaX / 2 : indice * areaX / (dati.length - 1)),
                    y: margine.alto + areaY * (1 - riga.incasso / asse.cima),
                    riga: riga
                };
            });

            ctx.beginPath();
            punti.forEach(function (punto, indice) {
                if (indice === 0) ctx.moveTo(punto.x, punto.y);
                else ctx.lineTo(punto.x, punto.y);
            });
            ctx.strokeStyle = '#235c49';
            ctx.lineWidth = 3;
            ctx.lineJoin = 'round';
            ctx.stroke();

            const ogni = dati.length <= 7 ? 1 : Math.max(1, Math.ceil(dati.length / (larghezza < 500 ? 6 : 12)));
            punti.forEach(function (punto, indice) {
                ctx.beginPath();
                ctx.arc(punto.x, punto.y, 5, 0, Math.PI * 2);
                ctx.fillStyle = '#1c5447';
                ctx.fill();
                ctx.strokeStyle = '#fffaf1';
                ctx.lineWidth = 2;
                ctx.stroke();

                if (indice % ogni === 0 || indice === dati.length - 1) {
                    ctx.fillStyle = '#50665c';
                    ctx.textAlign = 'center';
                    ctx.font = larghezza < 500 ? '10px system-ui, sans-serif' : '12px system-ui, sans-serif';
                    const parti = punto.riga.etichetta.split(' ');
                    ctx.fillText(parti[0], punto.x, altezza - 31);
                    if (parti.length > 1) ctx.fillText(parti.slice(1).join(' '), punto.x, altezza - 17);
                }
            });
        }

        function nascondi() {
            tooltip.classList.remove('tooltip-visibile');
        }
        canvas.addEventListener('mousemove', function (evento) {
            const rettangolo = canvas.getBoundingClientRect();
            const x = evento.clientX - rettangolo.left;
            const y = evento.clientY - rettangolo.top;
            const vicino = punti.find(function (punto) {
                return Math.abs(punto.x - x) <= 12 && Math.abs(punto.y - y) <= 12;
            });
            if (!vicino) {
                nascondi();
                return;
            }
            const riga = vicino.riga;
            const partiData = riga.data.split('-');
            const dataCompleta = partiData.length === 3 ?
                partiData[2] + '/' + partiData[1] + '/' + partiData[0] :
                (partiData.length === 2 ? riga.etichetta + ' ' + partiData[0] : riga.etichetta);
            const riferimento = riga.etichetta.includes(':') ?
                dataCompleta + ' · ' + riga.etichetta :
                dataCompleta;
            tooltip.textContent = riferimento + ' · ' + euro.format(riga.incasso) +
                ' · ' + riga.appuntamenti + ' appuntamenti · ' + riga.completati + ' completati';
            tooltip.style.left = Math.max(8, Math.min(x + 12, rettangolo.width - 255)) + 'px';
            tooltip.style.top = Math.max(8, y - 46) + 'px';
            tooltip.classList.add('tooltip-visibile');
        });
        canvas.addEventListener('mouseleave', nascondi);
        disegna();
        if (window.ResizeObserver) new ResizeObserver(disegna).observe(contenitore);
        else window.addEventListener('resize', disegna);
    });
});
