{{--
    Admin → Consultas. Dados: Admin\ConsultaController@index (README §7.3).

    Visão do AGENDAMENTO: quem, quando, onde, quanto e a situação.
    NÃO mostrar observações do paciente nem acessibilidade aqui — o admin vê
    o agendamento, não a vida do paciente (AGENTS.md §3).
--}}
@extends('layouts.painel')

@section('titulo', 'Consultas')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    use App\Support\Formatador;
    $statusLista = ['agendada', 'realizada', 'cancelada', 'nao_compareceu'];
    $totalFiltrado = array_sum($porStatus->all());
    $filtrando = array_filter($filtros) !== [];
    $cartoes = [
        ['icone' => 'calendar',       'tom' => 'azul',  'rotulo' => 'Total',           'valor' => Formatador::numero($totalFiltrado)],
        ['icone' => 'calendar-check', 'tom' => 'verde', 'rotulo' => 'Realizadas',      'valor' => Formatador::numero($porStatus['realizada'] ?? 0)],
        ['icone' => 'x-circle',       'tom' => 'rosa',  'rotulo' => 'Canceladas',      'valor' => Formatador::numero($porStatus['cancelada'] ?? 0)],
        ['icone' => 'user-x',         'tom' => 'ambar', 'rotulo' => 'Não compareceram', 'valor' => Formatador::numero($porStatus['nao_compareceu'] ?? 0)],
    ];
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Consultas</h1>
            <p class="fm-subtitulo">Todas as consultas da plataforma{{ $filtrando ? ', com os filtros abaixo' : '' }}.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.consultas') }}" class="fm-filtros">
        <div class="fm-campo">
            <label for="status">Situação</label>
            <select id="status" name="status">
                <option value="">Todas</option>
                @foreach ($statusLista as $s)
                    <option value="{{ $s }}" @selected(($filtros['status'] ?? '') === $s)>{{ Formatador::status($s)['rotulo'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="fm-campo">
            <label for="medico">Médico</label>
            <select id="medico" name="medico">
                <option value="">Todos</option>
                @foreach ($medicos as $m)
                    <option value="{{ $m->id }}" @selected((int) ($filtros['medico'] ?? 0) === $m->id)>{{ $m->user?->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="fm-campo">
            <label for="de">De</label>
            <input id="de" name="de" type="date" value="{{ $filtros['de'] ?? '' }}">
            @error('de') <span class="fm-campo__erro">{{ $message }}</span> @enderror
        </div>
        <div class="fm-campo">
            <label for="ate">Até</label>
            <input id="ate" name="ate" type="date" value="{{ $filtros['ate'] ?? '' }}">
            @error('ate') <span class="fm-campo__erro">{{ $message }}</span> @enderror
        </div>
        <button type="submit" class="fm-botao fm-botao--pequeno">Filtrar</button>
        @if ($filtrando)
            <a href="{{ route('admin.consultas') }}" class="fm-botao fm-botao--suave fm-botao--pequeno">Limpar</a>
        @endif
    </form>

    <div class="fm-grade fm-grade--cartoes">
        @foreach ($cartoes as $c)
            @include('painel.parciais.cartao', ['c' => $c])
        @endforeach
    </div>

    <section class="fm-painel" style="margin-top: 18px;">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="list" /> Lista</h2>
            @if (($porStatus['agendada'] ?? 0) > 0)
                <span class="fm-painel__periodo">{{ Formatador::numero($porStatus['agendada']) }} ainda agendadas</span>
            @endif
        </header>

        @if ($consultas->isNotEmpty())
            <div class="fm-rolagem">
                <table class="fm-tabela-crud">
                    <thead>
                        <tr><th>Quando</th><th>Paciente</th><th>Médico</th><th>Especialidade</th><th>Onde</th><th>Valor</th><th>Situação</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($consultas as $c)
                            @php $st = Formatador::status($c->status); @endphp
                            <tr>
                                <td><strong>{{ Formatador::dataCurta($c->data_consulta) }}</strong><br><span class="fm-campo__ajuda">{{ Formatador::hora($c->horario) }}</span></td>
                                <td>{{ $c->paciente?->user?->name }}</td>
                                <td>{{ $c->medico?->user?->name }}</td>
                                <td>{{ $c->especialidade?->nome }}</td>
                                <td>{{ $c->vinculo?->local?->nome }}</td>
                                <td>
                                    @if ($c->forma_pagamento === 'convenio')
                                        Convênio
                                    @else
                                        R$ {{ number_format((float) $c->valor, 2, ',', '.') }}
                                    @endif
                                </td>
                                <td>
                                    <span class="fm-etiqueta fm-etiqueta--{{ $st['tom'] }}">{{ $st['rotulo'] }}</span>
                                    @if ($c->status === 'cancelada' && $c->cancelamento_tardio)
                                        <br><span class="fm-campo__ajuda">cancelamento tardio</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $consultas->links('painel.parciais.paginacao') }}
        @else
            <p class="fm-vazio">Nenhuma consulta{{ $filtrando ? ' com esses filtros' : '' }}.</p>
        @endif
    </section>

@endsection
