{{--
    Sidebar do FacilMed.

    Lê os itens de config/navegacao.php pelo tipo do usuário logado.
    Não escreva item de menu direto aqui: se estiver só no Blade,
    ninguém acha quando precisar mudar, e o menu de um papel sai do ar
    sem ninguém perceber.

    Paleta vinda da identidade visual (PERFIL.md §8):
      azul-marinho no menu, azul-claro no item ativo, branco nos cards.

    Uso:  <x-sidebar />
--}}

@php
    $usuario = auth()->user();
    $itens = $usuario ? config('navegacao.' . $usuario->tipo, []) : [];
@endphp

<aside
    x-data="{ aberto: false }"
    class="bg-[#1e3a8a] text-white w-64 shrink-0 flex flex-col min-h-screen
           fixed inset-y-0 left-0 z-40 -translate-x-full transition-transform
           lg:static lg:translate-x-0"
    :class="aberto && 'translate-x-0'"
    @toggle-menu.window="aberto = !aberto"
>
    {{-- Marca --}}
    <div class="px-6 py-6 border-b border-white/10">
        <a href="{{ url('/') }}" class="flex items-center gap-3">
            <img src="{{ asset('imgs/logosemslogan.png') }}" alt="" class="h-9 w-9 object-contain">
            <span class="text-xl font-semibold tracking-tight">FacilMed</span>
        </a>
    </div>

    {{-- Itens --}}
    <nav class="flex-1 overflow-y-auto py-4" aria-label="Menu principal">
        <ul class="space-y-1 px-3">
            @foreach ($itens as $item)
                @continue(! \Illuminate\Support\Facades\Route::has($item['rota']))

                @php $ativo = request()->routeIs($item['rota'] . '*'); @endphp

                <li>
                    <a
                        href="{{ route($item['rota']) }}"
                        @if ($ativo) aria-current="page" @endif
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition
                               {{ $ativo
                                   ? 'bg-[#3b82f6] text-white font-medium'
                                   : 'text-blue-100 hover:bg-white/10' }}"
                    >
                        <x-icone :nome="$item['icone']" class="h-5 w-5 shrink-0" />
                        <span>{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    {{-- Rodapé --}}
    <div class="border-t border-white/10 p-3">
        @if ($usuario?->ehMedico() && $usuario->medico?->status_verificacao === 'pendente')
            {{-- Médico pendente não aparece na busca. Ele precisa saber
                 disso, em vez de estranhar que ninguém agenda com ele. --}}
            <p class="mb-3 rounded-lg bg-amber-400/15 px-3 py-2 text-xs text-amber-100">
                Seu CRM está em verificação. Você ainda não aparece nas buscas.
            </p>
        @endif

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm
                       text-blue-100 transition hover:bg-white/10"
            >
                <x-icone nome="logout" class="h-5 w-5 shrink-0" />
                <span>Sair</span>
            </button>
        </form>
    </div>
</aside>

{{-- Fundo escuro no mobile, quando o menu está aberto --}}
<div
    x-data
    x-show="$store.menu?.aberto"
    class="fixed inset-0 z-30 bg-black/40 lg:hidden"
    @click="$dispatch('toggle-menu')"
    x-cloak
></div>
