{{--
    Médico → Avaliações. Dados: Medico\AvaliacaoController@index (README §7.1).

    Uma das três telas onde o COMENTÁRIO aparece (as outras são a da clínica
    e a do admin). Em qualquer tela pública, só a nota (AGENTS.md §3).
--}}
@extends('layouts.painel')

@section('titulo', 'Avaliações')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    use App\Support\Formatador;
    $media = $medico->media_avaliacoes ? Formatador::numero((float) $medico->media_avaliacoes, 1) : null;
    $total = (int) $medico->total_avaliacoes;
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Avaliações</h1>
            <p class="fm-subtitulo">O que os pacientes acharam das consultas realizadas.</p>
        </div>
    </div>

    <div class="fm-duas" style="margin-top: 18px;">
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="star" /> Sua nota</h2>
            </header>
            @if ($media)
                <div class="fm-nota">
                    <strong class="fm-nota__valor">{{ $media }}</strong>
                    <span class="fm-nota__escala">de 5 estrelas</span>
                    <span class="fm-nota__total">{{ $total }} {{ $total === 1 ? 'avaliação' : 'avaliações' }}</span>
                </div>
            @else
                <p class="fm-vazio">Você ainda não recebeu avaliações.</p>
            @endif
        </section>

        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="shield" /> Quem vê o quê</h2>
            </header>
            <p class="fm-campo__ajuda" style="font-size: 14px;">
                A <strong>nota</strong> aparece no seu perfil público e na busca. Os <strong>comentários</strong> são
                privados: só você, as clínicas onde a consulta aconteceu e o administrador da plataforma leem.
                Só quem teve consulta realizada com você pode avaliar.
            </p>
        </section>
    </div>

    <section class="fm-painel">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="list" /> Todas as avaliações</h2>
        </header>

        @if ($avaliacoes->isNotEmpty())
            <ul class="fm-lista">
                @foreach ($avaliacoes as $a)
                    <li class="fm-avaliacao">
                        <div class="fm-avaliacao__topo">
                            <span class="fm-estrelas" aria-label="{{ $a->estrelas }} de 5 estrelas">
                                @for ($i = 1; $i <= 5; $i++)
                                    <span class="{{ $i <= $a->estrelas ? 'is-cheia' : '' }}" aria-hidden="true">★</span>
                                @endfor
                            </span>
                            <span class="fm-campo__ajuda">{{ Formatador::dataCurta($a->created_at) }}</span>
                        </div>
                        <p class="fm-avaliacao__quem">
                            <strong>{{ $a->paciente->user->name }}</strong>
                            · {{ $a->consulta?->especialidade?->nome }}
                        </p>
                        @if ($a->comentario)
                            <p class="fm-avaliacao__texto">“{{ $a->comentario }}”</p>
                        @else
                            <p class="fm-campo__ajuda">Sem comentário.</p>
                        @endif
                    </li>
                @endforeach
            </ul>

            {{ $avaliacoes->links('painel.parciais.paginacao') }}
        @else
            <p class="fm-vazio">As avaliações aparecem aqui depois que um paciente avalia uma consulta realizada.</p>
        @endif
    </section>

@endsection
