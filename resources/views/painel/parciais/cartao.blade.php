{{--
    Cartão de indicador (ícone colorido + rótulo + número grande).

    Recebe $c com: icone, tom, rotulo, valor e, se quiser, variacao,
    nota, link (texto de rodapé) e url (se vier, o cartão inteiro é link).
    Quem monta esse array é o controller — aqui só se imprime.
--}}
@if (! empty($c['url']))
    <a href="{{ $c['url'] }}" class="fm-cartao fm-cartao--link">
        @include('painel.parciais.cartao-corpo', ['c' => $c])
    </a>
@else
    <div class="fm-cartao">
        @include('painel.parciais.cartao-corpo', ['c' => $c])
    </div>
@endif
