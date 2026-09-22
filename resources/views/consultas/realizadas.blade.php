{{--
    Tela "Consultas realizadas". Serve ao médico (só as dele) e à clínica
    (todas as unidades): quem decide isso é o controller, que cria o
    serviço preso ao dono. Esta view só imprime o $relatorio pronto.

    Trocas em relação ao mockup:
      "Confirmadas" -> "Realizadas" e "Em espera" -> "Não compareceram",
      porque esses são os status que existem em consultas.status.
      "Média de espera 12 min" -> "Comparecimento": o sistema não registra
      check-in nem início do atendimento (lacuna anotada no AI_HANDOFF.md).
--}}
@extends('layouts.painel')

@section('titulo', 'Consultas realizadas')

@section('conteudo')

    <a href="{{ $voltarUrl }}" class="fm-voltar">
        <x-icone nome="chevron-left" />
        Voltar ao início
    </a>

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Consultas realizadas</h1>
            <p class="fm-subtitulo">Acompanhe o desempenho das consultas de {{ $relatorio['de'] }} a {{ $relatorio['ate'] }}.</p>
        </div>

        <nav class="fm-abas" aria-label="Período">
            @foreach ($abas as $aba)
                <a
                    href="{{ $aba['url'] }}"
                    class="fm-aba {{ $aba['ativo'] ? 'is-ativa' : '' }}"
                    @if ($aba['ativo']) aria-current="true" @endif
                >{{ $aba['rotulo'] }}</a>
            @endforeach
        </nav>
    </div>

    {{-- Indicadores --}}
    <div class="fm-grade fm-grade--cartoes">
        @foreach ($relatorio['cartoes'] as $c)
            @include('painel.parciais.cartao', ['c' => $c])
        @endforeach
    </div>

    {{-- Evolução --}}
    <section class="fm-painel">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo">
                <x-icone nome="chart" />
                Evolução das consultas realizadas
            </h2>
            <span class="fm-painel__periodo">{{ $relatorio['serieRotulo'] }}</span>
        </header>

        <div
            class="fm-grafico fm-grafico--sozinho"
            data-fm-linha='@json($relatorio['serie'])'
            role="img"
            aria-label="Gráfico de linha com a evolução das consultas realizadas"
        ></div>
    </section>

    <div class="fm-duas">

        {{-- Por especialidade --}}
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo">
                    <x-icone nome="chart" />
                    Consultas por especialidade
                </h2>
            </header>

            @if (count($relatorio['especialidades']) > 0)
                <div class="fm-donut-bloco">
                    <div
                        class="fm-donut"
                        data-fm-donut='@json($relatorio['donutEspecialidades'])'
                        role="img"
                        aria-label="Consultas realizadas por especialidade"
                    ></div>

                    <ul class="fm-legenda">
                        @foreach ($relatorio['especialidades'] as $e)
                            <li>
                                <span class="fm-legenda__ponto" style="background: {{ $e['cor'] }}"></span>
                                <span class="fm-legenda__nome">{{ $e['nome'] }}</span>
                                <span class="fm-legenda__valor">{{ $e['pct'] }}%</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <p class="fm-vazio">Sem consultas realizadas neste período.</p>
            @endif
        </section>

        {{-- Ranking: por médico (clínica) ou por local (médico) --}}
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo">
                    <x-icone nome="users" />
                    {{ $relatorio['rankingTitulo'] }}
                </h2>
            </header>

            @if (count($relatorio['ranking']) > 0)
                <ul class="fm-barras">
                    @foreach ($relatorio['ranking'] as $r)
                        <li>
                            <div class="fm-barras__texto">
                                <span>{{ $r['nome'] }}</span>
                                <strong>{{ $r['total_fmt'] }}</strong>
                            </div>
                            <div class="fm-barras__trilho">
                                <div class="fm-barras__preenchido" style="width: {{ $r['largura'] }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="fm-vazio">Sem consultas realizadas neste período.</p>
            @endif
        </section>
    </div>

    {{-- Últimas consultas (qualquer desfecho) --}}
    <section class="fm-painel">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo">
                <x-icone nome="calendar" />
                Últimas consultas
            </h2>
        </header>

        @if (count($relatorio['ultimas']) > 0)
            <div class="fm-tabela" role="table" aria-label="Últimas consultas">
                <div class="fm-tabela__cabecalho" role="row">
                    <span role="columnheader">Data e hora</span>
                    <span role="columnheader">Paciente</span>
                    <span role="columnheader">{{ $relatorio['colunaExtra'] }}</span>
                    <span role="columnheader">Especialidade</span>
                    <span role="columnheader">Status</span>
                </div>

                @foreach ($relatorio['ultimas'] as $u)
                    <div class="fm-tabela__linha" role="row">
                        <span class="fm-tabela__quando" role="cell">{{ $u['quando'] }}</span>
                        <span class="fm-tabela__paciente" role="cell">
                            <span class="fm-avatar">{{ $u['iniciais'] }}</span>
                            {{ $u['paciente'] }}
                        </span>
                        <span class="fm-tabela__extra" role="cell">{{ $u['extra'] }}</span>
                        <span class="fm-tabela__esp" role="cell">{{ $u['especialidade'] }}</span>
                        <span class="fm-tabela__status" role="cell">
                            <span class="fm-etiqueta fm-etiqueta--{{ $u['tom'] }}">{{ $u['status'] }}</span>
                        </span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="fm-vazio">Ainda não há consultas para mostrar.</p>
        @endif
    </section>

    {{-- Informações gerais --}}
    <section class="fm-painel">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo">
                <x-icone nome="lightbulb" />
                Informações gerais
            </h2>
        </header>

        <div class="fm-info">
            @foreach ($relatorio['informacoes'] as $i)
                <div class="fm-info__item">
                    <span class="fm-info__icone"><x-icone :nome="$i['icone']" /></span>
                    <div>
                        <span class="fm-info__rotulo">{{ $i['rotulo'] }}</span>
                        <strong class="fm-info__valor">{{ $i['valor'] }}</strong>
                        <span class="fm-info__nota">{{ $i['nota'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

@endsection
