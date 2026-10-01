{{--
    Clínicas perto de você — a busca principal do plano novo (01/10/2026).
    Dados: BuscaController@locais. Desenho: "Tela 3 · Resultados" do PDF do grupo.

    Clínicas e hospitais que recebem agendamento, do mais perto para o mais
    longe. De onde medir: CEP (vira coordenada: ViaCEP + Nominatim), a posição
    do navegador ou o centro da cidade. Filtros rápidos: raio (5/10/20 km),
    "Aceita meu plano" (carteirinha do paciente logado) e "Só particular".
    Distância em linha reta e aproximada — a tela diz isso.
--}}
@extends('layouts.site')

@section('titulo', 'Clínicas perto de você')
@section('menu', 'locais')

@php
    $moeda = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $tipos = ['clinica' => 'Clínica', 'hospital' => 'Hospital', 'consultorio' => 'Consultório'];
    $base = array_filter([
        'especialidade' => $filtros['especialidade'],
        'raio'          => $filtros['raio'],
        'plano'         => $filtros['plano'] ? 1 : null,
        'particular'    => $filtros['particular'] ? 1 : null,
        'convenio'      => $filtros['convenio'],
    ]) + ($origem['params'] ?? []);
    // Link que troca um filtro e mantém o resto.
    $com = fn (array $troca) => route('busca.locais', array_filter(array_merge($base, $troca), fn ($v) => $v !== null && $v !== ''));
    // Leva a especialidade e a origem para a página do local.
    $paramsLink = array_filter(['especialidade' => $filtros['especialidade']]) + ($origem['params'] ?? []);
@endphp

@push('scripts')
    <script src="{{ asset('javas/localizacao.js') }}" defer></script>
@endpush

