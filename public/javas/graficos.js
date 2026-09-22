/* ==========================================================================
   FacilMed — gráficos dos painéis, em SVG puro (sem biblioteca).

   Por que sem biblioteca: o AGENTS.md §3 pede aprovação para dependência
   nova, e dois gráficos simples não justificam Chart.js. São ~120 linhas
   que o grupo consegue ler inteiras.

   Como funciona: o Blade escreve os dados num atributo da <div>, e este
   arquivo lê o JSON e desenha.

     <div class="fm-grafico" data-fm-linha='{"rotulos":["Jan","Fev"],"valores":[8,12]}'>
     <div class="fm-donut"   data-fm-donut='{"centro":"42","legenda":"médicos",
                                             "itens":[{"nome":"Cardio","total":12,"cor":"#1f6feb"}]}'>

   Sem JavaScript, os números continuam na tela (legendas e cartões são
   HTML comum); só o desenho não aparece.

   Barras e colunas não passam por aqui: são CSS puro.
   ========================================================================== */

(function () {
    'use strict';

    var NS = 'http://www.w3.org/2000/svg';
    var contador = 0;

    function criar(tag, atributos, pai) {
        var el = document.createElementNS(NS, tag);
        Object.keys(atributos || {}).forEach(function (k) {
            el.setAttribute(k, atributos[k]);
        });
        if (pai) { pai.appendChild(el); }
        return el;
    }

    function formatar(n) {
        return Number(n).toLocaleString('pt-BR');
    }

    /**
     * Escolhe um passo "redondo" para o eixo Y (1, 2, 2,5, 5, 10, 20, 25, 50...)
     * de modo que caibam uns 4 intervalos. 187 vira teto 200 em passos de 50.
     */
    function escala(max) {
        if (max <= 0) { return { passo: 1, teto: 4 }; }

        var bruto = max / 4;
        var pot = Math.pow(10, Math.floor(Math.log(bruto) / Math.LN10));
        var opcoes = [1, 2, 2.5, 5, 10];
        var passo = pot * 10;

        for (var i = 0; i < opcoes.length; i++) {
            if (opcoes[i] * pot >= bruto) { passo = opcoes[i] * pot; break; }
        }

        if (passo < 1) { passo = 1; }

        return { passo: passo, teto: Math.ceil(max / passo) * passo };
    }

    /* ---------------------------------------------------------------
       Gráfico de linha com área
       --------------------------------------------------------------- */
    function desenharLinha(container) {
        var dados;
        try { dados = JSON.parse(container.getAttribute('data-fm-linha')); }
        catch (e) { return; }

        var largura = container.clientWidth;
        var altura = container.clientHeight;
        if (!largura || !altura || !dados.valores || !dados.valores.length) { return; }

        container.innerHTML = '';

        var m = { t: 14, r: 14, b: 30, l: 44 };
        var areaL = largura - m.l - m.r;
        var areaA = altura - m.t - m.b;

        var svg = criar('svg', {
            viewBox: '0 0 ' + largura + ' ' + altura,
            'aria-hidden': 'true'
        }, container);

        var id = 'fm-grad-' + (++contador);
        var defs = criar('defs', {}, svg);
        var grad = criar('linearGradient', { id: id, x1: 0, y1: 0, x2: 0, y2: 1 }, defs);
        criar('stop', { offset: '0%', 'stop-color': '#1f6feb', 'stop-opacity': '.22' }, grad);
        criar('stop', { offset: '100%', 'stop-color': '#1f6feb', 'stop-opacity': '.02' }, grad);

        var maximo = Math.max.apply(null, dados.valores);
        var esc = escala(maximo);

        function y(v) { return m.t + areaA - (v / esc.teto) * areaA; }

        // Linhas de grade e números do eixo Y
        for (var v = 0; v <= esc.teto + 1e-9; v += esc.passo) {
            var yy = y(v);
            criar('line', { x1: m.l, x2: largura - m.r, y1: yy, y2: yy, 'class': 'fm-g-grade' }, svg);
            var t = criar('text', { x: m.l - 10, y: yy + 4, 'text-anchor': 'end', 'class': 'fm-g-eixo' }, svg);
            t.textContent = formatar(v);
        }

        var n = dados.valores.length;
        var folga = Math.min(18, areaL / 12);
        var passoX = n > 1 ? (areaL - 2 * folga) / (n - 1) : 0;

        function x(i) { return n > 1 ? m.l + folga + i * passoX : m.l + areaL / 2; }

        // Área sob a linha
        var pontos = dados.valores.map(function (val, i) { return [x(i), y(val)]; });
        var linha = pontos.map(function (p, i) { return (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1); }).join(' ');
        var base = m.t + areaA;

        criar('path', {
            d: linha + ' L' + pontos[n - 1][0].toFixed(1) + ' ' + base + ' L' + pontos[0][0].toFixed(1) + ' ' + base + ' Z',
            fill: 'url(#' + id + ')'
        }, svg);

        criar('path', { d: linha, 'class': 'fm-g-linha' }, svg);

        // Rótulos do eixo X: pula alguns quando estão apertados
        var cada = passoX > 0 ? Math.max(1, Math.ceil(56 / passoX)) : 1;
        dados.rotulos.forEach(function (rotulo, i) {
            if (i % cada !== 0) { return; }
            var tx = criar('text', { x: x(i), y: altura - 8, 'text-anchor': 'middle', 'class': 'fm-g-eixo' }, svg);
            tx.textContent = rotulo;
        });

        // Pontos (com dica ao passar o mouse)
        if (n <= 40) {
            pontos.forEach(function (p, i) {
                var c = criar('circle', { cx: p[0], cy: p[1], r: n > 24 ? 3 : 4.5, 'class': 'fm-g-ponto' }, svg);
                var dica = criar('title', {}, c);
                dica.textContent = dados.rotulos[i] + ': ' + formatar(dados.valores[i]) + (dados.valores[i] === 1 ? ' consulta' : ' consultas');
            });
        }
    }

    /* ---------------------------------------------------------------
       Rosca (donut)
       --------------------------------------------------------------- */
    function desenharDonut(container) {
        var dados;
        try { dados = JSON.parse(container.getAttribute('data-fm-donut')); }
        catch (e) { return; }

        container.innerHTML = '';

        var itens = (dados.itens || []).filter(function (i) { return i.total > 0; });
        var total = itens.reduce(function (soma, i) { return soma + i.total; }, 0);

        var tam = 168, meio = tam / 2, raio = 66, espessura = 26;
        var circ = 2 * Math.PI * raio;

        var svg = criar('svg', { viewBox: '0 0 ' + tam + ' ' + tam, 'aria-hidden': 'true' }, container);

        criar('circle', {
            cx: meio, cy: meio, r: raio, fill: 'none', stroke: '#eaf1fb', 'stroke-width': espessura
        }, svg);

        var folga = itens.length > 1 ? 3 : 0;
        var acumulado = 0;

        itens.forEach(function (item) {
            var parte = (item.total / total) * circ;
            var traco = Math.max(parte - folga, 0.5);

            var c = criar('circle', {
                cx: meio, cy: meio, r: raio, fill: 'none',
                stroke: item.cor, 'stroke-width': espessura,
                'stroke-dasharray': traco.toFixed(2) + ' ' + (circ - traco).toFixed(2),
                'stroke-dashoffset': (-acumulado).toFixed(2),
                transform: 'rotate(-90 ' + meio + ' ' + meio + ')'
            }, svg);

            var dica = criar('title', {}, c);
            dica.textContent = item.nome + ': ' + formatar(item.total);

            acumulado += parte;
        });

        var centro = document.createElement('div');
        centro.className = 'fm-donut__centro';

        var forte = document.createElement('strong');
        forte.textContent = dados.centro;

        var legenda = document.createElement('span');
        legenda.textContent = dados.legenda || '';

        centro.appendChild(forte);
        centro.appendChild(legenda);
        container.appendChild(centro);
    }

    /* ---------------------------------------------------------------
       Inicialização
       --------------------------------------------------------------- */
    function iniciar() {
        var linhas = document.querySelectorAll('[data-fm-linha]');
        Array.prototype.forEach.call(linhas, function (el) {
            desenharLinha(el);

            // Redesenha quando a largura muda (girar o celular, abrir o menu...).
            if ('ResizeObserver' in window) {
                var ultima = el.clientWidth;
                new ResizeObserver(function () {
                    if (el.clientWidth !== ultima) {
                        ultima = el.clientWidth;
                        desenharLinha(el);
                    }
                }).observe(el);
            }
        });

        Array.prototype.forEach.call(document.querySelectorAll('[data-fm-donut]'), desenharDonut);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})();
