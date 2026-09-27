{{--
    Paciente → avaliar uma consulta realizada. Dados: Paciente\AvaliacaoController@form.
    A nota entra na média pública do médico; o comentário é PRIVADO
    (AGENTS.md §6) e não aparece no perfil público.
--}}
@extends('layouts.painel')

@section('titulo', 'Avaliar consulta')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
    <style>
        .estrelas { display: flex; gap: 6px; }
        .estrelas button { font-size: 36px; line-height: 1; color: #d5dde8; background: none; border: 0; cursor: pointer; padding: 2px; }
        .estrelas button.acesa { color: #f2b01e; }
    </style>
@endpush

@php use App\Support\Formatador; @endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Como foi a consulta?</h1>
            <p class="fm-subtitulo">
                {{ $consulta->medico->user->name }} · {{ $consulta->especialidade->nome }} ·
                {{ Formatador::dataCurta($consulta->data_consulta) }}
            </p>
        </div>
    </div>

    <section class="fm-painel" style="margin-top: 18px; max-width: 640px;" x-data="{ nota: {{ (int) old('estrelas', 0) }}, sobre: 0 }">
        <form method="POST" action="{{ route('paciente.consultas.avaliar', $consulta) }}" class="fm-form">
            @csrf
            <input type="hidden" name="estrelas" :value="nota">

            <div class="fm-campo {{ $errors->has('estrelas') ? 'fm-campo--erro' : '' }}">
                <label>Sua nota *</label>
                <div class="estrelas" @mouseleave="sobre = 0">
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button" aria-label="{{ $i }} {{ $i === 1 ? 'estrela' : 'estrelas' }}"
                                :class="(sobre || nota) >= {{ $i }} && 'acesa'"
                                @mouseenter="sobre = {{ $i }}" @click="nota = {{ $i }}">★</button>
                    @endfor
                </div>
                @error('estrelas') <span class="fm-campo__erro">Escolha de 1 a 5 estrelas.</span> @enderror
            </div>

            <div class="fm-campo">
                <label for="comentario">Comentário (opcional)</label>
                <textarea id="comentario" name="comentario" maxlength="1000">{{ old('comentario') }}</textarea>
                <span class="fm-campo__ajuda">Só o médico lê o comentário. No perfil público aparece apenas a nota.</span>
            </div>

            <div class="fm-form__acoes">
                <button type="submit" class="fm-botao" :disabled="!nota">Enviar avaliação</button>
            </div>
        </form>
    </section>

@endsection
