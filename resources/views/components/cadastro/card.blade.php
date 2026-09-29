{{--
    Cartão do cadastro (cabeçalho com ícone + corpo). Componente anônimo do
    Blade: o arquivo em components/cadastro/card.blade.php vira a tag
    <x-cadastro.card>, e o que fica entre as tags chega aqui como $slot.

    <x-cadastro.card titulo="Dados pessoais" icone="pessoa" nota="opcional">
        ...campos...
    </x-cadastro.card>

    É um <fieldset>: leitor de tela anuncia o título do grupo antes dos campos.
--}}
@props(['titulo', 'icone', 'nota' => null, 'grade' => true])
<fieldset {{ $attributes->merge(['class' => 'card']) }}>
    <legend class="card__cabecalho">
        <span class="card__icone">@include('cadastro._icone', ['nome' => $icone])</span>
        <span>
            <span class="card__titulo">{{ $titulo }}</span>
            @if ($nota) <span class="card__nota">{{ $nota }}</span> @endif
        </span>
    </legend>
    <div class="card__corpo {{ $grade ? 'grade' : '' }}">
        {{ $slot }}
    </div>
</fieldset>
