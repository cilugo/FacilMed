{{--
    Dashboard da clínica. Dados prontos de
    App\Http\Controllers\Clinica\DashboardController.

    Sem "Ligações", sem "Financeiro" e sem teleconsulta: não existem no
    sistema (AGENTS.md §2). Ver o comentário do controller.

    Os gráficos de linha e de rosca são desenhados por public/js/graficos.js
    a partir dos atributos data-fm-linha e data-fm-donut. Barras e colunas
    são só CSS.
--}}
@extends('layouts.painel')

@section('titulo', 'Início')

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Olá, {{ $saudacao }}!</h1>
            <p class="fm-subtitulo">Bem-vinda ao painel de {{ $nomeClinica }}.</p>
        </div>

        <div class="fm-data">
            <x-icone nome="calendar" />
            <span>{{ $dataHoje }}</span>
        </div>
    </div>

    {{-- Indicadores --}}
    <div class="fm-grade fm-grade--cartoes">
        @foreach ($cartoes as $c)
            @include('painel.parciais.cartao', ['c' => $c])
        @endforeach
    </div>

    {{-- Consultas realizadas ao longo do tempo --}}
    <section class="fm-painel">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo">
                <x-icone nome="chart" />
                Consultas realizadas
            </h2>

            <nav class="fm-abas" aria-label="Período do gráfico">
                @foreach ($grafico['abas'] as $aba)
                    <a
                        href="{{ $aba['url'] }}"
                        class="fm-aba {{ $aba['ativo'] ? 'is-ativa' : '' }}"
                        @if ($aba['ativo']) aria-current="true" @endif
                    >{{ $aba['rotulo'] }}</a>
                @endforeach
            </nav>
        </header>

        <div class="fm-grafico-bloco">
            <div
                class="fm-grafico"
                data-fm-linha='@json($grafico['serie'])'
                role="img"
                aria-label="Gráfico de linha com as consultas realizadas no período"
            ></div>

            <aside class="fm-resumo">
                <span class="fm-resumo__rotulo">Total no período</span>
                <strong class="fm-resumo__valor">{{ $grafico['total'] }}</strong>
                <span class="fm-resumo__legenda">{{ $grafico['rotulo'] }}</span>

                <ul class="fm-resumo__lista">
                    @foreach ($grafico['formas'] as $f)
                        <li>
                            <span class="fm-legenda__ponto" style="background: {{ $f['cor'] }}"></span>
                            <span class="fm-resumo__nome">{{ $f['nome'] }}</span>
                            <strong>{{ $f['total'] }}</strong>
                            <span class="fm-resumo__pct">({{ $f['pct'] }}%)</span>
                        </li>
                    @endforeach
                </ul>
            </aside>
        </div>
    </section>

    <div class="fm-duas">

        {{-- Médicos ativos por especialidade --}}
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo">
                    <x-icone nome="doctors" />
                    Médicos ativos
                </h2>
                <a href="{{ $medicosAtivos['url'] }}" class="fm-pilula fm-pilula--pequena">
                    Ver todos
                    <x-icone nome="chevron-right" />
                </a>
            </header>

            @if (count($medicosAtivos['itens']) > 0)
                <div class="fm-donut-bloco">
                    <div
                        class="fm-donut"
                        data-fm-donut='@json($medicosAtivos['donut'])'
                        role="img"
                        aria-label="Médicos ativos por especialidade"
                    ></div>

                    <ul class="fm-legenda">
                        @foreach ($medicosAtivos['itens'] as $i)
                            <li>
                                <span class="fm-legenda__ponto" style="background: {{ $i['cor'] }}"></span>
                                <span class="fm-legenda__nome">{{ $i['nome'] }}</span>
                                <span class="fm-legenda__valor">{{ $i['total_fmt'] }} <small>({{ $i['pct'] }}%)</small></span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <p class="fm-vazio">Nenhum médico vinculado às suas unidades ainda.</p>
            @endif
        </section>

        {{-- Consultas por especialidade --}}
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo">
                    <x-icone nome="chart" />
                    Consultas por especialidade
                </h2>
                <span class="fm-painel__periodo">últimos 30 dias</span>
            </header>

            @if (count($porEspecialidade) > 0)
                <ul class="fm-barras">
                    @foreach ($porEspecialidade as $e)
                        <li>
                            <div class="fm-barras__texto">
                                <span>{{ $e['nome'] }}</span>
                                <strong>{{ $e['pct'] }}%</strong>
                            </div>
                            <div class="fm-barras__trilho">
                                <div class="fm-barras__preenchido" style="width: {{ $e['largura'] }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="fm-vazio">Sem consultas realizadas nos últimos 30 dias.</p>
            @endif
        </section>
    </div>

    <div class="fm-duas">

        {{-- Agenda dos médicos hoje --}}
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo">
                    <x-icone nome="calendar" />
                    Agenda dos médicos hoje
                </h2>
                <a href="{{ $agendaUrl }}" class="fm-pilula fm-pilula--pequena">
                    Ver todos
                    <x-icone nome="chevron-right" />
                </a>
            </header>

            @if (count($agendaMedicos) > 0)
                <ul class="fm-lista">
                    @foreach ($agendaMedicos as $m)
                        <li class="fm-pessoa">
                            <span class="fm-avatar fm-avatar--grande">{{ $m['iniciais'] }}</span>
                            <div class="fm-pessoa__info">
                                <strong>{{ $m['nome'] }}</strong>
                                <span>{{ $m['especialidade'] }}</span>
                            </div>
                            <div class="fm-pessoa__lado">
                                <span>{{ $m['janela'] }}</span>
                                <strong>{{ $m['consultas_fmt'] }}</strong>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="fm-vazio">Nenhum médico atende hoje nas suas unidades.</p>
            @endif
        </section>

        {{-- Consultas por convênio (a fatia "Particular" entra na lista) --}}
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo">
                    <x-icone nome="shield" />
                    Consultas por convênio
                </h2>
                <a href="{{ $convenioUrl }}" class="fm-pilula fm-pilula--pequena">
                    Ver todos
                    <x-icone nome="chevron-right" />
                </a>
            </header>

            @if (count($porConvenio) > 0)
                <ul class="fm-lista">
                    @foreach ($porConvenio as $c)
                        <li class="fm-pessoa">
                            <span class="fm-avatar fm-avatar--grande fm-avatar--cor" style="background: {{ $c['cor'] }}">{{ $c['iniciais'] }}</span>
                            <div class="fm-pessoa__info">
                                <strong>{{ $c['nome'] }}</strong>
                                <span>{{ $c['detalhe'] }}</span>
                            </div>
                            <div class="fm-pessoa__lado">
                                <strong>{{ $c['pct'] }}%</strong>
                                <span>do total</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="fm-vazio">Sem consultas realizadas nos últimos 30 dias.</p>
            @endif
        </section>
    </div>

    <div class="fm-duas">

        {{-- Consultas por dia da semana --}}
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo">
                    <x-icone nome="calendar" />
                    Consultas por dia da semana
                </h2>
                <span class="fm-painel__periodo">últimos 30 dias</span>
            </header>

            <div class="fm-colunas" role="img" aria-label="Consultas realizadas por dia da semana">
                @foreach ($diasDaSemana as $d)
                    <div class="fm-coluna">
                        <span class="fm-coluna__valor">{{ $d['total'] }}</span>
                        <div class="fm-coluna__area">
                            <div class="fm-coluna__barra" style="height: {{ $d['altura'] }}%"></div>
                        </div>
                        <span class="fm-coluna__rotulo">{{ $d['rotulo'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Últimos agendamentos --}}
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo">
                    <x-icone nome="list" />
                    Últimos agendamentos
                </h2>
                <a href="{{ $agendaUrl }}" class="fm-pilula fm-pilula--pequena">
                    Ver todos
                    <x-icone nome="chevron-right" />
                </a>
            </header>

            @if (count($ultimos) > 0)
                <ul class="fm-lista">
                    @foreach ($ultimos as $u)
                        <li class="fm-pessoa">
                            <span class="fm-avatar fm-avatar--grande">{{ $u['iniciais'] }}</span>
                            <div class="fm-pessoa__info">
                                <strong>{{ $u['paciente'] }}</strong>
                                <span>{{ $u['especialidade'] }} · {{ $u['quando'] }}</span>
                            </div>
                            <span class="fm-etiqueta fm-etiqueta--{{ $u['tom'] }}">{{ $u['status'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="fm-vazio">Nenhum agendamento ainda.</p>
            @endif
        </section>
    </div>

@endsection
