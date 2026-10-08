{{--
    Formulário "Avalie este local / este médico" (01/10/2026; plano do app, tela 4).

    Recebe: $acao (URL do POST), $alvo ('local' ou 'médico'), $minhaAvaliacao
    (a avaliação que o usuário logado já deu, ou null).

    - Visitante: convite para entrar. Clínica/admin: não avaliam (não aparece).
    - Usuário que já avaliou: o formulário vem preenchido e vira edição
      (uma por usuário em cada local/médico — UNIQUE no banco).
    - Estrelas são radio buttons: funcionam mesmo sem JavaScript.
--}}
@php
    $usuario = auth()->user();
    $rotulos = [1 => 'Ruim', 2 => 'Regular', 3 => 'Bom', 4 => 'Muito bom', 5 => 'Excelente'];
    $notaAtual = (int) old('estrelas', $minhaAvaliacao?->estrelas ?? 0);
@endphp

@if (! $usuario || $usuario->ehUsuario())
    <section class="caixa avaliar" id="avaliar">
        <h2><x-icone nome="star" /> {{ $minhaAvaliacao ? 'Sua avaliação' : 'Avalie este ' . $alvo }}</h2>

        @guest
            <p class="texto-pequeno">
                <a href="{{ route('login') }}" style="text-decoration: underline;">Entre</a> ou
                <a href="{{ route('cadastro.usuario') }}" style="text-decoration: underline;">crie sua conta de usuário</a>
                para avaliar. Sua nota ajuda outras pessoas a escolher.
            </p>
        @else
            <p class="texto-pequeno">
                @if ($minhaAvaliacao)
                    Você avaliou em {{ $minhaAvaliacao->updated_at->format('d/m/Y') }}. Pode mudar quando quiser.
                @else
                    Sua nota ajuda outras pessoas a escolher.
                @endif
            </p>

            <form method="POST" action="{{ $acao }}" class="avaliar__form" x-data="{ nota: {{ $notaAtual }}, rotulos: @js($rotulos) }">
                @csrf

                <fieldset class="avaliar__estrelas">
                    <legend class="texto-pequeno">Nota *</legend>
                    @foreach ($rotulos as $n => $rotulo)
                        <label class="avaliar__estrela" :class="{ 'acesa': nota >= {{ $n }} }" title="{{ $rotulo }}">
                            <input type="radio" name="estrelas" value="{{ $n }}" @checked($notaAtual === $n) x-model.number="nota" required>
                            <span aria-hidden="true">★</span>
                            <span class="sr-only">{{ $n }} {{ $n === 1 ? 'estrela' : 'estrelas' }} — {{ $rotulo }}</span>
                        </label>
                    @endforeach
                    <span class="avaliar__rotulo" x-text="rotulos[nota] ?? ''" aria-hidden="true"></span>
                </fieldset>
                @error('estrelas') <p class="erro-campo">{{ $message }}</p> @enderror

                <label for="comentario-{{ $alvo }}" class="texto-pequeno"><strong>Comentário</strong> (opcional)</label>
                <textarea id="comentario-{{ $alvo }}" name="comentario" rows="3" maxlength="1000"
                          placeholder="Conte como foi o atendimento, a estrutura e o tempo de espera">{{ old('comentario', $minhaAvaliacao?->comentario) }}</textarea>
                <p class="texto-pequeno">A nota e o comentário aparecem para todos, com o seu primeiro nome e a inicial do sobrenome.</p>
                @error('comentario') <p class="erro-campo">{{ $message }}</p> @enderror

                <button type="submit" class="btn btn-primary">{{ $minhaAvaliacao ? 'Atualizar avaliação' : 'Enviar avaliação' }}</button>
            </form>
        @endguest
    </section>
@endif
