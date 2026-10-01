{{--
    Escolha do tipo de cadastro. 29/09/2026: só paciente e clínica/hospital -
    o médico é cadastrado pela clínica.
    01/10/2026: botões "Conta Pessoal" (paciente) e "Conta Empresarial"
    (clínica/hospital), como a Mari deixou no protótipo (commit e87d8a2).
    Visual: o do grupo (style1.css de 29/09), com as medidas da tela de login.
    As regras ficam em public/css/cadastro.css, bloco ".pagina--escolha".
--}}
@extends('layouts.cadastro')

@section('titulo', 'Criar conta')
@section('pagina_classe', 'pagina--escolha')
@section('conteudo_classe', 'conteudo--centro')

@section('conteudo')
    <div class="escolha">
        <h1>Como você deseja se cadastrar?</h1>
        <p class="subtitulo">Escolha uma opção para continuar</p>

        @include('cadastro._avisos')

        <nav class="opcoes" aria-label="Tipo de cadastro">
            <a class="botao" href="{{ route('cadastro.paciente') }}">Conta Pessoal</a>
            <a class="botao" href="{{ route('cadastro.clinica') }}">Conta Empresarial</a>
        </nav>

        <p class="entrar">Já tem uma conta? <a href="{{ route('login') }}">Entre</a></p>
    </div>
@endsection
