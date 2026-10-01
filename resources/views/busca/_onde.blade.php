{{--
    Formulário "o que e onde" da busca de clínicas (01/10/2026, plano novo do
    grupo). Usado na home e no topo de "Clínicas perto de você".

    Parâmetros: $especialidades, $filtros (especialidade, raio...), $origem
    (null ou o de Localizacao::origem), $raioPadrao (a home usa 5 km: "na home
    é possível pesquisar até 5 km", anotação do grupo).

    "Onde você está?": o botão do navegador (public/javas/localizacao.js) OU o
    campo "CEP ou cidade". A posição que já veio da URL vai nos campos
    escondidos - digitar algo novo em "CEP ou cidade" passa na frente dela.
--}}
@php
    $filtros = $filtros ?? [];
    $raioAtual = $filtros['raio'] ?? ($raioPadrao ?? null);
    $params = $origem['params'] ?? [];
@endphp
<form method="GET" action="{{ route('busca.locais') }}" class="busca-caixa busca-caixa--onde" role="search">
    <div class="busca-campo busca-campo--esp">
        <label for="b-especialidade">Especialidade</label>
        <select id="b-especialidade" name="especialidade">
            <option value="">Todas as especialidades</option>
            @foreach ($especialidades as $esp)
                <option value="{{ $esp->slug }}" @selected(($filtros['especialidade'] ?? null) === $esp->slug)>{{ $esp->nome }}</option>
            @endforeach
        </select>
    </div>

    <div class="busca-campo busca-campo--onde">
        <label for="b-onde">Onde você está?</label>
        <input id="b-onde" name="onde" maxlength="80" autocomplete="postal-code"
               placeholder="{{ $origem ? 'Agora: ' . ($origem['descricao'] === 'de você' ? 'sua localização' : str_replace(['do centro de ', 'do CEP '], ['', 'CEP '], $origem['descricao'])) . ' — ou digite outro' : 'Digite o CEP ou a cidade' }}">
    </div>

    <div class="busca-campo busca-campo--raio">
        <label for="b-raio">Até</label>
        <select id="b-raio" name="raio">
            @foreach (\App\Support\Localizacao::RAIOS as $km)
                <option value="{{ $km }}" @selected((int) $raioAtual === $km)>{{ $km }} km</option>
            @endforeach
            <option value="" @selected(! $raioAtual)>Qualquer distância</option>
        </select>
    </div>

    <button type="submit" class="btn btn-primary btn-grande"><x-icone nome="search" /> Buscar clínicas próximas</button>

    <div class="busca-localizacao">
        <button type="button" class="busca-localizacao__botao" data-localizacao="{{ route('busca.locais') }}">
            <x-icone nome="localizar" /> Usar minha localização
        </button>
        <span data-localizacao-status class="localizacao-aviso">O navegador pergunta se você permite. A posição só ordena os resultados e não fica salva.</span>
    </div>

    {{-- A posição que já está valendo (CEP, cidade ou navegador), para trocar só a especialidade ou o raio. --}}
    @foreach (['lat', 'lng', 'cep', 'cidade'] as $campo)
        @if (isset($params[$campo]))
            <input type="hidden" name="{{ $campo }}" value="{{ $params[$campo] }}">
        @endif
    @endforeach
    @foreach (['plano', 'particular'] as $campo)
        @if (! empty($filtros[$campo]))
            <input type="hidden" name="{{ $campo }}" value="1">
        @endif
    @endforeach
    @if (! empty($filtros['convenio']))
        <input type="hidden" name="convenio" value="{{ $filtros['convenio'] }}">
    @endif
</form>
