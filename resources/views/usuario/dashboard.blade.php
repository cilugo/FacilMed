{{--
    Dashboard do usuário. Dados prontos de
    App\Http\Controllers\Usuario\DashboardController.

    01/10/2026: sem agendamento. Saíram "Próximos atendimentos", "Consultas
    realizadas" e "Agendar consulta". No lugar: busca rápida de locais perto
    do usuário, as avaliações que ele fez e o plano de saúde.

    NÃO existe aqui "Resumo da sua saúde", exames, medicamentos nem
    documentos: o sistema não tem esse dado e AGENTS.md §3 proíbe qualquer
    leitura clínica. Não acrescente.
--}}
@extends('layouts.painel')

@section('titulo', 'Início')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('javas/localizacao.js') }}" defer></script>
@endpush

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Olá, {{ $saudacao }}!</h1>
            <p class="fm-subtitulo">Encontre clínicas e hospitais perto de você.</p>
        </div>

        <div class="fm-data">
            <x-icone nome="calendar" />
            <span>{{ $dataHoje }}</span>
        </div>
    </div>

    {{-- Busca rápida: leva para /locais (BuscaController@locais) --}}
    <section class="fm-painel">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="pin" /> Buscar perto de você</h2>
        </header>

        <form method="GET" action="{{ route('busca.locais') }}" class="fm-filtros" role="search">
            <div class="fm-campo">
                <label for="d-especialidade">Especialidade</label>
                <select id="d-especialidade" name="especialidade">
                    <option value="">Todas</option>
                    @foreach ($especialidades as $esp)
                        <option value="{{ $esp->slug }}">{{ $esp->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-campo">
                <label for="d-cidade">Cidade</label>
                <select id="d-cidade" name="cidade">
                    <option value="">Escolha a cidade</option>
                    @foreach ($cidades as $c)
                        <option value="{{ $c->cidade }}">{{ $c->cidade }} - {{ $c->uf }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fm-campo">
                <label for="d-convenio">Convênio</label>
                <select id="d-convenio" name="convenio">
                    <option value="">Qualquer um / particular</option>
                    @foreach ($convenios as $conv)
                        <option value="{{ $conv->id }}" @selected($convenioDoPlano === $conv->id)>{{ $conv->nome }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" class="fm-botao fm-botao--suave fm-botao--pequeno" data-localizacao="{{ route('busca.locais') }}">
                <x-icone nome="pin" /> Usar minha localização
            </button>
            <button type="submit" class="fm-botao fm-botao--pequeno"><x-icone nome="search" /> Buscar</button>
        </form>
    </section>

    {{-- Uma coluna por cartão: sem buraco à direita (notas do Lucas, 05/10). --}}
    <div class="fm-grade fm-grade--cartoes" style="--fm-cartoes: {{ count($cartoes) }}">
        @foreach ($cartoes as $c)
            @include('painel.parciais.cartao', ['c' => $c])
        @endforeach
    </div>

    <div class="fm-duas">

        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="star" /> Suas últimas avaliações</h2>
                <a href="{{ route('usuario.avaliacoes') }}" class="fm-pilula fm-pilula--pequena">
                    Ver todas <x-icone nome="chevron-right" />
                </a>
            </header>

            @if ($ultimas->isNotEmpty())
                <ul class="fm-lista">
                    @foreach ($ultimas as $a)
                        <li class="fm-linha">
                            <span class="fm-linha__icone fm-tom-roxo"><x-icone :nome="$a->local_id ? 'building' : 'user'" /></span>
                            <div class="fm-linha__info">
                                <strong>{{ $a->alvo_nome }}</strong>
                                <span>{{ str_repeat('★', $a->estrelas) . str_repeat('☆', 5 - $a->estrelas) }} · {{ $a->updated_at->format('d/m/Y') }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="fm-vazio">Você ainda não avaliou nenhum local ou médico. A avaliação fica na página de cada um.</p>
            @endif
        </section>

        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="card" /> Meu plano de saúde</h2>
                <a href="{{ route('usuario.planos') }}" class="fm-pilula fm-pilula--pequena">
                    Ver planos <x-icone nome="chevron-right" />
                </a>
            </header>

            @if (count($carteirinhas) > 0)
                <ul class="fm-lista">
                    @foreach ($carteirinhas as $cp)
                        <li class="fm-linha">
                            <span class="fm-linha__icone fm-tom-rosa"><x-icone nome="shield" /></span>
                            <div class="fm-linha__info">
                                <strong>{{ $cp['nome'] }}</strong>
                            </div>
                            <span class="fm-etiqueta fm-etiqueta--{{ $cp['tom'] }}">{{ $cp['status'] }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="fm-dica">
                    <x-icone nome="lightbulb" />
                    <span>Na busca rápida acima, o convênio do seu plano já vem marcado.</span>
                </p>
            @else
                <div class="fm-vazio fm-vazio--acao">
                    <p>Cadastre seu plano para filtrar os locais que aceitam o seu convênio.</p>
                    <a href="{{ route('usuario.planos') }}" class="fm-botao fm-botao--suave">Cadastrar plano</a>
                </div>
            @endif
        </section>
    </div>


@endsection
