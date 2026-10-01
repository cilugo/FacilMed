{{--
    Um item de "Minhas avaliações" (perfil do paciente, 01/10/2026): local ou
    médico. Mostra a nota e o comentário (o autor lê o que escreveu), com
    Editar (abre o formulário aqui mesmo) e Excluir.

    Parâmetros: $chave (única na página), $titulo, $tituloUrl, $detalhe,
    $avaliacao (estrelas, comentario, updated_at), $rotaSalvar + $metodo,
    $rotaExcluir, $quemLe (texto de quem lê o comentário).
--}}
@php
    $esteForm = old('_form') === 'avaliacao-' . $chave;
    $nota = (int) ($esteForm ? old('estrelas', $avaliacao->estrelas) : $avaliacao->estrelas);
@endphp
<li class="fm-avaliacao-minha" x-data="{ editando: {{ $esteForm ? 'true' : 'false' }}, nota: {{ $nota }} }">
    <div class="fm-avaliacao-minha__linha">
        <div class="fm-avaliacao-minha__info">
            <strong>
                @if ($tituloUrl)<a href="{{ $tituloUrl }}">{{ $titulo }}</a>@else{{ $titulo }}@endif
            </strong>
            <span>{{ $detalhe ? $detalhe . ' · ' : '' }}{{ \App\Support\Formatador::dataCurta($avaliacao->updated_at ?? $avaliacao->created_at) }}</span>
        </div>
        <span class="fm-estrelas" aria-label="{{ $avaliacao->estrelas }} de 5 estrelas">
            @for ($i = 1; $i <= 5; $i++)<span class="{{ $i <= $avaliacao->estrelas ? 'is-cheia' : '' }}">★</span>@endfor
        </span>
    </div>
    @if ($avaliacao->comentario)
        <p class="fm-avaliacao-minha__texto" x-show="!editando">“{{ $avaliacao->comentario }}”</p>
    @endif
    <div class="fm-consulta__acoes" x-show="!editando">
        <button type="button" class="fm-botao fm-botao--suave fm-botao--pequeno" @click="editando = true"><x-icone nome="pencil" /> Editar</button>
        <form method="POST" action="{{ $rotaExcluir }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="fm-botao fm-botao--perigo fm-botao--pequeno">Excluir</button>
        </form>
    </div>

    <form method="POST" action="{{ $rotaSalvar }}" class="fm-form fm-form--caixa" x-show="editando" x-cloak>
        @csrf
        @if ($metodo !== 'POST') @method($metodo) @endif
        <input type="hidden" name="_form" value="avaliacao-{{ $chave }}">
        <input type="hidden" name="voltar" value="perfil">
        <input type="hidden" name="estrelas" :value="nota">
        <div class="fm-campo">
            <label>Sua nota *</label>
            <div class="fm-estrelas fm-estrelas--escolher">
                @for ($i = 1; $i <= 5; $i++)
                    <button type="button" aria-label="{{ $i }} {{ $i === 1 ? 'estrela' : 'estrelas' }}" :class="nota >= {{ $i }} && 'is-cheia'" @click="nota = {{ $i }}">★</button>
                @endfor
            </div>
            @if ($esteForm) @error('estrelas') <span class="fm-campo__erro">{{ $message }}</span> @enderror @endif
        </div>
        <div class="fm-campo">
            <label for="comentario-{{ $chave }}">Comentário (opcional)</label>
            <textarea id="comentario-{{ $chave }}" name="comentario" maxlength="1000">{{ $esteForm ? old('comentario') : $avaliacao->comentario }}</textarea>
            <span class="fm-campo__ajuda">{{ $quemLe }}</span>
        </div>
        <div class="fm-form__acoes">
            <button type="button" class="fm-botao fm-botao--suave fm-botao--pequeno" @click="editando = false">Voltar</button>
            <button type="submit" class="fm-botao fm-botao--pequeno">Salvar</button>
        </div>
    </form>
</li>
