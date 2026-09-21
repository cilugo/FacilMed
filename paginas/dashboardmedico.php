{--
    Dashboard do médico. Os dados vêm prontos de
    App\Http\Controllers\Medico\DashboardController — aqui só se imprime.
    Nada de regra de negócio em Blade (AGENTS.md §7).
--}}
@extends('layouts.painel')

@section('titulo', 'Início')

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Olá, {{ $saudacao }}!</h1>
            <p class="fm-subtitulo">Veja um resumo da sua rotina de hoje.</p>
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

    <div class="fm-destaque">
        @include('painel.parciais.cartao', ['c' => $realizadas])
    </div>

    <div class="fm-duas fm-duas--agenda">

        {{-- Agenda de hoje --}}
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo">
                    <x-icone nome="calendar" />
                    Minha agenda de hoje
                </h2>
                <a href="{{ $agendaUrl }}" class="fm-pilula">
                    Ver agenda completa
                    <x-icone nome="chevron-right" />
                </a>
            </header>

            @if (count($agenda) > 0)
                <ul class="fm-lista">
                    @foreach ($agenda as $a)
                        <li class="fm-agenda">
                            <span class="fm-agenda__hora">{{ $a['hora'] }}</span>
                            <span class="fm-ponto fm-ponto--{{ $a['tom'] }}"></span>

                            <div class="fm-agenda__info">
                                <strong>{{ $a['paciente'] }}</strong>
                                <span>{{ $a['especialidade'] }} · {{ $a['local'] }}</span>
                            </div>

                            <div class="fm-agenda__acoes">
                                @if (! empty($a['etiqueta']))
                                    <span class="fm-etiqueta fm-etiqueta--{{ $a['tom'] }}">{{ $a['etiqueta'] }}</span>
                                @endif

                                <a href="{{ $agendaUrl }}" class="fm-pilula fm-pilula--pequena">
                                    Ver detalhes
                                    <x-icone nome="chevron-right" />
                                </a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="fm-vazio">Você não tem consultas marcadas para hoje.</p>
            @endif
        </section>

        {{-- Atalhos: os mesmos itens do menu --}}
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo">
                    <x-icone nome="bolt" />
                    Ações rápidas
                </h2>
            </header>

            <ul class="fm-atalhos">
                @foreach ($atalhos as $atalho)
                    <li>
                        <a href="{{ $atalho['url'] }}" class="fm-atalho">
                            <span class="fm-atalho__icone"><x-icone :nome="$atalho['icone']" /></span>
                            <span class="fm-atalho__rotulo">{{ $atalho['rotulo'] }}</span>
                            <x-icone nome="chevron-right" class="fm-atalho__seta" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    <div class="fm-duas">

        {{-- Últimas consultas realizadas --}}
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo">
                    <x-icone nome="clock" />
                    Histórico recente
                </h2>
                <a href="{{ $realizadas['url'] }}" class="fm-pilula fm-pilula--pequena">
                    Ver todos
                    <x-icone nome="chevron-right" />
                </a>
            </header>

            @if (count($historico) > 0)
                <ul class="fm-lista">
                    @foreach ($historico as $h)
                        <li class="fm-linha">
                            <span class="fm-linha__data">{{ $h['data'] }}</span>
                            <div class="fm-linha__info">
                                <strong>{{ $h['paciente'] }}</strong>
                                <span>{{ $h['especialidade'] }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="fm-vazio">Suas consultas realizadas vão aparecer aqui.</p>
            @endif
        </section>

        {{-- Nota pública. O comentário fica na tela de avaliações. --}}
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo">
                    <x-icone nome="star" />
                    Sua avaliação
                </h2>
                <a href="{{ $avaliacao['url'] }}" class="fm-pilula fm-pilula--pequena">
                    Ver avaliações
                    <x-icone nome="chevron-right" />
                </a>
            </header>

            @if ($avaliacao['media'])
                <div class="fm-nota">
                    <strong class="fm-nota__valor">{{ $avaliacao['media'] }}</strong>
                    <span class="fm-nota__escala">de 5 estrelas</span>
                    <span class="fm-nota__total">{{ $avaliacao['total_texto'] }}</span>
                </div>
            @else
                <p class="fm-vazio">Você ainda não recebeu avaliações. Elas aparecem depois que um paciente avalia uma consulta realizada.</p>
            @endif
        </section>
    </div>

@endsection
