{{--
    Sidebar do FacilMed, no estilo dos mockups (fundo claro, item ativo
    em azul-claro, marca no topo).

    Lê os itens de config/navegacao.php pelo tipo do usuário logado. Não
    escreva item de menu direto aqui: se estiver só no Blade, ninguém acha
    quando precisar mudar, e o menu de um papel sai do ar sem ninguém
    perceber.

    Só mostra o item se a ROTA EXISTE (Route::has). Assim o menu nunca
    aponta para uma tela que ninguém criou ainda.

    Precisa estar dentro de um elemento com x-data="{ menu: false }" —
    o layout layouts/painel.blade.php já faz isso. No celular o menu vira
    uma gaveta que abre pelo botão do topo.

    Uso:  <x-sidebar />
--}}

@php
    $usuario = auth()->user();

    $itens = collect($usuario ? config('navegacao.' . $usuario->tipo, []) : [])
        ->filter(fn ($item) => \Illuminate\Support\Facades\Route::has($item['rota']))
        ->map(fn ($item) => $item + [
            'url'   => route($item['rota']),
            'ativo' => request()->routeIs($item['rota'] . '*'),
        ])
        ->values()
        ->all();

    // Médico pendente não aparece na busca. Ele precisa saber disso, em vez
    // de estranhar que ninguém agenda com ele.
    $crmPendente = $usuario?->ehMedico() && $usuario->medico?->status_verificacao === 'pendente';
@endphp

<aside class="fm-sidebar" :class="{ 'is-open': menu }" aria-label="Menu principal">

    <div class="fm-sidebar__marca">
        <a href="{{ url('/') }}" aria-label="FacilMed, ir para o início">
            <img src="{{ asset('imgs/marca/facilmed-logo-horizontal-recortada.png') }}" alt="FacilMed">
        </a>

        <button type="button" class="fm-sidebar__fechar" @click="menu = false" aria-label="Fechar menu">
            <x-icone nome="x" />
        </button>
    </div>

    <nav class="fm-sidebar__nav">
        <ul>
            @foreach ($itens as $item)
                <li>
                    <a
                        href="{{ $item['url'] }}"
                        class="fm-nav {{ $item['ativo'] ? 'is-ativo' : '' }}"
                        @if ($item['ativo']) aria-current="page" @endif
                    >
                        <x-icone :nome="$item['icone']" />
                        <span>{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="fm-sidebar__rodape">
        @if ($crmPendente)
            <p class="fm-aviso">
                Seu CRM está em verificação. Você ainda não aparece nas buscas.
            </p>
        @endif

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="fm-nav fm-nav--sair">
                <x-icone nome="logout" />
                <span>Sair</span>
            </button>
        </form>
    </div>
</aside>
