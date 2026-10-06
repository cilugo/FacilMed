{{--
    Usuário → Meus planos (carteirinhas).
    Dados de App\Http\Controllers\Usuario\PlanoController@index.

    Desde 24/09/2026 a carteirinha é CONFERIDA NA HORA na base simulada
    (base_carteirinhas). Por isso não existe mais "em conferência": ou
    entra ativa, ou volta com o motivo. O texto da tela diz "base
    simulada do PointMed" — nunca "validada pela operadora".
--}}
@extends('layouts.painel')

@section('titulo', 'Meus planos')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Meus planos</h1>
            <p class="fm-subtitulo">Cadastre a carteirinha do seu convênio para achar os locais que aceitam o seu plano.</p>
        </div>
    </div>

    <p class="fm-dica">
        <x-icone nome="lightbulb" />
        <span>
            A carteirinha é <strong>conferida na hora na base simulada do PointMed</strong>: o número precisa existir
            no plano escolhido, estar no seu CPF, ativo e dentro da validade. Os convênios são fictícios —
            o PointMed é um projeto acadêmico e não consulta nenhuma operadora de verdade.
        </span>
    </p>

    {{-- ============================ NOVA CARTEIRINHA ============================ --}}
    <section class="fm-painel" style="margin-top: 18px;">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="plus" /> Adicionar carteirinha</h2>
        </header>

        @if ($convenios->isEmpty())
            <p class="fm-vazio">Nenhum convênio disponível no momento.</p>
        @else
            <form method="POST" action="{{ route('usuario.planos.salvar') }}" class="fm-form fm-form--duas">
                @csrf

                <div class="fm-campo {{ $errors->has('plano_id') ? 'fm-campo--erro' : '' }}">
                    <label for="plano_id">Plano *</label>
                    <select id="plano_id" name="plano_id" required>
                        <option value="">Escolha o seu plano</option>
                        @foreach ($convenios as $convenio)
                            @if ($convenio->planos->isNotEmpty())
                                <optgroup label="{{ $convenio->nome }}">
                                    @foreach ($convenio->planos as $plano)
                                        <option value="{{ $plano->id }}" @selected((string) old('plano_id') === (string) $plano->id)>
                                            {{ $plano->nome }} ({{ $plano->tipo_rotulo }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                    @error('plano_id') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                </div>

                <div class="fm-campo {{ $errors->has('numero_carteirinha') ? 'fm-campo--erro' : '' }}">
                    <label for="numero_carteirinha">Número da carteirinha *</label>
                    <input id="numero_carteirinha" name="numero_carteirinha" inputmode="numeric" maxlength="25" required
                           value="{{ old('numero_carteirinha') }}" placeholder="Só os números">
                    @error('numero_carteirinha') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                </div>

                <div class="fm-form__acoes">
                    <button type="submit" class="fm-botao">Conferir e adicionar</button>
                </div>
            </form>
        @endif
    </section>

    {{-- ============================ LISTA ============================ --}}
    <section class="fm-painel">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="card" /> Minhas carteirinhas</h2>
        </header>

        @if ($planos->isNotEmpty())
            <div class="fm-rolagem">
                <table class="fm-tabela-crud">
                    <thead>
                        <tr>
                            <th>Plano</th>
                            <th>Número</th>
                            <th>Validade</th>
                            <th>Situação</th>
                            <th><span class="sr-only">Ações</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($planos as $pp)
                            @php
                                $utilizavel = $pp->status === 'ativa' && ! $pp->estaVencida()
                                    && $pp->plano?->ativo && $pp->plano?->convenio?->ativo;
                            @endphp
                            <tr class="{{ $utilizavel ? '' : 'is-inativo' }}">
                                <td>
                                    <strong>{{ $pp->plano?->nome }}</strong><br>
                                    <small>{{ $pp->plano?->convenio?->nome }}</small>
                                </td>
                                <td>{{ $pp->numero_carteirinha }}</td>
                                <td>{{ $pp->validade?->format('d/m/Y') ?? '—' }}</td>
                                <td>
                                    @if ($utilizavel)
                                        <span class="fm-etiqueta fm-etiqueta--verde" title="Conferida na base simulada em {{ $pp->conferido_em?->format('d/m/Y') }}">Ativa</span>
                                    @elseif ($pp->estaVencida())
                                        <span class="fm-etiqueta fm-etiqueta--ambar">Vencida</span>
                                    @elseif ($pp->status === 'ativa')
                                        <span class="fm-etiqueta fm-etiqueta--cinza">Plano indisponível</span>
                                    @else
                                        <span class="fm-etiqueta fm-etiqueta--cinza">{{ ucfirst($pp->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('usuario.planos.remover', $pp) }}"
                                          onsubmit="return confirm('Remover esta carteirinha?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="fm-botao fm-botao--perigo fm-botao--pequeno"
                                                aria-label="Remover carteirinha {{ $pp->plano?->nome }}">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="fm-vazio">Você ainda não cadastrou nenhuma carteirinha. Com ela, a busca já marca o seu convênio.</p>
        @endif
    </section>

@endsection
