{{--
    Layout dos painéis (paciente, médico, clínica e a tela de consultas
    realizadas). Quem usa faz:

        @extends('layouts.painel')
        @section('titulo', 'Início')
        @section('conteudo')  ...  @endsection

    Estrutura: menu lateral fixo no computador e "gaveta" no celular, barra
    de topo com o usuário, e a área de conteúdo.

    Visual: public/css/painel.css (CSS simples, sem build).
    Comportamento: Alpine.js, que já vem no resources/js/app.js do Breeze.
    Gráficos: public/js/graficos.js (SVG puro, sem biblioteca).
--}}

@php
    $usuario = auth()->user();
    $nomeUsuario = $usuario->name ?? '';
    $iniciais = \App\Support\Formatador::iniciais($nomeUsuario);
    $papel = \App\Support\Formatador::PAPEIS[$usuario->tipo ?? ''] ?? '';
    $rotaPerfil = ($usuario && \Illuminate\Support\Facades\Route::has($usuario->tipo . '.perfil'))
        ? route($usuario->tipo . '.perfil')
        : null;
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('titulo', 'Painel') — FacilMed</title>

    <link rel="icon" href="{{ asset('imgs/marca/facilmed-simbolo.png') }}" type="image/png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=open-sans:400,600,700|rosario:600,700&display=swap" rel="stylesheet">

    {{-- Tailwind e Alpine do Breeze. Vem ANTES do painel.css para o nosso
         CSS ganhar qualquer empate. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/painel.css') }}">

    @stack('head')
</head>

<body class="fm-app" x-data="{ menu: false, perfil: false }" @keydown.escape.window="menu = false; perfil = false">

    <x-sidebar />

    {{-- Fundo escuro atrás da gaveta, só no celular --}}
    <div class="fm-fundo-menu" x-show="menu" x-cloak @click="menu = false"></div>

    <div class="fm-principal">

        <header class="fm-topo">
            <button type="button" class="fm-topo__menu" @click="menu = true" aria-label="Abrir menu">
                <x-icone nome="menu" />
            </button>

            <a href="{{ url('/') }}" class="fm-topo__logo" aria-label="FacilMed, ir para o início">
                <img src="{{ asset('imgs/marca/facilmed-logo-horizontal-recortada.png') }}" alt="FacilMed">
            </a>

            <div class="fm-usuario" @click.outside="perfil = false">
                <button
                    type="button"
                    class="fm-usuario__botao"
                    @click="perfil = !perfil"
                    :aria-expanded="perfil"
                    aria-haspopup="menu"
                >
                    <span class="fm-avatar">{{ $iniciais }}</span>
                    <span class="fm-usuario__texto">
                        <strong>{{ $nomeUsuario }}</strong>
                        <small>{{ $papel }}</small>
                    </span>
                    <x-icone nome="chevron-down" class="fm-usuario__seta" />
                </button>

                <div class="fm-usuario__menu" x-show="perfil" x-cloak role="menu">
                    @if ($rotaPerfil)
                        <a href="{{ $rotaPerfil }}" role="menuitem">Meu perfil</a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" role="menuitem">Sair</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="fm-conteudo">
            @if (session('sucesso'))
                <div class="fm-flash fm-flash--ok" role="status">{{ session('sucesso') }}</div>
            @endif

            @if (session('erro'))
                <div class="fm-flash fm-flash--erro" role="alert">{{ session('erro') }}</div>
            @endif

            @yield('conteudo')
        </main>

    </div>

    <script src="{{ asset('js/graficos.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
