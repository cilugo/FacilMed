{{--
    Clínica → Avaliações. Dados: Clinica\AvaliacaoController@index.

    01/10/2026: o usuário avalia o LOCAL ou o MÉDICO direto (sem consulta).
    Aqui aparecem as notas das unidades da clínica e dos médicos que atendem
    nelas, COM comentário — só a clínica, o autor e o admin leem.
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
            <p class="fm-subtitulo">O que os usuários acharam das suas unidades e dos seus médicos.</p>
        </div>
    </div>

    <div class="fm-duas" style="margin-top: 18px;">
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="building" /> Por unidade</h2>
            </header>
            <ul class="fm-lista">
                @foreach ($unidades as $u)
                    <li class="fm-linha fm-linha--nota">
                        <div class="fm-linha__info">
                            <strong>{{ $u->nome }}</strong>
                            <span>{{ $u->total_avaliacoes }} {{ $u->total_avaliacoes === 1 ? 'avaliação' : 'avaliações' }}</span>
                        </div>
                        @if ($u->total_avaliacoes > 0)
                            <span class="fm-etiqueta fm-etiqueta--ambar">★ {{ Formatador::numero((float) $u->media_avaliacoes, 1) }}</span>
                        @else
                            <span class="fm-etiqueta fm-etiqueta--cinza">sem nota</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="doctors" /> Por médico</h2>
            </header>
            @if ($medicos->isNotEmpty())
                <ul class="fm-lista">
                    @foreach ($medicos as $m)
                        <li class="fm-linha fm-linha--nota">
                            <div class="fm-linha__info">
                                <strong>{{ $m->nome }}</strong>
                                <span>{{ $m->total_avaliacoes }} {{ $m->total_avaliacoes === 1 ? 'avaliação' : 'avaliações' }}</span>
                            </div>
                            <span class="fm-etiqueta fm-etiqueta--ambar">★ {{ Formatador::numero((float) $m->media_avaliacoes, 1) }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="fm-vazio">Nenhum médico avaliado ainda.</p>
            @endif
        </section>
    </div>

    <section class="fm-painel">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="list" /> Comentários</h2>
            <span class="fm-painel__periodo">{{ $total }} {{ $total === 1 ? 'avaliação' : 'avaliações' }}</span>
        </header>

        <p class="fm-campo__ajuda" style="margin-bottom: 6px;">Os comentários aparecem também na página pública da unidade e do médico, com o nome encurtado de quem escreveu.</p>

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
                            <span class="fm-campo__ajuda">{{ Formatador::dataCurta($a->updated_at) }}</span>
                        </div>
                        <p class="fm-avaliacao__quem" style="display: flex; gap: 8px; align-items: center;">
                            <x-avatar :nome="$a->usuario->user->name" :foto="$a->usuario->user->foto_url" />
                            <span>
                                <strong>{{ $a->usuario->user->name }}</strong>
                                sobre {{ $a->local_id ? 'a unidade ' . $a->local?->nome : $a->medico?->nome }}
                            </span>
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
            <p class="fm-vazio">As avaliações aparecem aqui quando um usuário avaliar uma unidade ou um médico da clínica.</p>
        @endif
    </section>

@endsection
