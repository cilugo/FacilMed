{{--
    Layout das páginas PÚBLICAS de formulário (cadastro). 24/09/2026.

    Usa as mesmas cores e fontes do painel (painel.css) + os estilos de
    formulário do crud.css, para o cadastro parecer parte do mesmo site.
    Sem sidebar: quem está aqui ainda não tem conta.
--}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Cadastro') — FacilMed</title>
    <link rel="icon" href="{{ asset('imgs/marca/logosemslogan.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=open-sans:400,600,700|rosario:600,700&display=swap" rel="stylesheet">
    {{-- Sem Vite/Node desde 24/09: reset em base.css e Alpine local. --}}
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <script src="{{ asset('javas/alpine.min.js') }}" defer></script>
    <link rel="stylesheet" href="{{ asset('css/painel.css') }}">
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
    <style>
        .fm-publico { min-height: 100vh; background: var(--fm-fundo, #f4f8fd); padding: 24px 16px 48px; }
        .fm-publico__caixa { max-width: 760px; margin: 0 auto; }
        .fm-publico__marca { display: flex; justify-content: center; margin: 8px 0 24px; }
        .fm-publico__rodape { margin-top: 18px; text-align: center; font-size: 14px; color: var(--fm-suave); }
        .fm-publico__rodape a { color: var(--fm-azul); font-weight: 700; }
        .fm-opcoes { display: grid; gap: 14px; }
        @media (min-width: 720px) { .fm-opcoes { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        .fm-opcao { display: grid; gap: 8px; padding: 20px; border: 1px solid var(--fm-borda); border-radius: 16px; background: #fff; text-decoration: none; color: var(--fm-texto); }
        .fm-opcao:hover, .fm-opcao:focus-visible { border-color: var(--fm-azul); box-shadow: 0 0 0 3px var(--fm-azul-claro); }
        .fm-opcao strong { font-size: 17px; color: var(--fm-titulo); }
        .fm-opcao .fm-icone { width: 28px; height: 28px; color: var(--fm-azul); }
        .fm-secao-form { margin: 8px 0 -2px; grid-column: 1 / -1; font-size: 13px; font-weight: 700; color: var(--fm-suave); text-transform: uppercase; letter-spacing: .03em; }
        .fm-checks { display: flex; flex-wrap: wrap; gap: 8px 16px; }
        .fm-checks label { display: flex; gap: 6px; align-items: center; font-weight: 600; font-size: 14px; }
    </style>
</head>
<body class="fm-publico">
    <div class="fm-publico__caixa">
        <a href="{{ url('/') }}" class="fm-publico__marca" aria-label="FacilMed — página inicial">
            <span class="fm-logo-recorte" style="--fm-logo-altura: 40px"><img src="{{ asset('imgs/marca/logo.png') }}" alt="FacilMed"></span>
        </a>

        @if (session('sucesso'))
            <div class="fm-flash fm-flash--ok" role="status">{{ session('sucesso') }}</div>
        @endif
        @if (session('erro'))
            <div class="fm-flash fm-flash--erro" role="alert">{{ session('erro') }}</div>
        @endif
        @if ($errors->any())
            <div class="fm-flash fm-flash--erro" role="alert">Confira os campos marcados abaixo.</div>
        @endif

        @yield('conteudo')

        <p class="fm-publico__rodape">
            Já tem conta? <a href="{{ \Illuminate\Support\Facades\Route::has('login') ? route('login') : url('/login') }}">Entrar</a>
        </p>
    </div>
</body>
</html>
