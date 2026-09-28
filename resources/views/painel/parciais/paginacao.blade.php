{{--
    Paginação simples dos painéis: "‹ Anterior · Página 2 de 5 · Próxima ›".
    Uso: {{ $lista->links('painel.parciais.paginacao') }}
    (A paginação padrão do Laravel usa classes do Tailwind, que o projeto não tem.)
--}}
@if ($paginator->hasPages())
    <nav class="fm-paginacao" aria-label="Paginação">
        @if ($paginator->onFirstPage())
            <span class="fm-botao fm-botao--suave fm-botao--pequeno is-desativado" aria-disabled="true"><x-icone nome="chevron-left" /> Anterior</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="fm-botao fm-botao--suave fm-botao--pequeno" rel="prev"><x-icone nome="chevron-left" /> Anterior</a>
        @endif

        <span class="fm-paginacao__texto">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="fm-botao fm-botao--suave fm-botao--pequeno" rel="next">Próxima <x-icone nome="chevron-right" /></a>
        @else
            <span class="fm-botao fm-botao--suave fm-botao--pequeno is-desativado" aria-disabled="true">Próxima <x-icone nome="chevron-right" /></span>
        @endif
    </nav>
@endif
