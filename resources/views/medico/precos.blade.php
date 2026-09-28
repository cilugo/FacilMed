{{--
    Médico → Preços. Dados: Medico\PrecoController@index (README §7.1).

    Preço é por LUGAR + ESPECIALIDADE. O médico só edita o do consultório
    próprio; em unidade de clínica quem define é a clínica (a Policy devolve
    403) — aqui o valor aparece só para leitura.

    ⚠ O back-end tira os PONTOS do valor e troca vírgula por ponto
    ("1.250,00" → 1250.00). Por isso o valor é sempre mostrado com vírgula:
    "250.00" viraria 25000.
--}}
@extends('layouts.painel')

@section('titulo', 'Preços')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    $reais = fn ($v) => number_format((float) $v, 2, ',', '.');
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Preços</h1>
            <p class="fm-subtitulo">Quanto custa a consulta particular em cada lugar. O paciente vê esse valor antes de marcar.</p>
        </div>
    </div>

    @if ($especialidades->isEmpty())
        <p class="fm-dica">
            <x-icone nome="lightbulb" />
            <span>Você ainda não tem especialidade cadastrada. Escolha em <a href="{{ route('medico.perfil') }}">Meu perfil</a>.</span>
        </p>
    @endif

    @forelse ($vinculos as $v)
        @php $proprio = $v->local->ehConsultorioProprio(); @endphp

        <section class="fm-painel" style="margin-top: 18px;">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo">
                    <x-icone :nome="$proprio ? 'home' : 'building'" /> {{ $v->local->nome }}
                </h2>
                @if ($proprio)
                    <span class="fm-etiqueta fm-etiqueta--roxo">Você define</span>
                @else
                    <span class="fm-etiqueta fm-etiqueta--cinza">Definido por {{ $v->local->clinica?->nome_fantasia ?? 'clínica' }}</span>
                @endif
            </header>

            @if ($especialidades->isEmpty())
                <p class="fm-vazio">Sem especialidades, não há preço para definir.</p>
            @else
                <div class="fm-rolagem">
                    <table class="fm-tabela-crud">
                        <thead>
                            <tr>
                                <th>Especialidade</th>
                                <th>Valor da consulta</th>
                                <th>Situação</th>
                                @if ($proprio) <th></th> @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($especialidades as $esp)
                                @php
                                    $preco = $v->precos->firstWhere('especialidade_id', $esp->id);
                                    $formId = "preco-{$v->id}-{$esp->id}";
                                    // Erro só na linha que foi enviada.
                                    $estaLinha = (int) old('vinculo_id') === $v->id && (int) old('especialidade_id') === $esp->id;
                                @endphp
                                <tr class="{{ $preco && $preco->ativo ? '' : 'is-inativo' }}">
                                    <td><strong>{{ $esp->nome }}</strong></td>

                                    @if ($proprio)
                                        <td>
                                            <div class="fm-campo fm-campo--dinheiro {{ $estaLinha && $errors->has('valor') ? 'fm-campo--erro' : '' }}">
                                                <span>R$</span>
                                                <input form="{{ $formId }}" name="valor" inputmode="decimal" maxlength="10" required
                                                       value="{{ $estaLinha ? old('valor') : ($preco ? $reais($preco->valor) : '') }}"
                                                       placeholder="0,00" aria-label="Valor de {{ $esp->nome }} em {{ $v->local->nome }}">
                                            </div>
                                            @if ($estaLinha)
                                                @error('valor') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                                                @error('especialidade_id') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                                            @endif
                                        </td>
                                        <td>
                                            <input form="{{ $formId }}" type="hidden" name="ativo" value="0">
                                            <label class="fm-marcar fm-marcar--linha">
                                                <input form="{{ $formId }}" type="checkbox" name="ativo" value="1" @checked(! $preco || $preco->ativo)>
                                                <span>Oferecer</span>
                                            </label>
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('medico.precos.salvar') }}" id="{{ $formId }}" class="fm-tabela-crud__acoes">
                                                @csrf
                                                <input type="hidden" name="vinculo_id" value="{{ $v->id }}">
                                                <input type="hidden" name="especialidade_id" value="{{ $esp->id }}">
                                                <button type="submit" class="fm-botao fm-botao--pequeno">Salvar</button>
                                            </form>
                                        </td>
                                    @else
                                        <td>{{ $preco ? 'R$ ' . $reais($preco->valor) : '—' }}</td>
                                        <td>
                                            @if ($preco && $preco->ativo)
                                                <span class="fm-etiqueta fm-etiqueta--verde">Oferecida</span>
                                            @else
                                                <span class="fm-etiqueta fm-etiqueta--cinza">Não oferecida aqui</span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($proprio)
                    <p class="fm-campo__ajuda" style="margin-top: 10px;">Use vírgula para os centavos (ex.: 250,00). Desmarcando "Oferecer",
                        a especialidade deixa de aparecer para agendamento neste lugar.</p>
                @endif
            @endif
        </section>
    @empty
        <section class="fm-painel" style="margin-top: 18px;">
            <p class="fm-vazio fm-vazio--acao">
                Você ainda não tem lugar de atendimento.
                <a href="{{ route('medico.locais') }}" class="fm-pilula fm-pilula--pequena">Cadastrar onde eu atendo <x-icone nome="chevron-right" /></a>
            </p>
        </section>
    @endforelse

@endsection
