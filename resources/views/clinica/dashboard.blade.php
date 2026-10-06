{{--
    Dashboard da clínica/hospital. Dados: Clinica\DashboardController@index.

    01/10/2026: sem agendamento. Saíram gráfico de consultas, agenda do dia e
    consultas por convênio. Ficou: médicos, unidades (com a nota), médicos por
    especialidade, o que falta para um médico aparecer na busca e as últimas
    avaliações (com comentário: a clínica é uma das telas autorizadas).
--}}
@extends('layouts.painel')

@section('titulo', 'Início')

@php use App\Support\Formatador; @endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">{{ $saudacao }}</h1>
            <p class="fm-subtitulo">Como a sua clínica aparece para quem busca no site.</p>
        </div>

        <div class="fm-data">
            <x-icone nome="calendar" />
            <span>{{ $dataHoje }}</span>
        </div>
    </div>

    <div class="fm-grade fm-grade--cartoes">
        @foreach ($cartoes as $c)
            @include('painel.parciais.cartao', ['c' => $c])
        @endforeach
    </div>

    @if ($semFaixa->isNotEmpty())
        <section class="fm-painel fm-painel--destaque">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="alert" /> Unidades sem faixa de preço</h2>
                <a href="{{ route('clinica.unidades') }}" class="fm-pilula fm-pilula--pequena">Unidades <x-icone nome="chevron-right" /></a>
            </header>
            <p class="fm-campo__ajuda">Escolha de $ a $$$$ em Unidades. Sem faixa, a busca não mostra o preço.</p>
            <ul class="fm-lista">
                @foreach ($semFaixa as $u)
                    <li class="fm-linha"><div class="fm-linha__info"><strong>{{ $u->nome }}</strong></div></li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="fm-duas">
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="building" /> Unidades</h2>
                <a href="{{ route('clinica.unidades') }}" class="fm-pilula fm-pilula--pequena">Gerenciar <x-icone nome="chevron-right" /></a>
            </header>

            @if ($unidades->isNotEmpty())
                <ul class="fm-lista">
                    @foreach ($unidades as $u)
                        <li class="fm-linha fm-linha--nota">
                            <div class="fm-linha__info">
                                <strong><a href="{{ route('publico.local', $u) }}" target="_blank" rel="noopener">{{ $u->nome }}</a></strong>
                                <span>{{ $u->cidade }}/{{ $u->uf }} · {{ $u->total_avaliacoes }} {{ $u->total_avaliacoes === 1 ? 'avaliação' : 'avaliações' }}</span>
                            </div>
                            @if ($u->total_avaliacoes > 0)
                                <span class="fm-etiqueta fm-etiqueta--ambar">★ {{ Formatador::numero((float) $u->media_avaliacoes, 1) }}</span>
                            @else
                                <span class="fm-etiqueta fm-etiqueta--cinza">sem nota</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="fm-vazio">Nenhuma unidade ativa. Cadastre uma em Unidades.</p>
            @endif
        </section>

        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="doctors" /> Médicos por especialidade</h2>
            </header>

            @if (count($porEspecialidade) > 0)
                <ul class="fm-barras">
                    @foreach ($porEspecialidade as $e)
                        <li>
                            <div class="fm-barras__texto">
                                <span>{{ $e['nome'] }}</span>
                                <strong>{{ $e['total'] }}</strong>
                            </div>
                            <div class="fm-barras__trilho">
                                <div class="fm-barras__preenchido" style="width: {{ $e['largura'] }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="fm-vazio">Nenhum médico vinculado ainda.</p>
            @endif
        </section>
    </div>

    <section class="fm-painel">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="star" /> Últimas avaliações</h2>
            <a href="{{ route('clinica.avaliacoes') }}" class="fm-pilula fm-pilula--pequena">Ver todas <x-icone nome="chevron-right" /></a>
        </header>

        @if ($ultimas->isNotEmpty())
            <ul class="fm-lista">
                @foreach ($ultimas as $a)
                    <li class="fm-linha">
                        <x-avatar :nome="$a->usuario->user->name" :foto="$a->usuario->user->foto_url" />
                        <div class="fm-linha__info">
                            <strong>{{ str_repeat('★', $a->estrelas) . str_repeat('☆', 5 - $a->estrelas) }} · {{ $a->alvo_nome }}</strong>
                            <span>{{ $a->comentario ? '“' . \Illuminate\Support\Str::limit($a->comentario, 90) . '”' : 'Sem comentário' }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="fm-vazio">Nenhuma avaliação ainda. Elas aparecem quando um usuário avalia uma unidade ou um médico seu.</p>
        @endif
    </section>


@endsection
