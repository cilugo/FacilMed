{{--
    Admin → Meu perfil (01/10/2026): foto de perfil e troca de senha.
--}}
@extends('layouts.painel')

@section('titulo', 'Meu perfil')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Meu perfil</h1>
            <p class="fm-subtitulo">{{ auth()->user()->name }} · {{ auth()->user()->email }}</p>
        </div>
    </div>

    @include('painel.parciais.foto')

    @include('painel.parciais.senha')

@endsection
