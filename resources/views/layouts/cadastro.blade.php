{{--
    Layout do CADASTRO (escolha, paciente, clínica/hospital). 29/09/2026.

    Visual da Mariana (prototipo-antigo/paginas/cadpac): marca à esquerda,
    formulário em cartões à direita; no celular a marca vai para cima.
    CSS em public/css/cadastro.css, máscaras em public/javas/cadastro.js.
    Alpine (local) só para mostrar/esconder o bloco de acessibilidade.

    Uso:
        @extends('layouts.cadastro')
        @section('titulo', 'Cadastro de paciente')
        @section('conteudo') ... @endsection
        (opcional) @section('conteudo_classe', 'conteudo--centro')
        (opcional) @section('pagina_classe', 'pagina--escolha')  ← só a tela de escolha
--}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Cadastro') — FacilMed</title>
    <link rel="icon" href="{{ asset('imgs/marca/logosemslogan.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('css/cadastro.css') }}">
    <script src="{{ asset('javas/alpine.min.js') }}" defer></script>
</head>
<body>
    <main class="pagina @yield('pagina_classe')">
        <section class="marca">
            <div class="circulo circulo--1"></div>
            <div class="circulo circulo--2"></div>
            <div class="circulo circulo--3"></div>
            <a href="{{ route('home') }}" class="marca__link" aria-label="FacilMed — voltar para a página inicial">
                <img src="{{ asset('imgs/marca/logocomslogan.png') }}" alt="FacilMed — Sua saúde, conectada." class="marca__logo">
            </a>
        </section>

        <section class="conteudo @yield('conteudo_classe')">
            @yield('conteudo')
        </section>
    </main>

    <script src="{{ asset('javas/cadastro.js') }}" defer></script>
</body>
</html>
