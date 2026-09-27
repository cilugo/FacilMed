@extends('layouts.auth')

@section('titulo', 'Confirme seu e-mail')

@section('conteudo')
    <h1 class="title">Confirme seu e-mail</h1>
    <p class="subtitle">Mandamos um link de confirmação para o seu e-mail. Não chegou? Podemos mandar de novo.</p>

    @if (session('status') === 'verification-link-sent')
        <div class="aviso aviso--ok" role="status">Enviamos um link novo para o seu e-mail.</div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="login-button">Reenviar e-mail</button>
    </form>

    <div class="divider"><span>ou</span></div>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="botao-secundario">Sair</button>
    </form>
@endsection
