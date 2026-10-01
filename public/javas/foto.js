/*
 * Reduz a foto ANTES de enviar (01/10/2026).
 *
 * Foto de celular costuma ter 3 a 8 MB, e o FacilMed aceita até 2 MB (a foto
 * fica guardada no banco - ver app/Models/Foto.php). Então, quando a pessoa
 * escolhe a imagem num <input type="file" data-reduzir-foto>, este script:
 *   1. desenha a imagem num <canvas> com no máximo 1280 px no lado maior;
 *   2. troca o arquivo do campo por um JPEG (qualidade 0,85) bem menor;
 *   3. mostra a prévia numa <img data-previa-foto> do mesmo formulário, se houver.
 *
 * Se o navegador não conseguir (muito antigo, ou arquivo que não é imagem), o
 * arquivo original segue como está - e o servidor confere de novo do mesmo
 * jeito (Foto::regras). Isto é conforto, não validação.
 */
(function () {
    'use strict';

    var LADO_MAXIMO = 1280;

    function previa(input, url) {
        var form = input.closest('form');
        var img = form && form.querySelector('[data-previa-foto]');
        if (img) {
            img.src = url;
            img.hidden = false;
        }
    }

    document.addEventListener('change', function (evento) {
        var input = evento.target;
        if (!input.matches || !input.matches('input[type="file"][data-reduzir-foto]')) {
            return;
        }
        var arquivo = input.files && input.files[0];
        if (!arquivo || !/^image\//.test(arquivo.type) || !window.DataTransfer || !window.URL) {
            return;
        }

        var img = new Image();
        var url = URL.createObjectURL(arquivo);
        img.onload = function () {
            var escala = Math.min(1, LADO_MAXIMO / Math.max(img.width, img.height));
            var canvas = document.createElement('canvas');
            canvas.width = Math.round(img.width * escala);
            canvas.height = Math.round(img.height * escala);
            canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
            URL.revokeObjectURL(url);

            canvas.toBlob(function (blob) {
                if (!blob || blob.size >= arquivo.size) {
                    previa(input, URL.createObjectURL(arquivo));
                    return; // a original já era menor: fica ela
                }
                var nome = arquivo.name.replace(/\.[^.]+$/, '') + '.jpg';
                var lista = new DataTransfer();
                lista.items.add(new File([blob], nome, { type: 'image/jpeg' }));
                input.files = lista.files;
                previa(input, URL.createObjectURL(blob));
            }, 'image/jpeg', 0.85);
        };
        img.onerror = function () { URL.revokeObjectURL(url); };
        img.src = url;
    });
})();
