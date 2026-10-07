{{--
    Clínica → Perfil da clínica. Dados: Clinica\PerfilController@edit (README §7.2).

    CNPJ e razão social são SÓ LEITURA: foram conferidos na base simulada no
    cadastro. Muda o resto (responsável, nome fantasia, descrição, telefone).
    Senha: mesmo bloco do perfil do usuário (rota password.update).
    01/10/2026: foto de perfil (aparece na página pública da clínica).
--}}
@extends('layouts.painel')

@section('titulo', 'Perfil da clínica')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    use App\Support\Documento;
    use App\Support\Formatador;
    $user = $clinica->user;
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Perfil da clínica</h1>
            <p class="fm-subtitulo">Como a clínica aparece para os usuários.</p>
        </div>
        <a href="{{ route('publico.clinica', $clinica) }}" class="fm-pilula" target="_blank" rel="noopener">
            Ver perfil público <x-icone nome="chevron-right" />
        </a>
    </div>

    @if (session('status') === 'password-updated')
        <div class="fm-flash fm-flash--ok" role="status">Senha trocada.</div>
    @endif

    <div style="margin-top: 18px;">
        @include('painel.parciais.foto')
    </div>

    <section class="fm-painel" style="margin-top: 18px;">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="building" /> Dados da clínica</h2>
            <span class="fm-etiqueta fm-etiqueta--verde" title="Conferido na base simulada do PointMed">CNPJ conferido</span>
        </header>

        <form method="POST" action="{{ route('clinica.perfil.atualizar') }}" class="fm-form fm-form--duas">
            @csrf
            @method('PUT')

            <div class="fm-campo">
                <label for="cnpj">CNPJ</label>
                <input id="cnpj" value="{{ Documento::cnpj($clinica->cnpj) }}" disabled>
            </div>

            <div class="fm-campo">
                <label for="razao_social">Razão social</label>
                <input id="razao_social" value="{{ $clinica->razao_social }}" disabled>
            </div>

            <p class="fm-campo__ajuda fm-campo--largo" style="margin-top: -6px;">CNPJ e razão social foram conferidos na base simulada do
                PointMed no cadastro e não mudam por aqui.</p>

            <div class="fm-campo {{ $errors->has('nome_fantasia') ? 'fm-campo--erro' : '' }}">
                <label for="nome_fantasia">Nome fantasia *</label>
                <input id="nome_fantasia" name="nome_fantasia" required minlength="2" maxlength="150" value="{{ old('nome_fantasia', $clinica->nome_fantasia) }}">
                <span class="fm-campo__ajuda">É o nome que aparece na busca.</span>
                @error('nome_fantasia') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>

            <div class="fm-campo {{ $errors->has('telefone') ? 'fm-campo--erro' : '' }}">
                <label for="telefone">Telefone</label>
                <input id="telefone" name="telefone" type="tel" inputmode="numeric" maxlength="15"
                       value="{{ old('telefone', Formatador::telefone($clinica->telefone)) }}" placeholder="(12) 3456-7890">
                @error('telefone') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>

            <div class="fm-campo fm-campo--largo {{ $errors->has('descricao') ? 'fm-campo--erro' : '' }}">
                <label for="descricao">Sobre a clínica</label>
                <textarea id="descricao" name="descricao" maxlength="2000" placeholder="Especialidades, estrutura, estacionamento, acessibilidade do prédio...">{{ old('descricao', $clinica->descricao) }}</textarea>
                <span class="fm-campo__ajuda">Aparece no perfil público da clínica.</span>
                @error('descricao') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>

            <div class="fm-campo {{ $errors->has('name') ? 'fm-campo--erro' : '' }}">
                <label for="name">Responsável pela conta *</label>
                <input id="name" name="name" required minlength="3" maxlength="255" value="{{ old('name', $user->name) }}">
                @error('name') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>

            <div class="fm-campo">
                <label for="email">E-mail de acesso</label>
                <input id="email" value="{{ $user->email }}" disabled>
                <span class="fm-campo__ajuda">É o login; não muda por aqui.</span>
            </div>

            <div class="fm-form__acoes">
                <button type="submit" class="fm-botao">Salvar dados</button>
            </div>
        </form>
    </section>

    {{-- Mesmo bloco de senha do perfil do médico. --}}
    @include('painel.parciais.senha')

@endsection
