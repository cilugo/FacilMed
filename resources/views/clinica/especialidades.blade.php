{{--
    Clínica → Especialidades (01/10/2026). Dados: Clinica\EspecialidadeController.
    A clínica cria especialidade nova; renomear e desativar ficam com o admin.
--}}
@extends('layouts.painel')

@section('titulo', 'Especialidades')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Especialidades</h1>
            <p class="fm-subtitulo">As especialidades da plataforma. Falta alguma? Crie aqui.</p>
        </div>
    </div>

    <section class="fm-painel" style="margin-top: 18px;">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="plus" /> Nova especialidade</h2>
        </header>

        <form method="POST" action="{{ route('clinica.especialidades.salvar') }}" class="fm-filtros">
            @csrf
            <div class="fm-campo {{ $errors->has('nome') ? 'fm-campo--erro' : '' }}" style="flex-grow: 3;">
                <label for="nome">Nome *</label>
                <input id="nome" name="nome" maxlength="100" required value="{{ old('nome') }}" placeholder="Ex.: Endocrinologia">
                @error('nome') <span class="fm-campo__erro">{{ $message }}</span> @enderror
            </div>
            <button type="submit" class="fm-botao fm-botao--pequeno">Criar</button>
        </form>

        <p class="fm-dica">
            <x-icone nome="lightbulb" />
            <span>A especialidade nova vale para toda a plataforma. Depois de criar, marque-a no perfil do médico
                (<em>Meus médicos → Editar perfil</em>).</span>
        </p>
    </section>

    <section class="fm-painel">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="tag" /> Especialidades ativas</h2>
            <span class="fm-painel__periodo">{{ $especialidades->count() }}</span>
        </header>

        <div class="fm-chips">
            @foreach ($especialidades as $esp)
                <span class="fm-chip {{ in_array($esp->id, $daClinica, true) ? '' : 'fm-chip--cinza' }}">
                    {{ $esp->nome }}@if (in_array($esp->id, $daClinica, true)) · oferecida aqui @endif
                </span>
            @endforeach
        </div>
    </section>

@endsection
