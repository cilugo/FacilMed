{{--
    Admin → Convênios e planos (CRUD completo).
    Dados de App\Http\Controllers\Admin\ConvenioController@index.

    CONVÊNIOS FICTÍCIOS (decisão do grupo, 24/09/2026). O aviso no topo
    é obrigatório: sem ele, parece que a plataforma tem contrato com
    operadoras de verdade.

    Cada formulário tem seu próprio "error bag" (novoConvenio, convenio_3,
    novoPlano_3, plano_7). O $valor() abaixo só devolve o old() quando o
    erro é DAQUELE formulário — senão, um erro no convênio A preencheria
    todos os outros formulários da página com o texto digitado em A.

    Nada é apagado nesta tela, só desativado (ver o controller).
--}}
@extends('layouts.painel')

@section('titulo', 'Convênios e planos')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    $valor = fn (string $bag, string $campo, $padrao = '') =>
        $errors->getBag($bag)->any() ? old($campo, $padrao) : $padrao;

    $erro = fn (string $bag, string $campo) => $errors->getBag($bag)->first($campo);
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Convênios e planos</h1>
            <p class="fm-subtitulo">Cadastre os convênios aceitos na plataforma e os planos de cada um.</p>
        </div>

        <div class="fm-acoes-topo" x-data>
            <button type="button" class="fm-botao" @click="$dispatch('abrir-novo-convenio')">
                <x-icone nome="plus" />
                Novo convênio
            </button>
        </div>
    </div>

    <p class="fm-dica">
        <x-icone nome="lightbulb" />
        <span>
            <strong>Todos os convênios e planos são fictícios</strong>, criados para a demonstração do FacilMed.
            A plataforma não tem contrato com nenhuma operadora real. Convênio ou plano desativado some do
            agendamento, mas continua aqui — carteirinhas e histórico de consultas são preservados.
        </span>
    </p>

    <div class="fm-grade fm-grade--cartoes fm-grade--tres">
        @include('painel.parciais.cartao', ['c' => ['icone' => 'shield', 'tom' => 'azul',  'rotulo' => 'Convênios ativos', 'valor' => $totais['convenios']]])
        @include('painel.parciais.cartao', ['c' => ['icone' => 'card',   'tom' => 'verde', 'rotulo' => 'Planos disponíveis', 'valor' => $totais['planos'], 'nota' => 'em convênios ativos']])
        @include('painel.parciais.cartao', ['c' => ['icone' => 'pause',  'tom' => 'ambar', 'rotulo' => 'Convênios desativados', 'valor' => $totais['inativos']]])
    </div>

    {{-- ============================ NOVO CONVÊNIO ============================ --}}
    <section
        class="fm-painel"
        x-data="{ aberto: {{ $errors->novoConvenio->any() ? 'true' : 'false' }} }"
        x-show="aberto"
        x-cloak
        @abrir-novo-convenio.window="aberto = true; $nextTick(() => $refs.nome.focus())"
    >
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="plus" /> Novo convênio</h2>
        </header>

        <form method="POST" action="{{ route('admin.convenios.salvar') }}" class="fm-form fm-form--duas">
            @csrf
            @include('admin.parciais.campos-convenio', ['bag' => 'novoConvenio', 'c' => null, 'refNome' => true])

            <div class="fm-form__acoes">
                <button type="button" class="fm-botao fm-botao--suave" @click="aberto = false">Cancelar</button>
                <button type="submit" class="fm-botao">Salvar convênio</button>
            </div>
        </form>
    </section>

    {{-- ============================ FILTRO ============================ --}}
    <nav class="fm-abas" aria-label="Filtrar convênios" style="margin-bottom: 16px;">
        @foreach (['todos' => 'Todos', 'ativos' => 'Ativos', 'inativos' => 'Desativados'] as $chave => $rotulo)
            <a
                href="{{ route('admin.convenios', $chave === 'todos' ? [] : ['status' => $chave]) }}"
                class="fm-aba {{ $filtro === $chave ? 'is-ativa' : '' }}"
                @if ($filtro === $chave) aria-current="true" @endif
            >{{ $rotulo }}</a>
        @endforeach
    </nav>

    {{-- ============================ LISTA ============================ --}}
    @forelse ($convenios as $c)
        @php $bagConvenio = 'convenio_' . $c->id; $bagNovoPlano = 'novoPlano_' . $c->id; @endphp

        <section
            class="fm-painel"
            id="convenio-{{ $c->id }}"
            x-data="{
                editando: {{ $errors->getBag($bagConvenio)->any() ? 'true' : 'false' }},
                novoPlano: {{ $errors->getBag($bagNovoPlano)->any() ? 'true' : 'false' }}
            }"
        >
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo">
                    <x-icone nome="shield" />
                    {{ $c->nome }}
                    @if ($c->ativo)
                        <span class="fm-etiqueta fm-etiqueta--verde">Ativo</span>
                    @else
                        <span class="fm-etiqueta fm-etiqueta--cinza">Desativado</span>
                    @endif
                </h2>

                <div class="fm-tabela-crud__acoes">
                    <button type="button" class="fm-botao fm-botao--suave fm-botao--pequeno" @click="editando = !editando" :aria-expanded="editando">
                        <x-icone nome="pencil" /> Editar
                    </button>

                    <form method="POST" action="{{ route('admin.convenios.status', $c) }}"
                          onsubmit="return confirm('{{ $c->ativo ? 'Desativar' : 'Reativar' }} o convênio {{ addslashes($c->nome) }}?')">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="fm-botao fm-botao--pequeno {{ $c->ativo ? 'fm-botao--perigo' : 'fm-botao--ok' }}">
                            <x-icone nome="power" /> {{ $c->ativo ? 'Desativar' : 'Reativar' }}
                        </button>
                    </form>
                </div>
            </header>

            <p class="fm-meta">
                <span>CNPJ: {{ $c->cnpj ? \App\Support\Documento::cnpj($c->cnpj) : '—' }}</span>
                <span>Telefone: {{ $c->telefone ?: '—' }}</span>
                <span>E-mail: {{ $c->email ?: '—' }}</span>
                <span>{{ $c->medicos_count }} {{ (int) $c->medicos_count === 1 ? 'médico aceita' : 'médicos aceitam' }}</span>
            </p>

            @if ($c->descricao)
                <p class="fm-meta" style="margin-top: -8px;">{{ $c->descricao }}</p>
            @endif

            {{-- Editar convênio --}}
            <form method="POST" action="{{ route('admin.convenios.atualizar', $c) }}"
                  class="fm-form fm-form--duas fm-form--caixa" x-show="editando" x-cloak>
                @csrf
                @method('PUT')
                @include('admin.parciais.campos-convenio', ['bag' => $bagConvenio, 'c' => $c, 'refNome' => false])

                <div class="fm-form__acoes">
                    <button type="button" class="fm-botao fm-botao--suave" @click="editando = false">Cancelar</button>
                    <button type="submit" class="fm-botao">Salvar alterações</button>
                </div>
            </form>

            {{-- Planos --}}
            <div class="fm-painel__topo" style="margin-top: 18px;">
                <h3 class="fm-cobertura__rotulo">Planos ({{ $c->planos->count() }})</h3>
                <button type="button" class="fm-pilula fm-pilula--pequena" @click="novoPlano = !novoPlano" :aria-expanded="novoPlano">
                    <x-icone nome="plus" /> Novo plano
                </button>
            </div>

            @if ($c->planos->isNotEmpty())
                <div class="fm-rolagem">
                    <table class="fm-tabela-crud">
                        <thead>
                            <tr>
                                <th>Plano</th>
                                <th>Tipo</th>
                                <th>Abrangência</th>
                                <th>Carteirinhas</th>
                                <th>Status</th>
                                <th><span class="sr-only">Ações</span></th>
                            </tr>
                        </thead>
                        {{-- Um <tbody> por plano (HTML permite vários): cada um guarda
                             o estado "editando" daquele plano no Alpine. --}}
                        @foreach ($c->planos as $p)
                                @php $bagPlano = 'plano_' . $p->id; @endphp
                                <tbody x-data="{ editandoPlano: {{ $errors->getBag($bagPlano)->any() ? 'true' : 'false' }} }">
                                    <tr class="{{ $p->ativo ? '' : 'is-inativo' }}">
                                        <td>
                                            <strong>{{ $p->nome }}</strong>
                                            @if ($p->descricao)
                                                <br><small>{{ $p->descricao }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $p->tipo_rotulo }}</td>
                                        <td>{{ $p->abrangencia_rotulo }}</td>
                                        <td>{{ $p->paciente_planos_count }}</td>
                                        <td>
                                            @if ($p->ativo && $c->ativo)
                                                <span class="fm-etiqueta fm-etiqueta--verde">Ativo</span>
                                            @elseif ($p->ativo)
                                                <span class="fm-etiqueta fm-etiqueta--ambar" title="O plano está ativo, mas o convênio está desativado">Convênio desativado</span>
                                            @else
                                                <span class="fm-etiqueta fm-etiqueta--cinza">Desativado</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fm-tabela-crud__acoes">
                                                <button type="button" class="fm-botao fm-botao--suave fm-botao--pequeno"
                                                        @click="editandoPlano = !editandoPlano" :aria-expanded="editandoPlano"
                                                        aria-label="Editar {{ $p->nome }}">
                                                    <x-icone nome="pencil" />
                                                </button>
                                                <form method="POST" action="{{ route('admin.convenios.planos.status', $p) }}"
                                                      onsubmit="return confirm('{{ $p->ativo ? 'Desativar' : 'Reativar' }} o plano {{ addslashes($p->nome) }}?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                            class="fm-botao fm-botao--pequeno {{ $p->ativo ? 'fm-botao--perigo' : 'fm-botao--ok' }}"
                                                            aria-label="{{ $p->ativo ? 'Desativar' : 'Reativar' }} {{ $p->nome }}">
                                                        <x-icone nome="power" />
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr x-show="editandoPlano" x-cloak>
                                        <td colspan="6" class="fm-tabela-crud__form">
                                            <form method="POST" action="{{ route('admin.convenios.planos.atualizar', $p) }}" class="fm-form fm-form--tres">
                                                @csrf
                                                @method('PUT')
                                                @include('admin.parciais.campos-plano', ['bag' => $bagPlano, 'p' => $p])
                                                <div class="fm-form__acoes">
                                                    <button type="button" class="fm-botao fm-botao--suave" @click="editandoPlano = false">Cancelar</button>
                                                    <button type="submit" class="fm-botao">Salvar plano</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                </tbody>
                        @endforeach
                    </table>
                </div>
            @else
                <p class="fm-vazio">Nenhum plano cadastrado. Sem plano, ninguém consegue cadastrar carteirinha deste convênio.</p>
            @endif

            {{-- Novo plano --}}
            <form method="POST" action="{{ route('admin.convenios.planos', $c) }}"
                  class="fm-form fm-form--tres fm-form--caixa" x-show="novoPlano" x-cloak>
                @csrf
                @include('admin.parciais.campos-plano', ['bag' => $bagNovoPlano, 'p' => null])
                <div class="fm-form__acoes">
                    <button type="button" class="fm-botao fm-botao--suave" @click="novoPlano = false">Cancelar</button>
                    <button type="submit" class="fm-botao">Adicionar plano</button>
                </div>
            </form>
        </section>
    @empty
        <section class="fm-painel">
            <div class="fm-vazio fm-vazio--acao" x-data>
                <p>Nenhum convênio {{ $filtro === 'inativos' ? 'desativado' : 'cadastrado' }}.</p>
                @if ($filtro !== 'inativos')
                    <button type="button" class="fm-botao" @click="$dispatch('abrir-novo-convenio')">
                        <x-icone nome="plus" /> Cadastrar o primeiro convênio
                    </button>
                @endif
            </div>
        </section>
    @endforelse

@endsection
