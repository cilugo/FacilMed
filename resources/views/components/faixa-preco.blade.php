{{--
    Faixa de preço da consulta particular, de $ a $$$$ (01/10/2026).
    O usuário vê a faixa, nunca o valor exato. Limites em App\Support\FaixaDePreco.

    Uso:  <x-faixa-preco :nivel="$nivel" />            ($$ e o intervalo ao passar o mouse)
          <x-faixa-preco :nivel="$nivel" detalhada />  ($$ · R$ 200 a R$ 350)
--}}
@props(['nivel' => null, 'detalhada' => false])

@if ($nivel)
    @php $descricao = \App\Support\FaixaDePreco::descricao($nivel); @endphp
    <span {{ $attributes->merge(['class' => 'faixa-preco']) }} title="Consulta particular: {{ $descricao }}">
        <span class="faixa-preco__simbolo" aria-label="Faixa de preço {{ $nivel }} de 4">
            <strong>{{ str_repeat('$', $nivel) }}</strong><span class="faixa-preco__resto" aria-hidden="true">{{ str_repeat('$', 4 - $nivel) }}</span>
        </span>
        @if ($detalhada)
            <span class="faixa-preco__texto">· {{ $descricao }}</span>
        @endif
    </span>
@endif
