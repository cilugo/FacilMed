{{--
    Usuário → Minhas avaliações (01/10/2026; plano do app, tela 6).
    Dados: Usuario\AvaliacaoController@index.

    O usuário vê o PRÓPRIO comentário (precisa dele para lembrar e editar).
    Para editar, volta à página do local ou do médico: o formulário de lá já
    vem preenchido. Excluir passa pela AvaliacaoPolicy (só o autor).
--}}
@extends('layouts.painel')

@section('titulo', 'Minhas avaliações')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Minhas avaliações</h1>
            <p class="fm-subtitulo">As notas que você deu a locais e médicos.</p>
        </div>
        <a href="{{ route('busca.locais') }}" class="fm-botao"><x-icone nome="pin" /> Buscar locais</a>
    </div>

    <p class="fm-dica">
        <x-icone nome="lightbulb" />
        <span>A <strong>nota</strong> e o <strong>comentário</strong> aparecem para todos na página do local ou do médico, com o seu primeiro nome e a inicial do sobrenome.</span>
    </p>

    <section class="fm-painel" style="margin-top: 18px;">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="star" /> Avaliações</h2>
            <span class="fm-painel__periodo">{{ $avaliacoes->total() }}</span>
        </header>

        @if ($avaliacoes->isNotEmpty())
            <ul class="fm-lista">
                @foreach ($avaliacoes as $a)
                    @php
                        $url = $a->local_id ? route('publico.local', $a->local_id) : route('publico.medico', $a->medico_id);
                    @endphp
                    <li class="fm-conta">
                        <div class="fm-conta__linha">
                            <span class="fm-linha__icone fm-tom-roxo"><x-icone :nome="$a->local_id ? 'building' : 'user'" /></span>
                            <div class="fm-conta__info">
                                <strong>{{ $a->alvo_nome }}</strong>
                                <span aria-label="{{ $a->estrelas }} de 5 estrelas">
                                    {{ str_repeat('★', $a->estrelas) . str_repeat('☆', 5 - $a->estrelas) }}
                                    · {{ $a->local_id ? 'Local' : 'Médico' }} · {{ $a->updated_at->format('d/m/Y') }}
                                </span>
                            </div>
                            <div class="fm-conta__acoes">
                                <a href="{{ $url }}#avaliar" class="fm-botao fm-botao--suave fm-botao--pequeno">Editar</a>
                                <form method="POST" action="{{ route('usuario.avaliacoes.excluir', $a) }}"
                                      onsubmit="return confirm('Excluir esta avaliação?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="fm-botao fm-botao--perigo fm-botao--pequeno">Excluir</button>
                                </form>
                            </div>
                        </div>
                        @if ($a->getAttribute('comentario'))
                            <p class="fm-conta__extra fm-campo__ajuda">“{{ $a->getAttribute('comentario') }}”</p>
                        @endif
                    </li>
                @endforeach
            </ul>

            {{ $avaliacoes->links('painel.parciais.paginacao') }}
        @else
            <p class="fm-vazio">Você ainda não fez nenhuma avaliação. Abra a página de um local ou de um médico para avaliar.</p>
        @endif
    </section>

@endsection
