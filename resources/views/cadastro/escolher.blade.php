{{--
    Escolha do tipo de cadastro. Visual da Mariana (protótipo cadpac/cadastro.html).
    29/09/2026: só paciente e clínica/hospital - o médico é cadastrado pela clínica.
--}}
@extends('layouts.cadastro')

@section('titulo', 'Criar conta')
@section('conteudo_classe', 'conteudo--centro')

@section('conteudo')
    <div class="escolha">
        <h1>Como você deseja se cadastrar?</h1>
        <p class="subtitulo">Escolha uma opção para continuar</p>

        @include('cadastro._avisos')

        <nav class="opcoes" aria-label="Tipo de cadastro">
            <a class="botao" href="{{ route('cadastro.paciente') }}">Sou paciente</a>
            <a class="botao" href="{{ route('cadastro.clinica') }}">Sou clínica/hospital</a>
        </nav>

        <p class="entrar">Já tem uma conta? <a href="{{ route('login') }}">Entre</a></p>
    </div>
@endsection
