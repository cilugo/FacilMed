<span class="fm-cartao__icone fm-tom-{{ $c['tom'] }}">
    <x-icone :nome="$c['icone']" />
</span>

<div class="fm-cartao__texto">
    <span class="fm-cartao__rotulo">{{ $c['rotulo'] }}</span>
    <strong class="fm-cartao__valor">{{ $c['valor'] }}</strong>

    @if (! empty($c['variacao']) || ! empty($c['nota']))
        <span class="fm-cartao__nota">
            @if (! empty($c['variacao']))
                <span class="fm-variacao fm-variacao--{{ $c['variacao']['tom'] }}">
                    <span class="fm-variacao__seta">
                        @if ($c['variacao']['sentido'] === 'up')
                            <x-icone nome="arrow-up" />
                        @elseif ($c['variacao']['sentido'] === 'down')
                            <x-icone nome="arrow-down" />
                        @elseif ($c['variacao']['sentido'] === 'alerta')
                            <x-icone nome="alert" />
                        @else
                            <x-icone nome="minus" />
                        @endif
                    </span>
                    {{ $c['variacao']['texto'] }}
                </span>
            @endif

            @if (! empty($c['nota']))
                <span class="fm-cartao__legenda">{{ $c['nota'] }}</span>
            @endif
        </span>
    @endif

    @if (! empty($c['link']))
        <span class="fm-cartao__link">
            {{ $c['link'] }}
            <x-icone nome="chevron-right" />
        </span>
    @endif
</div>
