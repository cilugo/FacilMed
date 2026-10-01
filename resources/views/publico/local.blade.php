{{--
    Página do local (29/09; refeita em 01/10/2026 com o plano novo do grupo,
    "Tela 4" do PDF). Dados: PerfilPublicoController@local.

    Computador: duas colunas — à esquerda fotos, informações, planos e "Ver
    médicos disponíveis"; à direita a nota em destaque e "Avalie este local".
    Celular: tudo numa coluna, com a avaliação embaixo.

    Nota do local = média de avaliacoes_locais (qualquer paciente logado
    avalia, uma vez). Comentário nunca aparece para o público (AGENTS.md §3);
    o paciente vê só o que ele mesmo escreveu, no quadro de avaliar.
--}}
@extends('layouts.site')

@section('titulo', $local->nome)
@section('menu', 'locais')

@php
    $moeda = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $tipos = ['clinica' => 'Clínica', 'hospital' => 'Hospital', 'consultorio' => 'Consultório'];
    $ordemDias = ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado', 'domingo'];
    $nomeDia = ['segunda' => 'Segunda', 'terca' => 'Terça', 'quarta' => 'Quarta', 'quinta' => 'Quinta', 'sexta' => 'Sexta', 'sabado' => 'Sábado', 'domingo' => 'Domingo'];
    $horarios = $local->horarios->sortBy(fn ($h) => array_search($h->dia_semana, $ordemDias));
    // Mantém a especialidade e a origem (distância) nos links.
    $manter = array_filter(['especialidade' => $slug]) + ($origem['params'] ?? []);
    $rotulos = \App\Models\AvaliacaoLocal::ROTULOS;
    $fotos = $local->fotos;
    $notaInicial = (int) old('estrelas', $minha?->estrelas ?? 0);
@endphp

