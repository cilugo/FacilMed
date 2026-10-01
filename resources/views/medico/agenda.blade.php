{{--
    Médico → Minha agenda. Dados: Medico\AgendaController@index (README §7.1).

    Um dia por vez (‹ ›), com filtro por lugar. Cada consulta mostra o que o
    médico precisa para atender — inclusive a ACESSIBILIDADE do paciente, que
    aqui pode aparecer porque é o médico daquela consulta (AGENTS.md §3), e só
    enquanto ela está agendada (ConsultaPolicy::verAcessibilidade).

    01/10/2026 (plano novo do grupo): SÓ LEITURA. Quem marca realizada/falta e
    cancela é a clínica, na agenda dela.
--}}
@extends('layouts.painel')

@section('titulo', 'Minha agenda')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    use App\Support\Formatador;
    $vinculoAtual = request()->integer('vinculo') ?: null;
    // Mantém o filtro de lugar quando troca de dia.
    $urlDia = fn (string $dia) => route('medico.agenda', array_filter(['data' => $dia, 'vinculo' => $vinculoAtual]));
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Minha agenda</h1>
            <p class="fm-subtitulo">{{ Formatador::dataExtensa($data) }}</p>
        </div>

        <div class="fm-acoes-topo">
            <a href="{{ $urlDia($anterior) }}" class="fm-botao fm-botao--suave fm-botao--pequeno" aria-label="Dia anterior">
                <x-icone nome="chevron-left" /> Anterior
            </a>
            @unless ($data->isToday())
                <a href="{{ $urlDia(today()->toDateString()) }}" class="fm-botao fm-botao--suave fm-botao--pequeno">Hoje</a>
            @endunless
            <a href="{{ $urlDia($seguinte) }}" class="fm-botao fm-botao--suave fm-botao--pequeno" aria-label="Dia seguinte">
                Seguinte <x-icone nome="chevron-right" />
            </a>
        </div>
    </div>

    {{-- Filtros: dia e lugar --}}
    <form method="GET" action="{{ route('medico.agenda') }}" class="fm-filtros">
        <div class="fm-campo">
            <label for="data">Dia</label>
            <input id="data" name="data" type="date" value="{{ $data->toDateString() }}">
        </div>
        <div class="fm-campo">
            <label for="vinculo">Lugar</label>
            <select id="vinculo" name="vinculo">
                <option value="">Todos os lugares</option>
                @foreach ($vinculos as $v)
                    <option value="{{ $v->id }}" @selected($vinculoAtual === $v->id)>{{ $v->local->nome }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="fm-botao fm-botao--pequeno">Ver</button>
    </form>

    {{-- Resumo do dia --}}
    <div class="fm-resumo-dia">
        <span class="fm-etiqueta fm-etiqueta--azul">{{ $resumo['agendadas'] }} {{ $resumo['agendadas'] === 1 ? 'agendada' : 'agendadas' }}</span>
        <span class="fm-etiqueta fm-etiqueta--verde">{{ $resumo['realizadas'] }} {{ $resumo['realizadas'] === 1 ? 'realizada' : 'realizadas' }}</span>
        <span class="fm-etiqueta fm-etiqueta--rosa">{{ $resumo['canceladas'] }} {{ $resumo['canceladas'] === 1 ? 'cancelada' : 'canceladas' }}</span>
        <span class="fm-etiqueta fm-etiqueta--ambar">{{ $resumo['faltas'] }} {{ $resumo['faltas'] === 1 ? 'falta' : 'faltas' }}</span>
    </div>

    <section class="fm-painel">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="calendar" /> Consultas do dia</h2>
        </header>

        @if ($consultas->isNotEmpty())
            <ul class="fm-lista">
                @foreach ($consultas as $c)
                    @php $st = Formatador::status($c->status); @endphp
                    <li class="fm-consulta">
                        <div class="fm-consulta__linha">
                            <span class="fm-agenda__hora">{{ Formatador::hora($c->horario) }}</span>

                            <div class="fm-consulta__info">
                                <strong>{{ $c->paciente->user->name }}</strong>
                                <span>
                                    {{ $c->especialidade->nome }} · {{ $c->vinculo->local->nome }} ·
                                    @if ($c->forma_pagamento === 'convenio')
                                        {{ $c->pacientePlano?->plano->convenio->nome ?? 'Convênio' }}
                                    @else
                                        Particular
                                    @endif
                                </span>
                            </div>

                            <span class="fm-etiqueta fm-etiqueta--{{ $st['tom'] }}">{{ $st['rotulo'] }}</span>
                        </div>

                        {{-- Dado sensível (LGPD art. 11): só nesta consulta e só enquanto
                             ela está agendada. A regra está na ConsultaPolicy. --}}
                        @can('verAcessibilidade', $c)
                            @if ($c->paciente->acessibilidade?->descricao)
                                <p class="fm-aviso fm-consulta__extra">
                                    <strong>Acessibilidade:</strong> {{ $c->paciente->acessibilidade->descricao }}
                                </p>
                            @endif
                        @endcan

                        @if ($c->observacoes)
                            <p class="fm-consulta__extra fm-campo__ajuda"><strong>Observações do paciente:</strong> {{ $c->observacoes }}</p>
                        @endif

                        @if ($c->status === 'cancelada' && $c->motivo_cancelamento)
                            <p class="fm-consulta__extra fm-campo__ajuda">Motivo do cancelamento: {{ $c->motivo_cancelamento }}</p>
                        @endif

                    </li>
                @endforeach
            </ul>
        @else
            <p class="fm-vazio">Nenhuma consulta neste dia{{ $vinculoAtual ? ' neste lugar' : '' }}.</p>
        @endif
    </section>

    <p class="fm-dica">
        <x-icone nome="lightbulb" />
        <span>Seus horários, ausências e as consultas (realizada, falta ou cancelada) são cuidados pela clínica.
            Precisa mudar algo? Fale com a recepção.</span>
    </p>

@endsection
