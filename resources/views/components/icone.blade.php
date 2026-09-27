{{--
    Ícones do FacilMed (SVG inline, traço de 1.8px, herdam a cor do texto).

    Uso:  <x-icone nome="calendar" class="h-5 w-5" />
          <x-icone :nome="$item['icone']" />

    Os nomes são os "slugs" de config/navegacao.php (home, calendar, user...)
    mais os que os painéis usam (chart, check-circle, arrow-up...).
    Nome desconhecido desenha um círculo, em vez de quebrar a página.

    Para acrescentar um ícone: crie um @case novo com o desenho em
    viewBox 24x24. Sem biblioteca externa de propósito (AGENTS.md §3).
--}}
@props(['nome'])

<svg
    {{ $attributes->merge(['class' => 'fm-icone']) }}
    viewBox="0 0 24 24" fill="none" stroke="currentColor"
    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
    aria-hidden="true" focusable="false"
>
    @switch($nome)
        {{-- Especialidades (24/09) - o slug vem de especialidades.icone --}}
        @case('stethoscope')
            <path d="M6 3v6a4 4 0 0 0 8 0V3"/><path d="M4.5 3H7.5M12.5 3h3"/><path d="M10 13v2a5 5 0 0 0 10 0v-1.5"/><circle cx="20" cy="11.5" r="2"/>
            @break
        @case('heart')
            <path d="M12 20s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7.2a4.3 4.3 0 0 1 7.5 2.6C19.5 15.4 12 20 12 20Z"/>
            @break
        @case('baby')
            <circle cx="12" cy="12" r="8.5"/><path d="M9 10.5h.01M15 10.5h.01"/><path d="M9.5 15a3.5 3.5 0 0 0 5 0"/><path d="M12 3.5c-1.2 1-1.2 2.5 0 3"/>
            @break
        @case('skin')
            <path d="M4 7c3-3 13-3 16 0v10c-3 3-13 3-16 0Z"/><path d="M8 10h.01M12 12h.01M15.5 9.5h.01M10 15h.01M15 15h.01"/>
            @break
        @case('female')
            <circle cx="12" cy="9" r="5"/><path d="M12 14v7M9 18h6"/>
            @break
        @case('bone')
            <path d="M8.5 8.5 15.5 15.5"/><path d="M8.5 8.5a2.5 2.5 0 1 0-3.5-3.5 2.5 2.5 0 1 0-1.5 4.3 2.5 2.5 0 0 0 5 -.8"/><path d="M15.5 15.5a2.5 2.5 0 1 0 3.5 3.5 2.5 2.5 0 1 0 1.5-4.3 2.5 2.5 0 0 0-5 .8"/>
            @break
        @case('brain')
            <path d="M12 5v14"/><path d="M12 5a3 3 0 0 0-5.5 1.5A3 3 0 0 0 4 10.5a3 3 0 0 0 1 5A3 3 0 0 0 9 19.5 3 3 0 0 0 12 19"/><path d="M12 5a3 3 0 0 1 5.5 1.5A3 3 0 0 1 20 10.5a3 3 0 0 1-1 5 3 3 0 0 1-4 4 3 3 0 0 1-3-.5"/>
            @break
        @case('eye')
            <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/>
            @break
        @case('mind')
            <path d="M5 20v-3.5A7 7 0 1 1 17 12l1.5 3h-2V18a2 2 0 0 1-2 2h-2"/><path d="M10 9.5a2 2 0 1 1 2 2v1.5"/>
            @break
        @case('gland')
            <path d="M12 4v4"/><path d="M12 8c-4 0-7 2.5-7 6.5S7.5 20 9.5 20c1.3 0 2-1 2.5-2 .5 1 1.2 2 2.5 2 2 0 4.5-1.5 4.5-5.5S16 8 12 8Z"/>
            @break
        @case('ear')
            <path d="M7 9a5 5 0 0 1 10 0c0 3-2.5 4-3 6.5-.4 2.2-1.7 3.5-3.5 3.5A3 3 0 0 1 7.5 17"/><path d="M10 9.5a2 2 0 0 1 4 0c0 1.2-1 1.7-1.5 2.5"/>
            @break
        @case('kidney')
            <path d="M9 4C5.5 4 4 7 4 11s1.5 8 5 8c2.5 0 3-2 2-4-.8-1.5-.8-3 0-4.5 1-2 .5-6.5-2-6.5Z"/><path d="M15 4c3.5 0 5 3 5 7s-1.5 8-5 8"/>
            @break
        @case('hospital')
            <path d="M4 21V7l8-4 8 4v14"/><path d="M12 9v6M9 12h6"/><path d="M2.5 21h19"/>
            @break
        @case('phone')
            <path d="M5 4h3.5l1.5 4.5-2 1.5a11 11 0 0 0 6 6l1.5-2 4.5 1.5V19a1.5 1.5 0 0 1-1.5 1.5A15.5 15.5 0 0 1 3.5 5.5 1.5 1.5 0 0 1 5 4Z"/>
            @break
        @case('arrow-left')
            <path d="M19 12H5M11 6l-6 6 6 6"/>
            @break
        @case('arrow-right')
            <path d="M5 12h14M13 6l6 6-6 6"/>
            @break
        @case('info')
            <circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>
            @break
        @case('home')
            <path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v9a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1v-9"/>
            @break
        @case('search')
            <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
            @break
        @case('calendar')
            <rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M8 3v4M16 3v4M3.5 10h17"/>
            @break
        @case('calendar-check')
            <rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M8 3v4M16 3v4M3.5 10h17"/><path d="m9 15 2 2 4-4"/>
            @break
        @case('card')
            <rect x="3" y="5.5" width="18" height="13" rx="2.5"/><path d="M3 10h18M7 15h3"/>
            @break
        @case('user')
            <circle cx="12" cy="8" r="4"/><path d="M4.5 20c.8-3.6 4-5.5 7.5-5.5s6.7 1.9 7.5 5.5"/>
            @break
        @case('user-check')
            <circle cx="10" cy="8" r="4"/><path d="M3.5 20c.8-3.6 3.6-5.5 6.5-5.5"/><path d="m15 17 2 2 4-4.5"/>
            @break
        @case('user-x')
            <circle cx="10" cy="8" r="4"/><path d="M3.5 20c.8-3.6 3.6-5.5 6.5-5.5"/><path d="m16 15.5 4 4M20 15.5l-4 4"/>
            @break
        @case('users')
            <circle cx="9" cy="8.5" r="3.5"/><path d="M2.5 19.5c.6-3.3 3.3-5 6.5-5s5.9 1.7 6.5 5"/><circle cx="17" cy="9.5" r="2.6"/><path d="M17.5 14.6c2.4.2 4.2 1.7 4.7 4.4"/>
            @break
        @case('doctors')
            <path d="M6 3.5v5.5a4 4 0 0 0 8 0V3.5"/><path d="M4.5 3.5H7.5M12.5 3.5h3"/><path d="M10 13v2.2a4.8 4.8 0 0 0 9.6 0V13"/><circle cx="19.6" cy="11" r="2"/>
            @break
        @case('clock')
            <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>
            @break
        @case('pause')
            <circle cx="12" cy="12" r="9"/><path d="M10 9v6M14 9v6"/>
            @break
        @case('pin')
            <path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>
            @break
        @case('money')
            <circle cx="12" cy="12" r="9"/><path d="M14.6 9.4c-.5-.8-1.4-1.3-2.6-1.3-1.5 0-2.6.8-2.6 2 0 2.7 5.3 1.4 5.3 4.1 0 1.2-1.1 2.1-2.7 2.1-1.3 0-2.3-.5-2.8-1.4M12 6.5v1.6M12 16.2v1.5"/>
            @break
        @case('star')
            <path d="m12 3.5 2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8L3.5 9.7l5.9-.9z"/>
            @break
        @case('building')
            <path d="M4 20.5V5.5A1.5 1.5 0 0 1 5.5 4h8A1.5 1.5 0 0 1 15 5.5v15M15 10h3.5a1.5 1.5 0 0 1 1.5 1.5v9M3 20.5h18M8 8h3M8 12h3M8 16h3"/>
            @break
        @case('shield')
            <path d="M12 3 4.5 6v5.5c0 4.5 3.2 7.6 7.5 9.5 4.3-1.9 7.5-5 7.5-9.5V6z"/><path d="m9 12 2.2 2.2L15.5 10"/>
            @break
        @case('badge')
            <circle cx="12" cy="9" r="5.5"/><path d="m9 13.6-1 6.9 4-2.2 4 2.2-1-6.9"/><path d="m9.8 9 1.5 1.5L14.3 7.6"/>
            @break
        @case('tag')
            <path d="M3.5 12.2V4.5a1 1 0 0 1 1-1h7.7l8.3 8.3a1.5 1.5 0 0 1 0 2.1l-5.6 5.6a1.5 1.5 0 0 1-2.1 0z"/><circle cx="8" cy="8" r="1.3"/>
            @break
        @case('logout')
            <path d="M9 4.5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h3M15 8l4 4-4 4M19 12H9"/>
            @break
        @case('chart')
            <path d="M4 20V10M10 20V4M16 20v-7M21 20H3"/>
            @break
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16"/>
            @break
        @case('x')
            <path d="M6 6l12 12M18 6 6 18"/>
            @break
        @case('plus')
            <path d="M12 5v14M5 12h14"/>
            @break
        @case('check-circle')
            <circle cx="12" cy="12" r="9"/><path d="m8.5 12.3 2.4 2.4 4.6-5"/>
            @break
        @case('x-circle')
            <circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/>
            @break
        @case('alert')
            <path d="M12 3.8 2.8 19.5h18.4z"/><path d="M12 10v4.5M12 17.2v.3"/>
            @break
        @case('chevron-right')
            <path d="m9 5 7 7-7 7"/>
            @break
        @case('chevron-left')
            <path d="m15 5-7 7 7 7"/>
            @break
        @case('chevron-down')
            <path d="m6 9 6 6 6-6"/>
            @break
        @case('arrow-up')
            <path d="M12 19V5M6 11l6-6 6 6"/>
            @break
        @case('arrow-down')
            <path d="M12 5v14M6 13l6 6 6-6"/>
            @break
        @case('minus')
            <path d="M5 12h14"/>
            @break
        @case('bolt')
            <path d="M13 3 5 13.5h6L10 21l8-10.5h-6z"/>
            @break
        @case('list')
            <path d="M8.5 6.5H20M8.5 12H20M8.5 17.5H20"/><circle cx="4.5" cy="6.5" r=".6"/><circle cx="4.5" cy="12" r=".6"/><circle cx="4.5" cy="17.5" r=".6"/>
            @break
        @case('lightbulb')
            <path d="M9 18h6M10 21h4"/><path d="M12 3a6 6 0 0 0-3.6 10.8c.6.5 1 1.2 1 2V16h5.2v-.2c0-.8.4-1.5 1-2A6 6 0 0 0 12 3z"/>
            @break
        @case('pencil')
            {{-- 24/09: botão "Editar" dos CRUDs (convênios e planos) --}}
            <path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>
            @break
        @case('power')
            {{-- 24/09: ativar/desativar registro --}}
            <path d="M12 3v8"/><path d="M6.4 6.6a8 8 0 1 0 11.2 0"/>
            @break
        @default
            <circle cx="12" cy="12" r="8"/>
    @endswitch
</svg>