@section('conteudo')
<div class="container perfil">

    <a href="{{ route('busca.locais', $manter) }}" class="voltar">
        <x-icone nome="arrow-left" /> Voltar aos resultados
    </a>

    <div class="local-grade">
        <div class="local-principal">
            {{-- ============ Galeria ============ --}}
            <section class="galeria" x-data="{ atual: 0 }">
                @if ($fotos->isNotEmpty())
                    <div class="galeria__principal">
                        @foreach ($fotos as $i => $f)
                            <img src="{{ $f->url() }}" alt="Foto {{ $i + 1 }} de {{ $local->nome }}" x-show="atual === {{ $i }}" @if ($i > 0) x-cloak @endif>
                        @endforeach
                        <span class="galeria__contador" x-text="(atual + 1) + ' / {{ $fotos->count() }}'">1 / {{ $fotos->count() }}</span>
                    </div>
                    @if ($fotos->count() > 1)
                        <div class="galeria__miniaturas">
                            @foreach ($fotos as $i => $f)
                                <button type="button" @click="atual = {{ $i }}" :class="{ 'is-ativa': atual === {{ $i }} }" aria-label="Ver foto {{ $i + 1 }}">
                                    <img src="{{ $f->url() }}" alt="" loading="lazy">
                                </button>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="galeria__principal galeria__principal--vazia">
                        <x-icone :nome="$local->tipo === 'hospital' ? 'hospital' : 'building'" />
                        <span>Sem fotos ainda</span>
                    </div>
                @endif
            </section>

            {{-- ============ Cabeçalho ============ --}}
            <section class="local-cabeca">
                <span class="card-local__tipo">{{ $tipos[$local->tipo] ?? $local->tipo }}</span>
                <h1>{{ $local->nome }}</h1>
                <p class="local-nota">
                    @if ($nota)
                        <span class="medico-nota"><x-icone nome="star" /> {{ number_format((float) $nota->media, 1, ',', '') }}</span>
                        <span class="texto-pequeno">{{ $nota->total }} {{ (int) $nota->total === 1 ? 'avaliação' : 'avaliações' }}</span>
                    @else
                        <span class="texto-pequeno">Ainda sem avaliações</span>
                    @endif
                    @if ($distancia !== null)
                        <span class="card-local__distancia"><x-icone nome="localizar" /> {{ \App\Support\Localizacao::formatar($distancia) }} {{ $origem['descricao'] }}</span>
                    @endif
                </p>
                @if ($local->clinica)
                    <p class="texto-pequeno">De <a href="{{ route('publico.clinica', $local->clinica) }}" style="text-decoration: underline;">{{ $local->clinica->nome_fantasia }}</a></p>
                @elseif ($local->medico)
                    <p class="texto-pequeno">Consultório de <a href="{{ route('publico.medico', $local->medico) }}" style="text-decoration: underline;">{{ $local->medico->user->name }}</a></p>
                @endif
            </section>

            <div class="local-cartoes">
                {{-- ============ Endereço e contato ============ --}}
                <section class="caixa local-contato">
                    <p><x-icone nome="pin" /> <span>{{ $local->endereco_completo }}@if ($local->cep) · CEP {{ substr($local->cep, 0, 5) }}-{{ substr($local->cep, 5) }}@endif</span></p>
                    @if ($local->telefone)
                        <p><x-icone nome="phone" /> <a href="tel:{{ $local->telefone }}">{{ \App\Support\Formatador::telefone($local->telefone) }}</a></p>
                    @endif
                    @if ($local->site)
                        <p><x-icone nome="arrow-right" /> <a href="{{ $local->site }}" target="_blank" rel="noopener nofollow">Visitar o site</a></p>
                    @endif
                    @if ($horarios->isNotEmpty())
                        <h3 class="local-subtitulo">Horário de funcionamento</h3>
                        <div class="horarios-lista">
                            @foreach ($horarios as $h)
                                <span>{{ $nomeDia[$h->dia_semana] ?? $h->dia_semana }}</span>
                                <span>{{ substr($h->abre, 0, 5) }} às {{ substr($h->fecha, 0, 5) }}</span>
                            @endforeach
                        </div>
                    @endif
                </section>

                {{-- ============ Planos aceitos ============ --}}
                <section class="caixa">
                    <h2 class="local-subtitulo" style="margin-top: 0;">Planos de saúde aceitos</h2>
                    @if ($convenios->isNotEmpty())
                        <div class="chips">
                            @foreach ($convenios as $conv)
                                <span class="chip chip--cinza">{{ $conv->nome }}</span>
                            @endforeach
                        </div>
                        <p class="texto-pequeno">Confirme na recepção se o seu plano é aceito neste endereço. Os convênios do FacilMed são fictícios.</p>
                    @else
                        <p class="texto-pequeno">Nenhum convênio aceito neste local.</p>
                    @endif
                    @if ($particular)
                        <p class="local-particular"><x-icone nome="check-circle" /> Também atende particular</p>
                    @endif
                </section>
            </div>

            <div class="local-medicos-chamada">
                <a href="{{ route('publico.local.medicos', ['local' => $local] + $manter) }}" class="btn btn-primary btn-grande">
                    <x-icone nome="doctors" /> Ver médicos disponíveis
                </a>
                <span class="texto-pequeno">
                    {{ $totalMedicos }} {{ $totalMedicos === 1 ? 'médico' : 'médicos' }}{{ $escolhida ? ' de ' . $escolhida->nome : '' }} neste local
                </span>
            </div>

            @if ($especialidades->isNotEmpty())
                <section class="caixa">
                    <h2><x-icone nome="stethoscope" /> Especialidades e preços</h2>
                    <table class="tabela-precos">
                        @foreach ($especialidades as $e)
                            <tr>
                                <td><a href="{{ route('publico.local.medicos', ['local' => $local, 'especialidade' => $e->especialidade->slug] + ($origem['params'] ?? [])) }}">{{ $e->especialidade->nome }}</a></td>
                                <td>{{ $e->aPartirDe !== null ? 'a partir de ' . $moeda($e->aPartirDe) : 'só convênio' }}</td>
                            </tr>
                        @endforeach
                    </table>
                </section>
            @endif
        </div>

        {{-- ============ Coluna da direita: nota e avaliação ============ --}}
        <aside class="local-lado">
            <section class="caixa local-nota-grande">
                @if ($nota)
                    <div class="nota-grande">
                        <strong>{{ number_format((float) $nota->media, 1, ',', '') }}</strong>
                        <span class="estrelas-fixas" aria-label="{{ number_format((float) $nota->media, 1, ',', '') }} de 5">
                            @for ($i = 1; $i <= 5; $i++)<span class="{{ $i <= round((float) $nota->media) ? 'cheia' : '' }}">★</span>@endfor
                        </span>
                    </div>
                    <p class="texto-pequeno">Nota média · {{ $nota->total }} {{ (int) $nota->total === 1 ? 'avaliação' : 'avaliações' }}</p>
                    <div class="notas" style="margin-top: 12px;">
                        @for ($i = 5; $i >= 1; $i--)
                            @php $qtd = (int) ($distribuicao[$i] ?? 0); @endphp
                            <div class="nota-barra">
                                <span>{{ $i }} ★</span>
                                <span class="nota-barra__fundo"><span style="width: {{ $nota->total ? round($qtd / $nota->total * 100) : 0 }}%;"></span></span>
                                <span>{{ $qtd }}</span>
                            </div>
                        @endfor
                    </div>
                @else
                    <p class="texto-pequeno">Este local ainda não tem avaliações. Seja o primeiro!</p>
                @endif
            </section>

            <section class="caixa" id="avaliar">
                <h2>Avalie este local</h2>
                @if ($podeAvaliar)
                    <form method="POST" action="{{ route('publico.local.avaliar', $local) }}" x-data="{ nota: {{ $notaInicial }}, sobre: 0, rotulos: @js($rotulos) }">
                        @csrf
                        <input type="hidden" name="estrelas" :value="nota">
                        <p class="texto-pequeno">{{ $minha ? 'Você já avaliou. Pode mudar a nota quando quiser.' : 'Sua nota ajuda outras pessoas a escolher.' }}</p>
                        <div class="estrelas-escolher" @mouseleave="sobre = 0">
                            @for ($i = 1; $i <= 5; $i++)
                                <button type="button" aria-label="{{ $i }} - {{ $rotulos[$i] }}" :class="(sobre || nota) >= {{ $i }} && 'acesa'"
                                        @mouseenter="sobre = {{ $i }}" @click="nota = {{ $i }}">★</button>
                            @endfor
                            <span class="estrelas-escolher__rotulo" x-text="rotulos[sobre || nota] || ''"></span>
                        </div>
                        @error('estrelas') <p class="localizacao-aviso--erro texto-pequeno">{{ $message }}</p> @enderror

                        <label for="comentario" class="local-subtitulo" style="display: block;">Comentário</label>
                        <textarea id="comentario" name="comentario" maxlength="1000" rows="4" class="campo-texto"
                                  placeholder="Conte como foi o atendimento, a estrutura e o tempo de espera">{{ old('comentario', $minha?->comentario) }}</textarea>
                        <p class="texto-pequeno">Só a clínica e a administração leem o comentário. Aqui aparecem apenas as estrelas.</p>
                        @error('comentario') <p class="localizacao-aviso--erro texto-pequeno">{{ $message }}</p> @enderror

                        <button type="submit" class="btn btn-primary btn-bloco" :disabled="!nota" style="margin-top: 12px;">
                            {{ $minha ? 'Atualizar avaliação' : 'Enviar avaliação' }}
                        </button>
                    </form>
                @elseif (! auth()->check())
                    <p class="texto-pequeno">Entre na sua conta de paciente para avaliar este local.</p>
                    <a href="{{ route('login') }}" class="btn btn-outline btn-bloco" style="margin-top: 10px;">Entrar para avaliar</a>
                @else
                    <p class="texto-pequeno">Só pacientes avaliam os locais.</p>
                @endif
            </section>
        </aside>
    </div>
</div>
@endsection
