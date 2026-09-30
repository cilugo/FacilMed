{{--
    Dashboard do admin (24/09/2026). Dados prontos de
    App\Http\Controllers\Admin\DashboardController — ver ali o que mudou
    em relação ao mockup e por quê.

    Gráficos de linha e rosca: public/javas/graficos.js (data-fm-linha,
    data-fm-donut), os mesmos dos outros painéis.
--}}
@extends('layouts.painel')

@section('titulo', 'Início')

@push('head')
    <style>
        .adm-linha { display: grid; gap: 18px; margin-top: 18px; }
        .adm-linha > .fm-painel { margin: 0; min-width: 0; }
        @media (min-width: 1100px) {
            .adm-linha--tres { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (min-width: 1100px) and (max-width: 1399px) {
            .fm-grade--cartoes.adm-cartoes { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (min-width: 1400px) {
            .fm-grade--cartoes.adm-cartoes { grid-template-columns: repeat(5, minmax(0, 1fr)); }
        }
        .adm-mini { display: grid; grid-template-columns: 40px minmax(0, 1fr) auto; gap: 12px; align-items: center; padding: 10px 0; }
        .adm-mini + .adm-mini { border-top: 1px solid #e7eff9; }
        .adm-mini__icone { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
        .adm-mini strong { display: block; color: var(--fm-titulo); font-size: 14px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .adm-mini small { display: block; color: var(--fm-suave); font-size: 12.5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .adm-mini__lado { font-size: 12.5px; color: var(--fm-suave); text-align: right; }
        .adm-numeros { display: flex; flex-wrap: wrap; gap: 2px 12px; margin-top: 3px; font-size: 12.5px; color: var(--fm-suave); }
        .adm-numeros span { white-space: nowrap; display: inline-flex; gap: 4px; align-items: center; }
        .adm-numeros .fm-icone { width: 14px; height: 14px; }
    </style>
@endpush

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Olá, {{ $saudacao }}!</h1>
            <p class="fm-subtitulo">Acompanhe a plataforma e gerencie clínicas, médicos e atendimentos.</p>
        </div>
        <div class="fm-data">
            <x-icone nome="calendar" />
            <span>{{ $dataHoje }}</span>
        </div>
    </div>

    <div class="fm-grade fm-grade--cartoes adm-cartoes">
        @foreach ($cartoes as $c)
            @include('painel.parciais.cartao', ['c' => $c])
        @endforeach
    </div>

    {{-- ================= Gráfico ================= --}}
    <div class="adm-linha adm-linha--grafico">
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="chart" /> Consultas realizadas</h2>
                <nav class="fm-abas" aria-label="Período do gráfico">
                    @foreach ($grafico['abas'] as $aba)
                        <a href="{{ $aba['url'] }}" class="fm-aba {{ $aba['ativo'] ? 'is-ativa' : '' }}" @if ($aba['ativo']) aria-current="true" @endif>{{ $aba['rotulo'] }}</a>
                    @endforeach
                </nav>
            </header>
            <p class="fm-meta" style="margin: -6px 0 8px;"><strong style="color: var(--fm-titulo); font-size: 18px;">{{ $grafico['total'] }}</strong> {{ $grafico['rotulo'] }}</p>
            <div class="fm-grafico" data-fm-linha='@json($grafico['serie'])' role="img" aria-label="Consultas realizadas no período"></div>
        </section>
    </div>

    {{-- ================= Roscas + atividade ================= --}}
    <div class="adm-linha adm-linha--tres">
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="building" /> Unidades com mais consultas</h2>
                <span class="fm-painel__periodo">30 dias</span>
            </header>
            @if (count($porUnidade['itens']) > 0)
                <div class="fm-donut-bloco">
                    <div class="fm-donut" data-fm-donut='@json($porUnidade['donut'])' role="img" aria-label="Consultas por unidade"></div>
                    <ul class="fm-legenda">
                        @foreach ($porUnidade['itens'] as $i)
                            <li>
                                <span class="fm-legenda__ponto" style="background: {{ $i['cor'] }}"></span>
                                <span class="fm-legenda__nome">{{ $i['nome'] }}</span>
                                <span class="fm-legenda__valor">{{ $i['pct'] }}%</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <p class="fm-vazio">Sem consultas realizadas nos últimos 30 dias.</p>
            @endif
        </section>

        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="list" /> Consultas por situação</h2>
                <span class="fm-painel__periodo">30 dias antes e depois de hoje</span>
            </header>
            @if ($porStatus['donut']['centro'] !== '0')
                <div class="fm-donut-bloco">
                    <div class="fm-donut" data-fm-donut='@json($porStatus['donut'])' role="img" aria-label="Consultas por situação"></div>
                    <ul class="fm-legenda">
                        @foreach ($porStatus['itens'] as $i)
                            <li>
                                <span class="fm-legenda__ponto" style="background: {{ $i['cor'] }}"></span>
                                <span class="fm-legenda__nome">{{ $i['nome'] }}</span>
                                <span class="fm-legenda__valor">{{ $i['pct'] }}%</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <p class="fm-vazio">Nenhuma consulta nos últimos 30 dias.</p>
            @endif
        </section>

        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="clock" /> Atividade recente</h2>
                <a href="{{ route('admin.consultas') }}" class="fm-pilula fm-pilula--pequena">Ver todas <x-icone nome="chevron-right" /></a>
            </header>
            @forelse ($atividades as $a)
                <div class="adm-mini">
                    <span class="adm-mini__icone fm-tom-{{ $a['tom'] }}"><x-icone :nome="$a['icone']" /></span>
                    <div><strong>{{ $a['titulo'] }}</strong><small>{{ $a['detalhe'] }}</small></div>
                    <span class="adm-mini__lado">{{ $a['quando'] }}</span>
                </div>
            @empty
                <p class="fm-vazio">Nada por enquanto.</p>
            @endforelse
        </section>
    </div>

    {{-- ================= Listas ================= --}}
    <div class="adm-linha adm-linha--tres">
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="building" /> Clínicas e hospitais</h2>
                <a href="{{ route('admin.clinicas') }}" class="fm-pilula fm-pilula--pequena">Ver todas <x-icone nome="chevron-right" /></a>
            </header>
            @forelse ($clinicas as $c)
                <a href="{{ $c['url'] }}" class="adm-mini">
                    <span class="adm-mini__icone fm-tom-azul"><x-icone :nome="$c['hospital'] ? 'hospital' : 'building'" /></span>
                    <div>
                        <strong>{{ $c['nome'] }}</strong>
                        <small>{{ $c['cidade'] }}</small>
                        <span class="adm-numeros">
                            <span><x-icone nome="doctors" /> {{ $c['medicos'] }} {{ $c['medicos'] === 1 ? 'médico' : 'médicos' }}</span>
                            <span><x-icone nome="calendar-check" /> {{ $c['consultas'] }} em 30 dias</span>
                        </span>
                    </div>
                    <span class="fm-etiqueta fm-etiqueta--{{ $c['tom'] }}">{{ $c['status'] }}</span>
                </a>
            @empty
                <p class="fm-vazio">Nenhuma clínica cadastrada.</p>
            @endforelse
        </section>

        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="doctors" /> Médicos recentes</h2>
                <a href="{{ route('admin.usuarios') }}" class="fm-pilula fm-pilula--pequena">Ver todos <x-icone nome="chevron-right" /></a>
            </header>
            @forelse ($medicos as $m)
                <div class="adm-mini">
                    <span class="fm-avatar">{{ $m['iniciais'] }}</span>
                    <div><strong>{{ $m['nome'] }}</strong><small>{{ $m['especialidade'] }} · {{ $m['local'] }}</small></div>
                    <span class="fm-etiqueta fm-etiqueta--{{ $m['tom'] }}">{{ $m['status'] }}</span>
                </div>
            @empty
                <p class="fm-vazio">Nenhum médico cadastrado.</p>
            @endforelse
        </section>

        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="users" /> Últimos cadastros</h2>
                <a href="{{ route('admin.usuarios') }}" class="fm-pilula fm-pilula--pequena">Ver todos <x-icone nome="chevron-right" /></a>
            </header>
            @foreach ($ultimosCadastros as $u)
                <div class="adm-mini">
                    <span class="adm-mini__icone fm-tom-roxo"><x-icone :nome="$u['icone']" /></span>
                    <div><strong>{{ $u['nome'] }}</strong><small>{{ $u['tipo'] }}</small></div>
                    <span class="adm-mini__lado">{{ $u['quando'] }}</span>
                </div>
            @endforeach
        </section>
    </div>

@endsection
