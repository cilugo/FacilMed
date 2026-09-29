{{--
    Ícones do cadastro (traço, 24x24), os mesmos do protótipo da Mariana.
    @include('cadastro._icone', ['nome' => 'pessoa', 'classe' => 'tipo__icone'])
--}}
@php
    $caminhos = [
        'pessoa'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1c0-3.3 3.6-6 8-6s8 2.7 8 6v1z"/>',
        'documento'  => '<rect x="4" y="3" width="16" height="18" rx="2"/><circle cx="12" cy="10" r="2.5"/><path d="M8 17c.6-2 2.1-3 4-3s3.4 1 4 3"/>',
        'calendario' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'telefone'   => '<path d="M5 4h3l2 5-2.5 1.5a11 11 0 0 0 6 6L15 14l5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
        'email'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'cadeado'    => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"/>',
        'olho'       => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'seta-baixo' => '<path d="M6 9l6 6 6-6"/>',
        'seta'       => '<path d="M4 12h16M14 6l6 6-6 6"/>',
        'predio'     => '<path d="M4 21V7l8-4 8 4v14"/><path d="M4 21h16M9 21v-5h6v5"/>',
        'clinica'    => '<path d="M3 21h18M5 21V10l7-5 7 5v11"/><path d="M12 10v5M9.5 12.5h5"/>',
        'hospital'   => '<rect x="3" y="9" width="18" height="12" rx="1"/><rect x="7" y="3" width="10" height="18" rx="1"/><path d="M12 6v5M9.5 8.5h5M10 21v-4h4v4"/>',
        'instituicao'=> '<rect x="3" y="9" width="18" height="12" rx="1"/><rect x="7" y="3" width="10" height="18" rx="1"/><path d="M12 6v5M9.5 8.5h5"/>',
        'lista'      => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/>',
        'local'      => '<path d="M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/>',
        'texto'      => '<path d="M4 6h16M4 12h16M4 18h10"/>',
        'maos'       => '<circle cx="12" cy="5" r="2"/><path d="M12 7v6M8 10h8M9 21l3-8 3 8"/>',
    ];
@endphp
<svg class="icone {{ $classe ?? '' }}" viewBox="0 0 24 24" aria-hidden="true" focusable="false">{!! $caminhos[$nome] ?? '' !!}</svg>
