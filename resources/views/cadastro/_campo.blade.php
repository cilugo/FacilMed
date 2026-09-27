{{--
    Um campo de formulário com rótulo e erro. Evita repetir o mesmo
    bloco 40 vezes nos três cadastros.
      nome, rotulo, tipo (text), obrigatorio (false), ajuda, atributos (string extra)
--}}
@php $tipo = $tipo ?? 'text'; $erroCampo = $errors->first($nome); @endphp
<div class="fm-campo {{ $erroCampo ? 'fm-campo--erro' : '' }} {{ $largo ?? false ? 'fm-campo--largo' : '' }}">
    <label for="c-{{ $nome }}">{{ $rotulo }}{{ ($obrigatorio ?? false) ? ' *' : '' }}</label>
    @if ($tipo === 'textarea')
        <textarea id="c-{{ $nome }}" name="{{ $nome }}" {!! $atributos ?? '' !!}>{{ old($nome) }}</textarea>
    @else
        <input id="c-{{ $nome }}" name="{{ $nome }}" type="{{ $tipo }}"
               @if ($tipo !== 'password') value="{{ old($nome) }}" @endif
               @if ($obrigatorio ?? false) required @endif {!! $atributos ?? '' !!}>
    @endif
    @if (! empty($ajuda)) <span class="fm-campo__ajuda">{{ $ajuda }}</span> @endif
    @if ($erroCampo) <span class="fm-campo__erro">{{ $erroCampo }}</span> @endif
</div>
