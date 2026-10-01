{{--
    Clínica → Agenda da clínica. Dados: Clinica\AgendaController@index (README §7.2).

    Todas as unidades e todos os médicos num dia, com filtro por unidade,
    médico e especialidade.

    01/10/2026 (plano novo do grupo): a clínica marca realizada/falta (depois
    do horário) e cancela (antes, com motivo obrigatório, que vai no e-mail ao
    paciente). O médico só vê a agenda dele. A regra está na ConsultaPolicy;
    a view só esconde o botão que não cabe.

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
                    <li class="fm-consulta" x-data="{ cancelando: {{ $errors->has('motivo') && old('consulta_id') == $c->id ? 'true' : 'false' }} }">
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

                        {{-- Ações --}}
                        @if ($c->status === 'agendada')
                            <div class="fm-consulta__acoes">
                                @if (! $c->inicio->isFuture())
                                    <form method="POST" action="{{ route('clinica.agenda.realizada', $c) }}">
                                        @csrf
                                        <button type="submit" class="fm-botao fm-botao--ok fm-botao--pequeno">
                                            <x-icone nome="check-circle" /> Realizada
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('clinica.agenda.falta', $c) }}">
                                        @csrf
                                        <button type="submit" class="fm-botao fm-botao--suave fm-botao--pequeno">
                                            <x-icone nome="user-x" /> Não compareceu
                                        </button>
                                    </form>
                                @endif

                                @if ($c->podeSerCancelada())
                                    <button type="button" class="fm-botao fm-botao--perigo fm-botao--pequeno" @click="cancelando = !cancelando" :aria-expanded="cancelando">
                                        <x-icone nome="x-circle" /> Cancelar
                                    </button>
                                @endif
                            </div>

                            @if ($c->podeSerCancelada())
                                <form method="POST" action="{{ route('clinica.agenda.cancelar', $c) }}" class="fm-form fm-form--caixa" x-show="cancelando" x-cloak>
                                    @csrf
                                    <input type="hidden" name="consulta_id" value="{{ $c->id }}">
                                    <div class="fm-campo {{ $errors->has('motivo') && old('consulta_id') == $c->id ? 'fm-campo--erro' : '' }}">
                                        <label for="motivo-{{ $c->id }}">Motivo do cancelamento *</label>
                                        <input id="motivo-{{ $c->id }}" name="motivo" maxlength="255" required
                                               value="{{ old('consulta_id') == $c->id ? old('motivo') : '' }}"
                                               placeholder="Ex.: o médico teve um imprevisto">
                                        <span class="fm-campo__ajuda">O paciente recebe esse motivo por e-mail.</span>
                                        @if (old('consulta_id') == $c->id)
                                            @error('motivo') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                                        @endif
                                    </div>
                                    <div class="fm-form__acoes">
                                        <button type="button" class="fm-botao fm-botao--suave fm-botao--pequeno" @click="cancelando = false">Voltar</button>
                                        <button type="submit" class="fm-botao fm-botao--perigo fm-botao--pequeno">Confirmar cancelamento</button>
                                    </div>
                                </form>
                            @endif
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
        <span>Depois do horário, marque se a consulta foi realizada ou se o paciente faltou: só consulta realizada pode
            ser avaliada pelo paciente.</span>
    </p>

@endsection
