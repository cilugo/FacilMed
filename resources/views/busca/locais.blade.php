{{--
    Locais perto de você (29/09/2026, plano do app). Dados: BuscaController@locais.

    Clínicas e hospitais no ar, do mais perto para o mais longe. 01/10: filtro
    de convênio e faixa de preço ($ a $$$$) no lugar do valor. De onde medir: a posição do navegador (botão "Usar minha
    localização", public/javas/localizacao.js) ou o centro da cidade escolhida.
    Distância em linha reta e coordenadas aproximadas (config/localizacao.php).
--}}
@extends('layouts.site')

@section('titulo', 'Locais perto de você')
@section('menu', 'locais')

@php
    $temFiltro = collect($filtros)->filter()->isNotEmpty() || $origem;
    $tipos = ['clinica' => 'Clínica', 'hospital' => 'Hospital', 'consultorio' => 'Consultório'];
    // Leva a especialidade e a origem para a página do local.
    $paramsLink = array_filter(['especialidade' => $filtros['especialidade']]) + ($origem['params'] ?? []);
    $convenioEscolhido = $convenios->firstWhere('id', (int) $filtros['convenio']);
@endphp

@push('scripts')
    <script src="{{ asset('javas/localizacao.js') }}" defer></script>
@endpush

@section('conteudo')

    <section class="pagina-topo">
        <div class="container">
            <h1>Locais perto de você</h1>
            <p>Clínicas e hospitais, do mais perto para o mais longe.</p>

            <form method="GET" action="{{ route('busca.locais') }}" class="busca-caixa busca-caixa--local" role="search">
                <div class="busca-campo">
                    <label for="l-especialidade">Especialidade</label>
                    <select id="l-especialidade" name="especialidade">
                        <option value="">Todas</option>
                        @foreach ($especialidades as $esp)
                            <option value="{{ $esp->slug }}" @selected($filtros['especialidade'] === $esp->slug)>{{ $esp->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="busca-campo">
                    <label for="l-cidade">Perto de qual cidade?</label>
                    <select id="l-cidade" name="cidade">
                        <option value="">{{ ($origem['params']['lat'] ?? null) !== null ? 'Minha localização' : 'Escolha a cidade' }}</option>
                        @foreach ($cidades as $c)
                            <option value="{{ $c->cidade }}" @selected($filtros['cidade'] === $c->cidade)>{{ $c->cidade }} - {{ $c->uf }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="busca-campo">
                    <label for="l-convenio">Convênio</label>
                    <select id="l-convenio" name="convenio">
                        <option value="">Qualquer um / particular</option>
                        @foreach ($convenios as $conv)
                            <option value="{{ $conv->id }}" @selected((string) $filtros['convenio'] === (string) $conv->id)>{{ $conv->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" class="btn btn-outline btn-grande" data-localizacao="{{ route('busca.locais') }}">
                    <x-icone nome="localizar" /> Usar minha localização
                </button>
                <button type="submit" class="btn btn-primary btn-grande"><x-icone nome="search" /> Buscar</button>

                {{-- Preenchidos pelo botão de localização. Escolher uma cidade tem prioridade. --}}
                <input type="hidden" name="lat" value="{{ $origem['params']['lat'] ?? '' }}">
                <input type="hidden" name="lng" value="{{ $origem['params']['lng'] ?? '' }}">

                <p class="localizacao-aviso" data-localizacao-status>
                    Ao usar sua localização, o navegador pergunta se você permite o acesso a ela. Ela só serve para
                    ordenar os locais por distância e não fica salva.
                </p>
            </form>
        </div>
    </section>

    <div class="container">
        <div class="resultado-info">
            <span>
                <strong>{{ $resultados->count() }}</strong>
                {{ $resultados->count() === 1 ? 'local encontrado' : 'locais encontrados' }}
                @if ($convenioEscolhido)
                    · aceitando {{ $convenioEscolhido->nome }}
                @endif
                @if ($origem)
                    · distância aproximada, em linha reta, {{ $origem['descricao'] }}
                @else
                    · em ordem alfabética. Use sua localização ou escolha a cidade para ver a distância.
                @endif
            </span>
            <span style="display: flex; gap: 14px; align-items: center;">
                <a href="{{ route('busca.index', array_filter(['especialidade' => $filtros['especialidade']])) }}" class="limpar-filtros">Buscar por médico</a>
                @if ($temFiltro)
                    <a href="{{ route('busca.locais') }}" class="limpar-filtros">Limpar filtros</a>
                @endif
            </span>
        </div>

        @if ($resultados->isEmpty())
            <div class="vazio">
                <h2>Nenhum local com esses filtros</h2>
                <p>Tente outra especialidade{{ $convenioEscolhido ? ' ou tire o filtro de convênio' : '' }}.</p>
            </div>
        @else
            <div class="lista-medicos">
                @foreach ($resultados as $r)
                    @php
                        $local = $r->local;
                        // Já vêm só os vínculos públicos (que oferecem a especialidade e aceitam o convênio filtrados).
                        $vinculos = $local->vinculos;
                        $especialidadesAqui = $vinculos->flatMap->especialidadesOferecidas()->unique('id')->sortBy('nome');
                        // 05/10: a faixa é da unidade, escolhida pela clínica.
                        $faixa = $vinculos->contains('aceita_particular', true) ? $local->faixa_preco : null;
                        $aceitaConvenio = $vinculos->contains('aceita_convenio', true);
                        $link = route('publico.local', ['local' => $local] + $paramsLink);
                    @endphp
                    <article class="card-medico">
                        <div class="avatar" style="background-color: #183e9f;">
                            <x-icone :nome="$local->tipo === 'hospital' ? 'hospital' : 'building'" style="width: 30px; height: 30px;" />
                        </div>

                        <div>
                            <h2><a href="{{ $link }}">{{ $local->nome }}</a></h2>
                            <span class="crm">
                                {{ $tipos[$local->tipo] ?? $local->tipo }}
                                @if ($local->clinica)
                                    · {{ $local->clinica->nome_fantasia }}
                                @endif
                            </span>
                            @if ($local->total_avaliacoes > 0)
                                · <span class="medico-nota"><x-icone nome="star" /> {{ number_format((float) $local->media_avaliacoes, 1, ',', '') }}
                                    <span style="font-weight: 400; color: #666;">({{ $local->total_avaliacoes }})</span></span>
                            @endif

                            <div class="locais-resumo" style="margin-top: 8px;">
                                <div><x-icone nome="pin" /><span>{{ $local->endereco_completo }}</span></div>
                                <div><x-icone nome="doctors" /><span>{{ $vinculos->count() }} {{ $vinculos->count() === 1 ? 'médico disponível' : 'médicos disponíveis' }}</span></div>
                            </div>

                            <div class="chips">
                                @foreach ($especialidadesAqui as $esp)
                                    <span class="chip">{{ $esp->nome }}</span>
                                @endforeach
                            </div>
                        </div>

                        <div class="card-medico__lado">
                            @if ($r->distancia !== null)
                                <span class="distancia"><x-icone nome="localizar" /> {{ \App\Support\Localizacao::formatar($r->distancia) }}</span>
                            @elseif ($origem)
                                <span class="texto-pequeno">Distância indisponível</span>
                            @endif
                            @if ($faixa)
                                <span class="preco-a-partir">Particular <x-faixa-preco :nivel="$faixa" /></span>
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
