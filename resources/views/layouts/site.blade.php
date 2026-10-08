{{--
    Layout do SITE PÚBLICO: início, busca e perfis de médico e clínica.

    Visual: public/css/home.css, feito pelo grupo para o protótipo
    (paginas/home.html) e trazido para o Laravel em 24/09/2026, mais
    public/css/site.css com o que a home não tinha (busca, perfis,
    formulários do topo).

    Uso:
        @extends('layouts.site')
        @section('titulo', 'Buscar médicos')
        @section('menu', 'busca')      ← item do menu que fica marcado
        @section('conteudo') ... @endsection

    MENU (01/10/2026, documento de modificações): visitante vê o menu da
    home (Como funciona, Sobre...); quem ENTROU vê o menu de uso — o mesmo
    de config/navegacao.php para o tipo dele, com foto e "Sair". Antes era
    o mesmo menu logado ou não, e só o botão mudava.
--}}
@php
    $usuario = auth()->user();
    $menuAtivo = trim($__env->yieldContent('menu', 'inicio'));

    // Menu de quem entrou: os itens do painel dele (config/navegacao.php),
    // só os que têm rota. O "Início" leva ao painel.
    $menuLogado = $usuario
        ? collect(config('navegacao.' . $usuario->tipo, []))
            ->filter(fn ($item) => \Illuminate\Support\Facades\Route::has($item['rota']))
            ->map(fn ($item) => $item + [
                'url'   => route($item['rota']),
                'ativo' => request()->routeIs($item['rota']),
            ])
            ->values()
        : collect();
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('titulo')@yield('titulo') — PointMed @else PointMed — Clínicas e hospitais perto de você @endif</title>
    <meta name="description" content="Encontre médicos, clínicas e hospitais perto de você, veja quem aceita o seu convênio e a avaliação de outros usuários.">
    <link rel="icon" href="{{ asset('imgs/marca/logosemslogan.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=open-sans:400,600,700|rosario:600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}">
    <script src="{{ asset('javas/alpine.min.js') }}" defer></script>
    @stack('head')
</head>
<body x-data="{ menuAberto: false }" @keydown.escape.window="menuAberto = false">

    <header class="header">
        <div class="container header-content">
            <a href="{{ route('home') }}" class="logo site-logo" aria-label="PointMed, página inicial">
                <img src="{{ asset('imgs/marca/logo-horizontal.png') }}" alt="PointMed">
            </a>

            <nav class="menu" aria-label="Menu principal">
                @auth
                    @foreach ($menuLogado as $item)
                        <a href="{{ $item['url'] }}" class="menu-link {{ $item['ativo'] ? 'active' : '' }}">{{ $item['label'] }}</a>
                    @endforeach
                @else
                    <a href="{{ route('home') }}" class="menu-link {{ $menuAtivo === 'inicio' ? 'active' : '' }}">Início</a>
                    <a href="{{ route('home') }}#como-funciona" class="menu-link">Como funciona</a>
                    <a href="{{ route('home') }}#especialidades" class="menu-link">Especialidades</a>
                    <a href="{{ route('home') }}#hospitais-clinicas" class="menu-link">Hospitais e clínicas</a>
                    <a href="{{ route('home') }}#sobre" class="menu-link">Sobre</a>
                    <a href="{{ route('busca.index') }}" class="menu-link {{ $menuAtivo === 'busca' ? 'active' : '' }}">Encontrar médicos</a>
                    <a href="{{ route('busca.locais') }}" class="menu-link {{ $menuAtivo === 'locais' ? 'active' : '' }}">Perto de você</a>
                @endauth
            </nav>

            <div class="header-actions">
                @auth
                    <a href="{{ route('dashboard') }}" class="site-usuario" title="Meu painel">
                        <x-avatar :nome="$usuario->name" :foto="$usuario->foto_url" />
                        <span class="site-usuario__nome">{{ \App\Support\Formatador::saudacao($usuario->name) }}</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline">Sair</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline">Entrar</a>
                    <a href="{{ route('cadastro.escolher') }}" class="btn btn-primary">Cadastrar</a>
                @endauth

                <button type="button" class="site-menu-botao" @click="menuAberto = !menuAberto"
                        :aria-expanded="menuAberto" aria-label="Abrir menu">
                    <x-icone nome="menu" />
                </button>
            </div>
        </div>

        {{-- Menu do celular (home.css esconde o .menu abaixo de 800px) --}}
        <nav class="site-menu-celular" x-show="menuAberto" x-cloak @click.outside="menuAberto = false" aria-label="Menu">
            @auth
                @foreach ($menuLogado as $item)
                    <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                @endforeach
            @else
                <a href="{{ route('home') }}">Início</a>
                <a href="{{ route('home') }}#como-funciona" @click="menuAberto = false">Como funciona</a>
                <a href="{{ route('home') }}#especialidades" @click="menuAberto = false">Especialidades</a>
                <a href="{{ route('home') }}#hospitais-clinicas" @click="menuAberto = false">Hospitais e clínicas</a>
                <a href="{{ route('home') }}#sobre" @click="menuAberto = false">Sobre</a>
                <a href="{{ route('busca.index') }}">Encontrar médicos</a>
                <a href="{{ route('busca.locais') }}">Perto de você</a>
            @endauth
        </nav>
    </header>

    <main>
        @if (session('sucesso'))
            <div class="container"><div class="site-aviso site-aviso--ok" role="status">{{ session('sucesso') }}</div></div>
        @endif
        @if (session('erro'))
            <div class="container"><div class="site-aviso site-aviso--erro" role="alert">{{ session('erro') }}</div></div>
        @endif

        @yield('conteudo')
    </main>

    <footer class="footer">
        <div class="container site-rodape">
            <img src="{{ asset('imgs/marca/simbolo.png') }}" alt="" class="site-rodape__logo">
            <p class="footer-copy">
                {{ now()->year }} PointMed · Projeto acadêmico (TCC) — Etec Profª Ilza Nascimento Pintus.
                Médicos, clínicas e convênios são fictícios.
            </p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
