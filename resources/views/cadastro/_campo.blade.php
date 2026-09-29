{{--
    Um campo do cadastro no visual do protótipo da Mariana: rótulo, caixa
    com ícone, e embaixo a ajuda ou o erro que o SERVIDOR devolveu.

    Parâmetros:
      nome, rotulo            obrigatórios
      tipo                    text (padrão) | email | tel | password | date | number | textarea | select
      icone                   nome em cadastro/_icone (padrão: nenhum)
      obrigatorio             mostra o * e põe required (conforto visual; quem valida é o FormRequest)
      inteiro                 ocupa a linha inteira da grade
      mascara                 cpf | cnpj | telefone | cep (public/javas/cadastro.js)
      opcoes                  para select: [valor => rótulo]
      vazio                   para select: texto da opção vazia (padrão: "Selecione")
      padrao                  valor inicial quando não há old()
      ajuda                   texto pequeno embaixo (some quando há erro)
      atributos               string com atributos extras (placeholder, maxlength, autocomplete...)
--}}
@php
    $tipo = $tipo ?? 'text';
    $id = 'c-' . $nome;
    $erroCampo = $errors->first($nome);
    $valor = old($nome, $padrao ?? '');
    $obrigatorio = $obrigatorio ?? false;
    // Leitor de tela lê o erro (se houver) ou a ajuda junto com o campo.
    $descrito = $erroCampo ? $id . '-erro' : (! empty($ajuda) ? $id . '-ajuda' : null);
@endphp
<div class="campo {{ ($inteiro ?? false) ? 'campo--inteiro' : '' }} {{ $erroCampo ? 'campo--invalido' : '' }}">
    <label for="{{ $id }}">{{ $rotulo }}{{ $obrigatorio ? ' *' : '' }}</label>

    <div class="entrada {{ $tipo === 'select' ? 'entrada--select' : '' }} {{ $tipo === 'textarea' ? 'entrada--texto' : '' }}">
        @if (! empty($icone)) @include('cadastro._icone', ['nome' => $icone]) @endif

        @if ($tipo === 'textarea')
            <textarea id="{{ $id }}" name="{{ $nome }}" @if ($erroCampo) aria-invalid="true" @endif @if ($descrito) aria-describedby="{{ $descrito }}" @endif {!! $atributos ?? '' !!}>{{ $valor }}</textarea>
        @elseif ($tipo === 'select')
            <select id="{{ $id }}" name="{{ $nome }}" @if ($obrigatorio) required @endif @if ($erroCampo) aria-invalid="true" @endif @if ($descrito) aria-describedby="{{ $descrito }}" @endif {!! $atributos ?? '' !!}>
                <option value="">{{ $vazio ?? 'Selecione' }}</option>
                @foreach ($opcoes as $chave => $texto)
                    <option value="{{ $chave }}" @selected((string) $valor === (string) $chave)>{{ $texto }}</option>
                @endforeach
            </select>
            @include('cadastro._icone', ['nome' => 'seta-baixo', 'classe' => 'icone--seta'])
        @else
            <input id="{{ $id }}" name="{{ $nome }}" type="{{ $tipo }}"
                   @if ($tipo !== 'password') value="{{ $valor }}" @endif
                   @if (! empty($mascara)) data-mascara="{{ $mascara }}" @endif
                   @if ($obrigatorio) required @endif
                   @if ($erroCampo) aria-invalid="true" @endif @if ($descrito) aria-describedby="{{ $descrito }}" @endif
                   {!! $atributos ?? '' !!}>
            @if ($tipo === 'password')
                <button type="button" class="mostrar-senha" aria-label="Mostrar {{ mb_strtolower($rotulo) }}" aria-pressed="false" data-alvo="{{ $id }}">
                    @include('cadastro._icone', ['nome' => 'olho'])
                </button>
            @endif
        @endif
    </div>

    @if ($erroCampo)
        <p class="erro" id="{{ $id }}-erro">{{ $erroCampo }}</p>
    @elseif (! empty($ajuda))
        <p class="ajuda" id="{{ $id }}-ajuda">{{ $ajuda }}</p>
    @else
        <p class="erro" aria-hidden="true"></p>
    @endif
</div>
