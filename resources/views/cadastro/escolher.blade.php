{{-- Escolha do tipo de conta. Visual do "pré-cadastro" do grupo (login.css). --}}
@extends('layouts.auth')

@section('titulo', 'Criar conta')

@section('conteudo')
    <h1 class="title">Crie sua conta</h1>
    <p class="subtitle">Como você quer usar o FacilMed?</p>

    <div class="buttongroup">
        <a href="{{ route('cadastro.paciente') }}" class="login-button login-button--link" style="flex-direction: column;">
            Sou paciente
            <span class="escolha-descricao">Encontre médicos e agende consultas</span>
        </a>
        <a href="{{ route('cadastro.medico') }}" class="login-button login-button--link" style="flex-direction: column;">
            Sou médico
            <span class="escolha-descricao">CRM conferido na hora, na base simulada</span>
        </a>
        <a href="{{ route('cadastro.clinica') }}" class="login-button login-button--link" style="flex-direction: column;">
            Sou clínica ou hospital
            <span class="escolha-descricao">Cadastre a empresa e depois os seus médicos</span>
        </a>
    </div>

    <p class="register">Já tem uma conta? <a href="{{ route('login') }}">Entrar</a></p>
@endsection