@section('conteudo')

    <section class="pagina-topo">
        <div class="container">
            <h1>{{ $escolhida ? $escolhida->nome : 'Clínicas e hospitais' }} perto de você</h1>
            <p>Diga o que procura e onde está. A lista vai do mais perto ao mais longe.</p>

            @include('busca._onde', ['origem' => $origem, 'filtros' => $filtros])

            @if ($aviso)
                <p class="site-aviso site-aviso--erro" role="alert">{{ $aviso }}</p>
            @endif
        </div>
    </section>

    <div class="container">
        {{-- Filtros rápidos (desenho: "Mais próximos", "Aceita meu plano", "Só particular") --}}
        <nav class="filtros-rapidos" aria-label="Filtros rápidos">
            @if ($origem)
                @foreach (\App\Support\Localizacao::RAIOS as $km)
                    <a href="{{ $com(['raio' => $km]) }}" class="filtro {{ $filtros['raio'] === $km ? 'is-ativo' : '' }}">Até {{ $km }} km</a>
                @endforeach
                <a href="{{ $com(['raio' => null]) }}" class="filtro {{ $filtros['raio'] ? '' : 'is-ativo' }}">Mais próximos</a>
            @endif

            @if ($ehPaciente && $temCarteirinha)
                <a href="{{ $com(['plano' => $filtros['plano'] ? null : 1, 'convenio' => null]) }}" class="filtro {{ $filtros['plano'] ? 'is-ativo' : '' }}">
                    <x-icone nome="card" /> Aceita meu plano
                </a>
            @elseif ($ehPaciente)
                <a href="{{ route('paciente.planos') }}" class="filtro" title="Cadastre a sua carteirinha para filtrar pelo seu plano">
                    <x-icone nome="card" /> Aceita meu plano
                </a>
            @endif

            <a href="{{ $com(['particular' => $filtros['particular'] ? null : 1]) }}" class="filtro {{ $filtros['particular'] ? 'is-ativo' : '' }}">Só particular</a>

            {{-- Sem carteirinha (ou sem login): escolher o convênio na lista. --}}
            @unless ($ehPaciente && $temCarteirinha && $filtros['plano'])
                <form method="GET" action="{{ route('busca.locais') }}" class="filtro-convenio">
                    @foreach (array_diff_key($base, ['convenio' => 1, 'plano' => 1]) as $k => $v)
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endforeach
                    <label for="f-convenio" class="sr-only">Convênio</label>
                    <select id="f-convenio" name="convenio" onchange="this.form.submit()">
                        <option value="">Qualquer convênio</option>
                        @foreach ($convenios as $c)
                            <option value="{{ $c->id }}" @selected($filtros['convenio'] === $c->id)>{{ $c->nome }}</option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" class="btn btn-outline">Filtrar</button></noscript>
                </form>
            @endunless
        </nav>

        <div class="resultado-info">
            <span>
                <strong>{{ $resultados->count() }}</strong>
                {{ $resultados->count() === 1 ? 'local encontrado' : 'locais encontrados' }}
                @if ($origem)
                    {{ $filtros['raio'] ? 'até ' . $filtros['raio'] . ' km' : '' }}
                    · distância aproximada, em linha reta, {{ $origem['descricao'] }}
                @else
                    · em ordem alfabética. Use sua localização ou digite o CEP para ver a distância.
                @endif
            </span>
            <span style="display: flex; gap: 14px; align-items: center;">
                <a href="{{ route('busca.index', array_filter(['especialidade' => $filtros['especialidade']])) }}" class="limpar-filtros">Buscar por médico</a>
                @if (array_filter($filtros) || $origem)
                    <a href="{{ route('busca.locais') }}" class="limpar-filtros">Limpar filtros</a>
                @endif
            </span>
        </div>

        @if ($resultados->isEmpty())
            <div class="vazio">
                <h2>Nenhum local com esses filtros</h2>
                @if ($origem && $filtros['raio'] && $foraDoRaio > 0)
                    <p>Há {{ $foraDoRaio }} {{ $foraDoRaio === 1 ? 'local' : 'locais' }} mais longe que {{ $filtros['raio'] }} km.</p>
                    <p style="margin-top: 12px;">
                        @foreach (\App\Support\Localizacao::RAIOS as $km)
                            @if ($km > $filtros['raio'])
                                <a href="{{ $com(['raio' => $km]) }}" class="btn btn-outline">Ver até {{ $km }} km</a>
                            @endif
                        @endforeach
                        <a href="{{ $com(['raio' => null]) }}" class="btn btn-primary">Qualquer distância</a>
                    </p>
                @else
                    <p>Tente outra especialidade ou tire um filtro.</p>
                @endif
            </div>
        @else
            <div class="lista-locais">
                @foreach ($resultados as $r)
                    @php
                        $local = $r->local;
                        // Já vêm só os vínculos que servem para esta busca.
                        $vinculos = $local->vinculos;
                        $especialidadesAqui = $vinculos->flatMap->especialidadesOferecidas()->unique('id')->sortBy('nome');
                        $menorPreco = $vinculos->where('aceita_particular', true)->flatMap->precosOferecidos()->min('valor');
                        $aceitaConvenio = $vinculos->contains('aceita_convenio', true);
                        $link = route('publico.local', ['local' => $local] + $paramsLink);
                        $foto = $local->fotos->first();
                    @endphp
                    <article class="card-local">
                        <a href="{{ $link }}" class="card-local__foto" tabindex="-1" aria-hidden="true">
                            @if ($foto)
                                <img src="{{ $foto->url() }}" alt="" loading="lazy">
                            @else
                                <x-icone :nome="$local->tipo === 'hospital' ? 'hospital' : 'building'" />
                            @endif
                        </a>

                        <div class="card-local__info">
                            <div class="card-local__topo">
                                <span class="card-local__tipo">{{ $tipos[$local->tipo] ?? $local->tipo }}</span>
                                @if ($r->distancia !== null)
                                    <span class="card-local__distancia"><x-icone nome="localizar" /> {{ \App\Support\Localizacao::formatar($r->distancia) }}</span>
                                @elseif ($origem)
                                    <span class="texto-pequeno">Distância indisponível</span>
                                @endif
                            </div>
                            <h2><a href="{{ $link }}">{{ $local->nome }}</a></h2>
                            <p class="card-local__endereco">{{ $local->endereco_completo }}</p>
                            <p class="local-nota">
                                @if ($r->nota)
                                    <span class="medico-nota"><x-icone nome="star" /> {{ number_format((float) $r->nota->media, 1, ',', '') }}</span>
                                    <span class="texto-pequeno">({{ $r->nota->total }} {{ (int) $r->nota->total === 1 ? 'avaliação' : 'avaliações' }})</span>
                                @else
                                    <span class="texto-pequeno">Ainda sem avaliações</span>
                                @endif
                                <span class="texto-pequeno">· {{ $vinculos->count() }} {{ $vinculos->count() === 1 ? 'médico disponível' : 'médicos disponíveis' }}</span>
                            </p>
                            <div class="chips">
                                @foreach ($especialidadesAqui as $esp)
                                    <span class="chip">{{ $esp->nome }}</span>
                                @endforeach
                            </div>
                        </div>

                        <div class="card-medico__lado">
                            @if ($menorPreco !== null)
                                <span class="preco-a-partir">Particular a partir de <strong>{{ $moeda($menorPreco) }}</strong></span>
                            @endif
                            @if ($aceitaConvenio)
                                <span class="chip chip--verde"><x-icone nome="shield" /> Aceita convênio</span>
                            @endif
                            <a href="{{ $link }}" class="btn btn-primary">Ver local</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>

@endsection
