{{--
    Clínica → Agenda da clínica. Dados: Clinica\AgendaController@index (README §7.2).

    Todas as unidades e todos os médicos num dia, com filtro por unidade,
    médico e especialidade. É SÓ LEITURA: quem marca realizada/falta ou
    cancela é o médico, na agenda dele (não existe rota da clínica para isso).

    Acessibilidade do paciente NÃO aparece (28/09, 3ª revisão): só o médico
    da consulta lê, e só enquanto ela está agendada — AGENTS.md §3 e o que o
    paciente autorizou no cadastro. Observações do paciente aparecem, para a
    recepção.
--}}
@extends('layouts.painel')

@section('titulo', 'Agenda da clínica')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    use App\Support\Formatador;
    $f = fn (string $k) => (int) ($filtros[$k] ?? 0) ?: null;
    $urlDia = fn (string $dia) => route('clinica.agenda', array_filter(['data' => $dia] + array_map('intval', $filtros)));
    $filtrando = array_filter(array_map('intval', $filtros)) !== [];
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Agenda da clínica</h1>
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

    <form method="GET" action="{{ route('clinica.agenda') }}" class="fm-filtros">
        <div class="fm-campo">
            <label for="data">Dia</label>
            <input id="data" name="data" type="date" value="{{ $data->toDateString() }}">
        </div>
        <div class="fm-campo">
            <label for="local">Unidade</label>
            <select id="local" name="local">
                <option value="">Todas</option>
                @foreach ($unidades as $u)
                    <option value="{{ $u->id }}" @selected($f('local') === $u->id)>{{ $u->nome }}</option>
                @endforeach
            </select>
        </div>
        <div class="fm-campo">
            <label for="medico">Médico</label>
            <select id="medico" name="medico">
                <option value="">Todos</option>
                @foreach ($medicos as $m)
                    <option value="{{ $m->id }}" @selected($f('medico') === $m->id)>{{ $m->user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="fm-campo">
            <label for="especialidade">Especialidade</label>
            <select id="especialidade" name="especialidade">
                <option value="">Todas</option>
                @foreach ($especialidades as $e)
                    <option value="{{ $e->id }}" @selected($f('especialidade') === $e->id)>{{ $e->nome }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="fm-botao fm-botao--pequeno">Filtrar</button>
        @if ($filtrando)
            <a href="{{ route('clinica.agenda', ['data' => $data->toDateString()]) }}" class="fm-botao fm-botao--suave fm-botao--pequeno">Limpar</a>
        @endif
    </form>

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
                                    {{ $c->medico->user->name }} · {{ $c->especialidade->nome }} · {{ $c->vinculo->local->nome }} ·
                                    @if ($c->forma_pagamento === 'convenio')
                                        {{ $c->pacientePlano?->plano->convenio->nome ?? 'Convênio' }}
                                    @else
                                        Particular
                                    @endif
                                </span>
                            </div>
                            <span class="fm-etiqueta fm-etiqueta--{{ $st['tom'] }}">{{ $st['rotulo'] }}</span>
                        </div>

                        {{-- Sem acessibilidade aqui (28/09, 3ª revisão): quem lê é só o médico
                             da consulta (ConsultaPolicy::verAcessibilidade, AGENTS.md §3). --}}
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
            <p class="fm-vazio">Nenhuma consulta neste dia{{ $filtrando ? ' com esses filtros' : '' }}.</p>
        @endif
    </section>

    <p class="fm-dica">
        <x-icone nome="lightbulb" />
        <span>Marcar consulta como realizada, falta ou cancelar é feito pelo próprio médico, na agenda dele.</span>
    </p>

@endsection
