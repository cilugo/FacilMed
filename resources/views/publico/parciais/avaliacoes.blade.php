{{--
    Avaliações públicas de um local ou médico (05/10/2026: decisão do grupo,
    comentário público). Recebe $avaliacoes (até 10, mais recentes) e $total.
    Quem escreveu aparece como "Ana L." (Formatador::nomeCurto), sem foto.
--}}
@php use App\Support\Formatador; @endphp
<section class="caixa avaliacoes-publicas">
    <h2><x-icone nome="star" /> O que dizem os usuários</h2>

    @if ($avaliacoes->isEmpty())
        <p class="texto-pequeno">Ainda não há avaliações. Seja o primeiro a avaliar.</p>
    @else
        <ul class="avaliacoes-publicas__lista">
            @foreach ($avaliacoes as $a)
                <li>
                    <div class="avaliacoes-publicas__topo">
                        <strong>{{ Formatador::nomeCurto($a->usuario?->user?->name) }}</strong>
                        <span class="avaliacoes-publicas__estrelas" aria-label="{{ $a->estrelas }} de 5 estrelas">{{ str_repeat('★', $a->estrelas) }}<span aria-hidden="true">{{ str_repeat('★', 5 - $a->estrelas) }}</span></span>
                        <span class="texto-pequeno">{{ $a->updated_at->format('d/m/Y') }}</span>
                    </div>
                    @if ($a->comentario)
                        <p>{{ $a->comentario }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
        @if ($total > $avaliacoes->count())
            <p class="texto-pequeno">Mostrando as {{ $avaliacoes->count() }} mais recentes de {{ $total }}.</p>
        @endif
    @endif
</section>
