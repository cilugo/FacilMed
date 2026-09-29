{{--
    Layout das telas de ENTRADA: login, esqueci a senha, nova senha,
    confirmar senha e verificar e-mail. (O cadastro tem layout próprio,
    layouts/cadastro, desde 29/09/2026.)

    Visual: public/css/login.css, feito pelo grupo para o protótipo
    (paginas/login.html) e trazido para o Laravel em 24/09/2026.
    Tela dividida: marca à esquerda, formulário à direita. No celular a
    marca some e a logo aparece em cima do formulário.

    Uso:
        @extends('layouts.auth')
        @section('titulo', 'Entrar')
        @section('conteudo') ... @endsection
--}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Entrar') — FacilMed</title>
    <link rel="icon" href="{{ asset('imgs/marca/logosemslogan.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
    <main class="login-page">
        <section class="login-brand" aria-hidden="true">
            <div class="login-brand__blob login-brand__blob--1"></div>
            <div class="login-brand__blob login-brand__blob--2"></div>
            <div class="login-brand__blob login-brand__blob--3"></div>
            <a href="{{ route('home') }}" style="position: relative; z-index: 2; display: contents;">
                <img src="{{ asset('imgs/marca/logocomslogan.png') }}" alt="" class="login-brand__logo">
            </a>
        </section>

        <section class="login-form-area">
            <div class="login-container">
                <a href="{{ route('home') }}">
                    <img src="{{ asset('imgs/marca/logocomslogan.png') }}" alt="FacilMed — Sua saúde, conectada." class="login-mobile-logo">
                </a>

                @if (session('status') && session('status') !== 'verification-link-sent')
                    <div class="aviso aviso--ok" role="status">{{ session('status') }}</div>
                @endif
                @if (session('sucesso'))
                    <div class="aviso aviso--ok" role="status">{{ session('sucesso') }}</div>
                @endif
                @if (session('erro'))
                    <div class="aviso aviso--erro" role="alert">{{ session('erro') }}</div>
                @endif

                @yield('conteudo')
            </div>
        </section>
    </main>
</body>
</html>
