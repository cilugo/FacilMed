{{--
    Clínica → Tabela de preços. Dados: Clinica\PrecoController@index (README §7.2).

    Uma grade: linha = médico numa unidade, coluna = especialidade dele.
    Salva A GRADE INTEIRA de uma vez: precos[VINCULO_ID][ESPECIALIDADE_ID] = "250,00".
    Campo vazio = a especialidade deixa de ser oferecida naquele lugar.

    ⚠ O back-end tira os PONTOS do valor ("1.250,00" → 1250). Por isso o
    valor é sempre mostrado com vírgula: "250.00" viraria 25000.

    Depois de cadastrar médico novo, a clínica cai aqui com
    session('senha_temporaria'): mostrar em destaque, UMA vez.
--}}
@extends('layouts.painel')

@section('titulo', 'Tabela de preços')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    $reais = fn ($v) => number_format((float) $v, 2, ',', '.');
    $ativos = $vinculos->where('ativo', true)->sortBy(fn ($v) => $v->local->nome . '|' . $v->medico->user->name);
    $porUnidade = $ativos->groupBy(fn ($v) => $v->local->nome);
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Tabela de preços</h1>
            <p class="fm-subtitulo">Valor da consulta particular de cada médico, por unidade e especialidade.</p>
        </div>
    </div>

    @if (session('senha_temporaria'))
        <section class="fm-painel fm-painel--destaque" style="margin-top: 18px;" role="alert">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="shield" /> Senha provisória do médico</h2>
            </header>
            <p style="font-size: 14px;">Repasse esta senha ao médico. Ela <strong>não aparece de novo</strong> — anote agora.
                No primeiro acesso ele é obrigado a criar a senha dele.</p>
            <p class="fm-senha-provisoria">{{ session('senha_temporaria') }}</p>
        </section>
    @endif

    @if ($ativos->isEmpty())
        <section class="fm-painel" style="margin-top: 18px;">
            <p class="fm-vazio fm-vazio--acao">
                Nenhum médico vinculado ainda.
                <a href="{{ route('clinica.medicos.novo') }}" class="fm-pilula fm-pilula--pequena">Cadastrar médico <x-icone nome="chevron-right" /></a>
            </p>
        </section>
    @else
        <form method="POST" action="{{ route('clinica.precos.salvar') }}">
            @csrf

            @foreach ($porUnidade as $nomeUnidade => $lista)
                <section class="fm-painel" style="margin-top: 18px;">
                    <header class="fm-painel__topo">
                        <h2 class="fm-painel__titulo"><x-icone nome="building" /> {{ $nomeUnidade }}</h2>
                    </header>

                    <div class="fm-rolagem">
                        <table class="fm-tabela-crud">
                            <thead>
                                <tr><th>Médico</th><th>Especialidade</th><th>Valor da consulta</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($lista as $v)
                                    @foreach ($v->medico->especialidades as $i => $esp)
                                        @php
                                            $chave = "precos.{$v->id}.{$esp->id}";
                                            $preco = $v->precos->firstWhere('especialidade_id', $esp->id);
                                            $atual = $preco && $preco->ativo ? $reais($preco->valor) : '';
                                        @endphp
                                        <tr @if ($i === 0) id="vinculo-{{ $v->id }}" @endif class="{{ $atual === '' && ! old($chave) ? 'is-inativo' : '' }}">
                                            <td>
                                                @if ($i === 0)
                                                    <strong>{{ $v->medico->user->name }}</strong>
                                                @endif
                                            </td>
                                            <td>{{ $esp->nome }}</td>
                                            <td>
                                                <div class="fm-campo fm-campo--dinheiro {{ $errors->has($chave) ? 'fm-campo--erro' : '' }}">
                                                    <span>R$</span>
                                                    <input name="precos[{{ $v->id }}][{{ $esp->id }}]" inputmode="decimal" maxlength="10"
                                                           value="{{ old($chave, $atual) }}" placeholder="não oferece"
                                                           aria-label="Valor: {{ $v->medico->user->name }}, {{ $esp->nome }}, {{ $nomeUnidade }}">
                                                </div>
                                                @error($chave) <span class="fm-campo__erro">{{ $message }}</span> @enderror
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach

            <p class="fm-dica">
                <x-icone nome="lightbulb" />
                <span>Use vírgula para os centavos (ex.: <strong>250,00</strong>). Deixe em branco para <strong>não oferecer</strong> aquela
                    especialidade naquela unidade — ela some do agendamento ali. O paciente vê o valor antes de marcar.</span>
            </p>

            <div class="fm-form__acoes fm-barra-salvar">
                <button type="submit" class="fm-botao">Salvar tabela de preços</button>
            </div>
        </form>
    @endif

@endsection
