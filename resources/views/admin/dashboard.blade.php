{{--
    Dashboard do admin (24/09/2026; refeito em 01/10/2026 sem consultas).
    Dados prontos de App\Http\Controllers\Admin\DashboardController.
--}}
@extends('layouts.painel')

@section('titulo', 'Início')

@push('head')
    <style>
        .adm-linha { display: grid; gap: 18px; margin-top: 18px; }
        .adm-linha > .fm-painel { margin: 0; min-width: 0; }
        @media (min-width: 1100px) {
            .adm-linha--tres { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (min-width: 1100px) and (max-width: 1399px) {
            .fm-grade--cartoes.adm-cartoes { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (min-width: 1400px) {
            .fm-grade--cartoes.adm-cartoes { grid-template-columns: repeat(5, minmax(0, 1fr)); }
        }
        .adm-mini { display: grid; grid-template-columns: 40px minmax(0, 1fr) auto; gap: 12px; align-items: center; padding: 10px 0; }
        .adm-mini + .adm-mini { border-top: 1px solid #e7eff9; }
        .adm-mini__icone { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
        .adm-mini strong { display: block; color: var(--fm-titulo); font-size: 14px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .adm-mini small { display: block; color: var(--fm-suave); font-size: 12.5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .adm-mini__lado { font-size: 12.5px; color: var(--fm-suave); text-align: right; }
        .adm-numeros { display: flex; flex-wrap: wrap; gap: 2px 12px; margin-top: 3px; font-size: 12.5px; color: var(--fm-suave); }
        .adm-numeros span { white-space: nowrap; display: inline-flex; gap: 4px; align-items: center; }
        .adm-numeros .fm-icone { width: 14px; height: 14px; }
    </style>
@endpush

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Olá, {{ $saudacao }}!</h1>
            <p class="fm-subtitulo">Acompanhe a plataforma: clínicas, médicos, usuários e avaliações.</p>
        </div>
        <div class="fm-data">
            <x-icone nome="calendar" />
            <span>{{ $dataHoje }}</span>
        </div>
    </div>

    <div class="fm-grade fm-grade--cartoes adm-cartoes">
        @foreach ($cartoes as $c)
            @include('painel.parciais.cartao', ['c' => $c])
        @endforeach
    </div>

    <div class="adm-linha adm-linha--tres">
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="building" /> Locais mais bem avaliados</h2>
                <a href="{{ route('admin.clinicas') }}" class="fm-pilula fm-pilula--pequena">Clínicas <x-icone nome="chevron-right" /></a>
            </header>
            @forelse ($locais as $l)
                <a href="{{ route('publico.local', $l) }}" class="adm-mini" target="_blank" rel="noopener">
                    <span class="adm-mini__icone fm-tom-azul"><x-icone :nome="$l->tipo === 'hospital' ? 'hospital' : 'building'" /></span>
                    <div><strong>{{ $l->nome }}</strong><small>{{ $l->cidade }}/{{ $l->uf }} · {{ $l->total_avaliacoes }} {{ $l->total_avaliacoes === 1 ? 'avaliação' : 'avaliações' }}</small></div>
                    <span class="fm-etiqueta fm-etiqueta--ambar">★ {{ \App\Support\Formatador::numero((float) $l->media_avaliacoes, 1) }}</span>
                </a>
            @empty
                <p class="fm-vazio">Nenhum local avaliado ainda.</p>
            @endforelse
        </section>

        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="doctors" /> Médicos mais bem avaliados</h2>
            </header>
            @forelse ($medicos as $m)
                <a href="{{ route('publico.medico', $m) }}" class="adm-mini" target="_blank" rel="noopener">
                    <x-avatar :nome="$m->nome" :foto="$m->foto_url" />
                    <div><strong>{{ $m->nome }}</strong><small>CRM {{ $m->crm }}/{{ $m->uf }} · {{ $m->total_avaliacoes }} {{ $m->total_avaliacoes === 1 ? 'avaliação' : 'avaliações' }}</small></div>
                    <span class="fm-etiqueta fm-etiqueta--ambar">★ {{ \App\Support\Formatador::numero((float) $m->media_avaliacoes, 1) }}</span>
                </a>
            @empty
                <p class="fm-vazio">Nenhum médico avaliado ainda.</p>
            @endforelse
        </section>

        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="users" /> Últimos cadastros</h2>
                <a href="{{ route('admin.usuarios') }}" class="fm-pilula fm-pilula--pequena">Ver todos <x-icone nome="chevron-right" /></a>
            </header>
            @foreach ($ultimosCadastros as $u)
                <div class="adm-mini">
                    <x-avatar :nome="$u->name" :foto="$u->foto_url" />
                    <div><strong>{{ $u->name }}</strong><small>{{ \App\Support\Formatador::PAPEIS[$u->tipo] ?? $u->tipo }}</small></div>
                    <span class="adm-mini__lado">{{ $u->created_at?->isToday() ? $u->created_at->format('H:i') : $u->created_at?->format('d/m') }}</span>
                </div>
            @endforeach
        </section>
    </div>

    <section class="fm-painel" style="margin-top: 18px;">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="star" /> Últimas avaliações</h2>
        </header>
        <p class="fm-campo__ajuda" style="margin-bottom: 6px;">Comentários são públicos desde 05/10: aparecem na página do local ou do médico, com o nome encurtado de quem escreveu.</p>
        @forelse ($ultimasAvaliacoes as $a)
            <div class="adm-mini">
                <x-avatar :nome="$a->usuario->user->name" :foto="$a->usuario->user->foto_url" />
                <div>
                    <strong>{{ str_repeat('★', $a->estrelas) . str_repeat('☆', 5 - $a->estrelas) }} · {{ $a->alvo_nome }}</strong>
                    <small>{{ $a->usuario->user->name }}{{ $a->comentario ? ' — “' . \Illuminate\Support\Str::limit($a->comentario, 100) . '”' : '' }}</small>
                </div>
                <span class="adm-mini__lado">{{ $a->updated_at->format('d/m') }}</span>
            </div>
        @empty
            <p class="fm-vazio">Nenhuma avaliação ainda.</p>
        @endforelse
    </section>

@endsection
