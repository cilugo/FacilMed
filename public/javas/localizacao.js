/*
 * "Usar minha localização" (29/09/2026) — tela de locais e busca da home.
 *
 * Um botão com data-localizacao="URL" dentro de um <form>:
 *   1. pede a posição ao navegador. É o NAVEGADOR que pergunta
 *      "Permitir que este site acesse sua localização?" — o site não tem
 *      como ler a posição sem essa permissão;
 *   2. preenche os campos escondidos lat e lng (cria se não existirem),
 *      limpa a cidade escolhida e envia o formulário para a URL do botão.
 *
 * A posição NÃO é salva em lugar nenhum: vai só no endereço desta busca,
 * arredondada para 3 casas (uns 100 m), o suficiente para ordenar por distância.
 *
 * Os navegadores só liberam a localização em https ou em localhost
 * (XAMPP em http://localhost/FacilMed funciona; pelo IP da rede, não).
 */
(function () {
    'use strict';

    function avisar(form, texto, erro) {
        var aviso = form.querySelector('[data-localizacao-status]');
        if (!aviso) {
            aviso = document.createElement('p');
            aviso.className = 'localizacao-aviso';
            aviso.setAttribute('data-localizacao-status', '');
            form.appendChild(aviso);
        }
        aviso.textContent = texto;
        aviso.classList.toggle('localizacao-aviso--erro', !!erro);
        aviso.setAttribute('role', erro ? 'alert' : 'status');
    }

    function campo(form, nome, valor) {
        var input = form.querySelector('input[name="' + nome + '"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = nome;
            form.appendChild(input);
        }
        input.value = valor;
    }

    document.addEventListener('click', function (evento) {
        var botao = evento.target.closest('[data-localizacao]');
        if (!botao) {
            return;
        }
        var form = botao.closest('form');

        if (!('geolocation' in navigator) || !window.isSecureContext) {
            avisar(form, 'Seu navegador não liberou a localização nesta página. Escolha a cidade na lista.', true);
            return;
        }

        botao.disabled = true;
        avisar(form, 'Aguardando você permitir o acesso à localização no navegador...', false);

        navigator.geolocation.getCurrentPosition(function (posicao) {
            campo(form, 'lat', posicao.coords.latitude.toFixed(3));
            campo(form, 'lng', posicao.coords.longitude.toFixed(3));

            var cidade = form.querySelector('[name="cidade"]');
            if (cidade) {
                cidade.value = '';
            }

            form.action = botao.getAttribute('data-localizacao');
            form.submit();
        }, function (erro) {
            botao.disabled = false;
            avisar(form, erro.code === 1
                ? 'Você não permitiu o acesso à localização. Tudo bem: escolha a cidade na lista.'
                : 'Não deu para pegar sua localização agora. Escolha a cidade na lista.', true);
        }, { enableHighAccuracy: false, timeout: 15000, maximumAge: 300000 });
    });
})();
