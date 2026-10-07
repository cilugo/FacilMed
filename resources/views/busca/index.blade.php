{{--
    Busca de médicos. Dados: BuscaController@index.
    Só aparecem médicos com CRM verificado (Medico::visivel()).
    01/10: faixa de preço ($ a $$$$) no lugar do valor; sem "ver horários".
--}}
@extends('layouts.site')

@section('titulo', 'Encontrar médicos')
@section('menu', 'busca')

@php
    $cidades = \App\Models\Local::where('ativo', true)->select('cidade', 'uf')->distinct()->orderBy('cidade')->get();
    $temFiltro = collect($filtros)->filter()->isNotEmpty();
@endphp

@section('conteudo')

    <section class="pagina-topo">
        <div class="container">
            <h1>Encontre um médico</h1>
            <p>Filtre por especialidade, cidade ou pelo convênio que você tem.</p>

            <form method="GET" action="{{ route('busca.index') }}" class="busca-caixa busca-caixa--larga" role="search">
                <div class="busca-campo">
                    <label for="b-especialidade">Especialidade</label>
                    <select id="b-especialidade" name="especialidade">
                        <option value="">Todas</option>
                        @foreach ($especialidades as $esp)
                            <option value="{{ $esp->slug }}" @selected(($filtros['especialidade'] ?? '') === $esp->slug)>{{ $esp->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="busca-campo">
                    <label for="b-cidade">Cidade</label>
                    <select id="b-cidade" name="cidade">
                        <option value="">Todas</option>
                        @foreach ($cidades as $c)
                            <option value="{{ $c->cidade }}" @selected(($filtros['cidade'] ?? '') === $c->cidade)>{{ $c->cidade }} - {{ $c->uf }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="busca-campo">
                    <label for="b-convenio">Convênio</label>
                    <select id="b-convenio" name="convenio">
                        <option value="">Qualquer um / particular</option>
                        @foreach ($convenios as $conv)
                            <option value="{{ $conv->id }}" @selected((string) ($filtros['convenio'] ?? '') === (string) $conv->id)>{{ $conv->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-grande"><x-icone nome="search" /> Buscar</button>
            </form>
        </div>
    </section>

    <div class="container">
        <div class="resultado-info">
            <span>
                <strong>{{ $medicos->total() }}</strong>
                {{ $medicos->total() === 1 ? 'médico encontrado' : 'médicos encontrados' }}
            </span>
            <span style="display: flex; gap: 14px; align-items: center;">
                <form method="GET" action="{{ route('busca.index') }}">
                    @foreach ($filtros as $chave => $valor)
                        @if ($valor !== null && $valor !== '')<input type="hidden" name="{{ $chave }}" value="{{ $valor }}">@endif
                    @endforeach
                    <label for="ordem" style="font-size: 13px;">Ordenar por</label>
                    <select id="ordem" name="ordem" onchange="this.form.submit()" style="height: 34px; border: 1px solid #d9e2e8; border-radius: 8px; padding: 0 8px; font: inherit; font-size: 13px;">
                        <option value="avaliacao" @selected($ordem === 'avaliacao')>Melhor avaliação</option>
                        <option value="preco" @selected($ordem === 'preco')>Menor faixa de preço</option>
                        <option value="nome" @selected($ordem === 'nome')>Nome</option>
                    </select>
                </form>
                <a href="{{ route('busca.locais', array_filter(['especialidade' => $filtros['especialidade'] ?? null, 'cidade' => $filtros['cidade'] ?? null])) }}" class="limpar-filtros">Ver locais perto de você</a>
                @if ($temFiltro)
                    <a href="{{ route('busca.index') }}" class="limpar-filtros">Limpar filtros</a>
                @endif
            </span>
        </div>

        @if ($medicos->isEmpty())
            <div class="vazio">
                <h2>Nenhum médico com esses filtros</h2>
                <p>Tente outra cidade ou tire o filtro de convênio.</p>
            </div>
        @else
            <div class="lista-medicos">
                @foreach ($medicos as $i => $medico)
                    @php
                        // Já vêm só os lugares públicos (BuscaController).
                        $vinculos = $medico->vinculos;
                        // 05/10: média das faixas das unidades onde ele atende particular.
                        $faixa = \App\Support\FaixaDePreco::mediaDasFaixas(
                            $vinculos->where('aceita_particular', true)->map(fn ($v) => $v->local->faixa_preco));
                        $aceitaConvenio = $vinculos->contains('aceita_convenio', true);
                    @endphp
                    <article class="card-medico">
                        <div class="avatar" style="background-color: {{ \App\Support\Formatador::corAvatar($i + $medicos->firstItem()) }};">
                            {{ \App\Support\Formatador::iniciais($medico->nome) }}
                            @if ($medico->foto_url)
                                <img src="{{ $medico->foto_url }}" alt="" class="avatar__foto" onerror="this.remove()">
                            @endif
                        </div>

                        <div>
                            <h2><a href="{{ route('publico.medico', $medico) }}">{{ $medico->nome }}</a></h2>
                            <span class="crm">CRM {{ $medico->crm }}/{{ $medico->uf }}</span>
                            @if ($medico->total_avaliacoes > 0)
                                · <span class="medico-nota"><x-icone nome="star" /> {{ number_format((float) $medico->media_avaliacoes, 1, ',', '') }}
                                    <span style="font-weight: 400; color: #666;">({{ $medico->total_avaliacoes }})</span></span>
                            @endif

                            <div class="chips">
                                @foreach ($medico->especialidades as $esp)
                                    <span class="chip">{{ $esp->nome }}</span>
                                @endforeach
                            </div>

                            <div class="locais-resumo">
                                @foreach ($vinculos as $v)
                                    <div>
                                        <x-icone :nome="$v->local->tipo === 'hospital' ? 'hospital' : 'pin'" />
                                        <span>
                                            <a href="{{ route('publico.local', $v->local_id) }}" style="text-decoration: underline;">{{ $v->local->nome }}</a>
                                            — {{ $v->local->cidade }}/{{ $v->local->uf }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="card-medico__lado">
                            @if ($faixa)
                                <span class="preco-a-partir">Particular <x-faixa-preco :nivel="$faixa" /></span>
                            @endif
                            @if ($aceitaConvenio)
                                <span class="chip chip--verde"><x-icone nome="shield" /> Aceita convênio</span>
                            @endif
                            <a href="{{ route('publico.medico', $medico) }}" class="btn btn-primary">Ver perfil</a>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($medicos->hasPages())
                <nav class="paginacao" aria-label="Páginas">
                    @if ($medicos->onFirstPage())
                        <span class="desligado">‹</span>
                    @else
                        <a href="{{ $medicos->previousPageUrl() }}" aria-label="Anterior">‹</a>
                    @endif
                    @foreach ($medicos->getUrlRange(1, $medicos->lastPage()) as $pagina => $url)
                        @if ($pagina === $medicos->currentPage())
                            <span class="atual">{{ $pagina }}</span>
                        @else
                            <a href="{{ $url }}">{{ $pagina }}</a>
                        @endif
                    @endforeach
                    @if ($medicos->hasMorePages())
                        <a href="{{ $medicos->nextPageUrl() }}" aria-label="Próxima">›</a>
                    @else
                        <span class="desligado">›</span>
                    @endif
                </nav>
            @endif
        @endif
    </div>

@endsection
