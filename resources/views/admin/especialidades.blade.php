{{--
    Admin → Especialidades. Dados: Admin\EspecialidadeController@index (README §7.3).

    - Nova: nome, ícone, destaque (card na home).
    - Editar: nome, ícone, destaque e ativo. A URL usa o SLUG
      (/admin/especialidades/cardiologia), que não muda quando o nome muda.
    - 01/10/2026: a clínica também cria especialidade (Clinica\EspecialidadeController);
      renomear, destacar e desativar continuam só aqui.

    Caixas de marcar: um hidden "0" antes do checkbox "1", porque o controller
    só mexe em destaque/ativo quando o campo vem no formulário.
    Cada formulário manda "_form" para o erro voltar no lugar certo.
--}}
@extends('layouts.painel')

@section('titulo', 'Especialidades')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    // Os ícones que existem em resources/views/components/icone.blade.php.
    $icones = [
        'stethoscope' => 'Estetoscópio', 'heart' => 'Coração', 'baby' => 'Bebê', 'skin' => 'Pele',
        'female' => 'Feminino', 'bone' => 'Osso', 'brain' => 'Cérebro', 'eye' => 'Olho', 'mind' => 'Mente',
        'gland' => 'Glândula', 'ear' => 'Ouvido', 'kidney' => 'Rim', 'hospital' => 'Hospital',
    ];
    $formVolta = old('_form');
    $novaVolta = $formVolta === 'nova';
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Especialidades</h1>
            <p class="fm-subtitulo">As especialidades que médicos podem escolher e usuários podem buscar.</p>
        </div>
    </div>

    <p class="fm-dica">
        <x-icone nome="lightbulb" />
        <span><strong>Destaque</strong> faz a especialidade aparecer nos cards da página inicial na hora. Especialidade
            <strong>desativada</strong> some da busca e do cadastro dos médicos. As clínicas também podem criar especialidades.</span>
    </p>

    {{-- ===================== NOVA ===================== --}}
    <section class="fm-painel" style="margin-top: 18px;" x-data="{ aberto: {{ $novaVolta ? 'true' : 'false' }} }">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="plus" /> Nova especialidade</h2>
            <button type="button" class="fm-pilula fm-pilula--pequena" @click="aberto = !aberto" x-text="aberto ? 'Fechar' : 'Abrir'" :aria-expanded="aberto">Abrir</button>
        </header>

        <form method="POST" action="{{ route('admin.especialidades.salvar') }}" class="fm-form fm-form--tres" x-show="aberto" x-cloak>
            @csrf
            <input type="hidden" name="_form" value="nova">

            <div class="fm-campo {{ $novaVolta && $errors->has('nome') ? 'fm-campo--erro' : '' }}">
                <label for="nova-nome">Nome *</label>
                <input id="nova-nome" name="nome" maxlength="100" required value="{{ $novaVolta ? old('nome') : '' }}" placeholder="Ex.: Reumatologia">
                @if ($novaVolta) @error('nome') <span class="fm-campo__erro">{{ $message }}</span> @enderror @endif
            </div>

            <div class="fm-campo">
                <label for="nova-icone">Ícone</label>
                <select id="nova-icone" name="icone">
                    @foreach ($icones as $valor => $rotulo)
                        <option value="{{ $valor }}" @selected(($novaVolta ? old('icone') : 'stethoscope') === $valor)>{{ $rotulo }}</option>
                    @endforeach
                </select>
            </div>

            <input type="hidden" name="destaque" value="0">
            <label class="fm-marcar fm-marcar--linha" style="align-self: end; padding-bottom: 10px;">
                <input type="checkbox" name="destaque" value="1" @checked($novaVolta && old('destaque') === '1')>
                <span>Destaque na página inicial</span>
            </label>

            <div class="fm-form__acoes">
                <button type="submit" class="fm-botao">Criar especialidade</button>
            </div>
        </form>
    </section>

    {{-- ===================== LISTA ===================== --}}
    <section class="fm-painel">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="tag" /> Todas</h2>
            <span class="fm-painel__periodo">{{ $especialidades->where('ativo', true)->count() }} ativas de {{ $especialidades->count() }}</span>
        </header>

        @if ($especialidades->isNotEmpty())
            <ul class="fm-lista">
                @foreach ($especialidades as $esp)
                    @php $esteForm = $formVolta === 'editar-' . $esp->id; @endphp
                    <li class="fm-conta {{ $esp->ativo ? '' : 'is-inativo' }}" x-data="{ editando: {{ $esteForm ? 'true' : 'false' }} }">
                        <div class="fm-conta__linha">
                            <span class="fm-avatar fm-avatar--icone"><x-icone :nome="$esp->icone ?: 'stethoscope'" /></span>
                            <div class="fm-conta__info">
                                <strong>{{ $esp->nome }}</strong>
                                <span>/{{ $esp->slug }} · {{ $esp->medicos_count }} {{ $esp->medicos_count === 1 ? 'médico' : 'médicos' }}</span>
                            </div>
                            <div class="fm-chips fm-conta__etiquetas">
                                @if ($esp->destaque) <span class="fm-chip">Destaque</span> @endif
                                <span class="fm-etiqueta fm-etiqueta--{{ $esp->ativo ? 'verde' : 'cinza' }}">{{ $esp->ativo ? 'Ativa' : 'Desativada' }}</span>
                            </div>
                            <div class="fm-conta__acoes">
                                <button type="button" class="fm-pilula fm-pilula--pequena" @click="editando = !editando" :aria-expanded="editando"><x-icone nome="pencil" /> Editar</button>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.especialidades.atualizar', $esp) }}" class="fm-form fm-form--tres fm-form--caixa fm-conta__extra" x-show="editando" x-cloak>
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="_form" value="editar-{{ $esp->id }}">

                            <div class="fm-campo {{ $esteForm && $errors->has('nome') ? 'fm-campo--erro' : '' }}">
                                <label for="nome-{{ $esp->id }}">Nome *</label>
                                <input id="nome-{{ $esp->id }}" name="nome" maxlength="100" required value="{{ $esteForm ? old('nome') : $esp->nome }}">
                                <span class="fm-campo__ajuda">O endereço (/{{ $esp->slug }}) não muda.</span>
                                @if ($esteForm) @error('nome') <span class="fm-campo__erro">{{ $message }}</span> @enderror @endif
                            </div>

                            <div class="fm-campo">
                                <label for="icone-{{ $esp->id }}">Ícone</label>
                                <select id="icone-{{ $esp->id }}" name="icone">
                                    @foreach ($icones as $valor => $rotulo)
                                        <option value="{{ $valor }}" @selected(($esteForm ? old('icone') : $esp->icone) === $valor)>{{ $rotulo }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="fm-campo" style="gap: 10px; align-content: end;">
                                <input type="hidden" name="destaque" value="0">
                                <label class="fm-marcar fm-marcar--linha">
                                    <input type="checkbox" name="destaque" value="1" @checked($esp->destaque)>
                                    <span>Destaque na página inicial</span>
                                </label>
                                <input type="hidden" name="ativo" value="0">
                                <label class="fm-marcar fm-marcar--linha">
                                    <input type="checkbox" name="ativo" value="1" @checked($esp->ativo)>
                                    <span>Ativa</span>
                                </label>
                            </div>


                            <div class="fm-form__acoes">
                                <button type="button" class="fm-botao fm-botao--suave fm-botao--pequeno" @click="editando = false">Cancelar</button>
                                <button type="submit" class="fm-botao fm-botao--pequeno">Salvar</button>
                            </div>
                        </form>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="fm-vazio">Nenhuma especialidade cadastrada.</p>
        @endif
    </section>

@endsection
