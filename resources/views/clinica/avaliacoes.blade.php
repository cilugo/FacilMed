{{--
    Clínica → Avaliações. Dados: Clinica\AvaliacaoController@index (README §7.2).

    Avaliações das consultas feitas nas unidades da clínica, COM comentário —
    é uma das três telas autorizadas a ver (médico, clínica, admin).
--}}
@extends('layouts.painel')

@section('titulo', 'Avaliações')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php use App\Support\Formatador; @endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Avaliações</h1>
            <p class="fm-subtitulo">O que os pacientes acharam das consultas nas suas unidades.</p>
        </div>
    </div>

    <div class="fm-duas" style="margin-top: 18px;">
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="star" /> Nota da clínica</h2>
            </header>
            @if ($total > 0)
                <div class="fm-nota">
                    <strong class="fm-nota__valor">{{ Formatador::numero($media, 1) }}</strong>
                    <span class="fm-nota__escala">de 5 estrelas</span>
                    <span class="fm-nota__total">{{ $total }} {{ $total === 1 ? 'avaliação' : 'avaliações' }}</span>
                </div>
            @else
                <p class="fm-vazio">Ainda não há avaliações.</p>
            @endif
        </section>

        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="doctors" /> Por médico</h2>
            </header>
            @if ($porMedico->isNotEmpty())
                <ul class="fm-lista">
                    @foreach ($porMedico->sortByDesc('media') as $pm)
                        <li class="fm-linha fm-linha--nota">
                            <div class="fm-linha__info">
                                <strong>{{ $pm->medico->user->name }}</strong>
                                <span>{{ $pm->total }} {{ (int) $pm->total === 1 ? 'avaliação' : 'avaliações' }}</span>
                            </div>
                            <span class="fm-etiqueta fm-etiqueta--ambar">★ {{ Formatador::numero((float) $pm->media, 1) }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="fm-vazio">Sem avaliações por enquanto.</p>
            @endif
        </section>
    </div>

    <section class="fm-painel">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="list" /> Comentários</h2>
        </header>

        <p class="fm-campo__ajuda" style="margin-bottom: 6px;">Comentários são privados: só a clínica, o médico avaliado e o
            administrador leem. No site aparece apenas a nota.</p>

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
                            sobre {{ $a->medico->user->name }}
                            · {{ $a->consulta?->especialidade?->nome }}
                            · {{ $a->consulta?->vinculo?->local?->nome }}
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
