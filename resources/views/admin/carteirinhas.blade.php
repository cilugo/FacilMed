{{--
    Admin → Conferir carteirinhas. Dados: Admin\CarteirinhaController@index (README §7.3).

    Desde 24/09 a carteirinha é conferida NA HORA na base simulada (bateu →
    'ativa'; não bateu → nem é gravada). A fila de pendentes costuma ficar
    vazia; a tela vira consulta das carteirinhas, com filtro por situação.
    Aprovar/recusar só aparece para alguma que tenha ficado pendente.
--}}
@extends('layouts.painel')

@section('titulo', 'Conferir carteirinhas')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    use App\Support\Formatador;
    $formVolta = old('_form');
    $situacoes = ['ativa' => 'Ativas', 'pendente' => 'Em conferência', 'recusada' => 'Recusadas'];
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Conferir carteirinhas</h1>
            <p class="fm-subtitulo">Carteirinhas de plano cadastradas pelos pacientes.</p>
        </div>
    </div>

    <p class="fm-dica">
        <x-icone nome="lightbulb" />
        <span>A carteirinha é <strong>conferida na hora na base simulada do FacilMed</strong> (número, plano, CPF do titular,
            situação e validade). Os convênios são fictícios: a plataforma não consulta nenhuma operadora de verdade.</span>
    </p>

    @if ($pendentes->isNotEmpty())
        <section class="fm-painel" style="margin-top: 18px;">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="clock" /> Aguardando conferência</h2>
                <span class="fm-etiqueta fm-etiqueta--ambar">{{ $pendentes->count() }}</span>
            </header>

            @foreach ($pendentes as $pp)
                @php $esteForm = $formVolta === 'recusar-' . $pp->id; @endphp
                <div class="fm-conta" x-data="{ recusando: {{ $esteForm ? 'true' : 'false' }} }">
                    <div class="fm-conta__linha">
                        <span class="fm-avatar">{{ Formatador::iniciais($pp->paciente->user->name) }}</span>
                        <div class="fm-conta__info">
                            <strong>{{ $pp->paciente->user->name }}</strong>
                            <span>{{ $pp->plano->convenio->nome }} — {{ $pp->plano->nome }} · Nº {{ $pp->numero_carteirinha }}</span>
                        </div>
                        <div class="fm-conta__acoes">
                            <form method="POST" action="{{ route('admin.carteirinhas.aprovar', $pp) }}">
                                @csrf
                                <button type="submit" class="fm-botao fm-botao--ok fm-botao--pequeno">Aprovar</button>
                            </form>
                            <button type="button" class="fm-botao fm-botao--perigo fm-botao--pequeno" @click="recusando = !recusando">Recusar</button>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.carteirinhas.recusar', $pp) }}" class="fm-form fm-form--caixa fm-conta__extra" x-show="recusando" x-cloak>
                        @csrf
                        <input type="hidden" name="_form" value="recusar-{{ $pp->id }}">
                        <div class="fm-campo {{ $esteForm && $errors->has('motivo') ? 'fm-campo--erro' : '' }}">
                            <label for="motivo-{{ $pp->id }}">Motivo da recusa *</label>
                            <input id="motivo-{{ $pp->id }}" name="motivo" minlength="10" maxlength="255" required value="{{ $esteForm ? old('motivo') : '' }}">
                            @if ($esteForm) @error('motivo') <span class="fm-campo__erro">{{ $message }}</span> @enderror @endif
                        </div>
                        <div class="fm-form__acoes">
                            <button type="button" class="fm-botao fm-botao--suave fm-botao--pequeno" @click="recusando = false">Voltar</button>
                            <button type="submit" class="fm-botao fm-botao--perigo fm-botao--pequeno">Confirmar recusa</button>
                        </div>
                    </form>
                </div>
            @endforeach
        </section>
    @endif

    <nav class="fm-abas" aria-label="Filtrar por situação" style="margin-top: 18px; flex-wrap: wrap;">
        <a href="{{ route('admin.carteirinhas') }}" class="fm-aba {{ request('status') ? '' : 'is-ativa' }}">Todas</a>
        @foreach ($situacoes as $valor => $rotulo)
            <a href="{{ route('admin.carteirinhas', ['status' => $valor]) }}" class="fm-aba {{ request('status') === $valor ? 'is-ativa' : '' }}">{{ $rotulo }}</a>
        @endforeach
    </nav>

    <section class="fm-painel" style="margin-top: 14px;">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="card" /> Carteirinhas</h2>
            <span class="fm-painel__periodo">{{ $recentes->total() }} no total</span>
        </header>

        @if ($recentes->isNotEmpty())
            <div class="fm-rolagem">
                <table class="fm-tabela-crud">
                    <thead>
                        <tr><th>Paciente</th><th>Convênio / plano</th><th>Número</th><th>Validade</th><th>Situação</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($recentes as $pp)
                            @php $st = Formatador::statusCarteirinha($pp->status); @endphp
                            <tr>
                                <td><strong>{{ $pp->paciente->user->name }}</strong></td>
                                <td>{{ $pp->plano->convenio->nome }}<br><span class="fm-campo__ajuda">{{ $pp->plano->nome }}</span></td>
                                <td>{{ $pp->numero_carteirinha }}</td>
                                <td>{{ $pp->validade ? Formatador::dataCurta($pp->validade) : '—' }}</td>
                                <td>
                                    <span class="fm-etiqueta fm-etiqueta--{{ $st['tom'] }}">{{ $st['rotulo'] }}</span>
                                    @if ($pp->status === 'recusada' && $pp->motivo_recusa)
                                        <br><span class="fm-campo__ajuda">{{ $pp->motivo_recusa }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $recentes->links('painel.parciais.paginacao') }}
        @else
            <p class="fm-vazio">Nenhuma carteirinha{{ request('status') ? ' nessa situação' : '' }}.</p>
        @endif
    </section>

@endsection
