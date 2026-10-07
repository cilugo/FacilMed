{{--
    Perfil público do médico. Dados: PerfilPublicoController@medico.

    Mostra onde ele atende, a faixa de preço ($ a $$$$) de cada lugar (da
    unidade, escolhida pela clínica desde 05/10), a distribuição das notas e,
    desde 05/10, as avaliações com comentário (decisão do grupo).

    01/10/2026: o médico não tem conta (perfil mantido pela clínica) e não há
    agendamento. O usuário logado avalia o médico aqui mesmo.
--}}
@extends('layouts.site')

@section('titulo', $medico->nome)
@section('menu', 'busca')

@php
    // Já vêm só os lugares públicos (PerfilPublicoController).
    $vinculos = $medico->vinculos;
    $ordemDias = ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado', 'domingo'];
    $nomeDia = ['segunda' => 'Segunda', 'terca' => 'Terça', 'quarta' => 'Quarta', 'quinta' => 'Quinta', 'sexta' => 'Sexta', 'sabado' => 'Sábado', 'domingo' => 'Domingo'];
    $totalNotas = max(1, (int) $notas->sum());
@endphp

@section('conteudo')
<div class="container perfil">

    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('busca.index') }}" class="voltar">
        <x-icone nome="arrow-left" /> Voltar
    </a>

    <div class="perfil-grade">
        <div>
            <section class="caixa">
                <div class="perfil-cabeca">
                    <div class="avatar avatar--grande" style="background-color: {{ \App\Support\Formatador::corAvatar($medico->id) }};">
                        {{ \App\Support\Formatador::iniciais($medico->nome) }}
                        @if ($medico->foto_url)
                            <img src="{{ $medico->foto_url }}" alt="Foto de {{ $medico->nome }}" class="avatar__foto" onerror="this.remove()">
                        @endif
                    </div>
                    <div>
                        <h1>{{ $medico->nome }}</h1>
                        <div class="dados">
                            <span>CRM {{ $medico->crm }}/{{ $medico->uf }}</span>
                            @if ($medico->anos_atuacao > 0)
                                <span>{{ $medico->anos_atuacao }} {{ $medico->anos_atuacao === 1 ? 'ano' : 'anos' }} de carreira</span>
                            @endif
                            @if ($medico->total_avaliacoes > 0)
                                <span class="medico-nota"><x-icone nome="star" /> {{ number_format((float) $medico->media_avaliacoes, 1, ',', '') }} ({{ $medico->total_avaliacoes }})</span>
                            @endif
                        </div>
                        <div class="chips">
                            @foreach ($medico->especialidades as $esp)
                                <span class="chip">{{ $esp->nome }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>

                @if ($medico->bio)
                    <p class="perfil-bio">{{ $medico->bio }}</p>
                @endif

                <p class="texto-pequeno" style="margin-top: 14px;">
                    <x-icone nome="badge" style="width: 14px; height: 14px; vertical-align: -2px;" />
                    CRM conferido na base simulada do PointMed.
                </p>
            </section>

            <section class="caixa">
                <h2><x-icone nome="pin" /> Onde atende</h2>

                @forelse ($vinculos as $v)
                    <div class="local">
                        <div class="local__topo">
                            <div>
                                <h3><a href="{{ route('publico.local', $v->local) }}" style="text-decoration: underline;">{{ $v->local->nome }}</a></h3>
                                <p class="local__endereco">{{ $v->local->endereco_completo }}</p>
                                <div class="chips">
                                    <span class="chip chip--cinza">{{ ['hospital' => 'Hospital', 'consultorio' => 'Consultório'][$v->local->tipo] ?? 'Clínica' }}</span>
                                    @if ($v->aceita_particular)<span class="chip">Particular</span>@endif
                                    @if ($v->aceita_convenio)<span class="chip chip--verde">Convênio</span>@endif
                                </div>
                            </div>
                            <a href="{{ route('publico.local', $v->local) }}" class="btn btn-outline">Ver local</a>
                        </div>

                        {{-- 05/10: a faixa é da unidade, escolhida pela clínica. --}}
                        @if ($v->aceita_particular && $v->local->faixa_preco)
                            <p class="texto-pequeno" style="margin-top: 8px;">Consulta particular aqui:
                                <x-faixa-preco :nivel="$v->local->faixa_preco" detalhada /></p>
                        @endif

                        @php $horarios = $v->local->horarios->sortBy(fn ($h) => array_search($h->dia_semana, $ordemDias)); @endphp
                        @if ($horarios->isNotEmpty())
                            <details style="margin-top: 10px;">
                                <summary class="texto-pequeno" style="cursor: pointer;">Horário de funcionamento do local</summary>
                                <div class="horarios-lista">
                                    @foreach ($horarios as $h)
                                        <span>{{ $nomeDia[$h->dia_semana] ?? $h->dia_semana }}</span>
                                        <span>{{ substr($h->abre, 0, 5) }} às {{ substr($h->fecha, 0, 5) }}</span>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    </div>
                @empty
                    <p class="texto-pequeno">Este médico não está atendendo em nenhum local no momento.</p>
                @endforelse
            </section>
        </div>

        <aside>
            <section class="caixa">
                <h2><x-icone nome="shield" /> Convênios aceitos</h2>
                @if ($medico->convenios->where('ativo', true)->isEmpty())
                    <p class="texto-pequeno">Atende só particular.</p>
                @else
                    <div class="chips">
                        @foreach ($medico->convenios->where('ativo', true) as $conv)
                            <span class="chip chip--verde">{{ $conv->nome }}</span>
                        @endforeach
                    </div>
                    <p class="texto-pequeno">Confirme na recepção se o seu plano é aceito no endereço escolhido.</p>
                @endif
            </section>

            <section class="caixa">
                <h2><x-icone nome="star" /> Avaliações</h2>
                @if ($medico->total_avaliacoes > 0)
                    <div class="nota-grande">
                        <strong>{{ number_format((float) $medico->media_avaliacoes, 1, ',', '') }}</strong>
                        <span>de 5 · {{ $medico->total_avaliacoes }} {{ $medico->total_avaliacoes === 1 ? 'avaliação' : 'avaliações' }}</span>
                    </div>
                    <div class="notas">
                        @for ($estrela = 5; $estrela >= 1; $estrela--)
                            @php $qtd = (int) ($notas[$estrela] ?? 0); @endphp
                            <div class="notas__linha">
                                <span>{{ $estrela }} ★</span>
                                <div class="notas__barra"><span style="width: {{ round($qtd / $totalNotas * 100) }}%"></span></div>
                                <span>{{ $qtd }}</span>
                            </div>
                        @endfor
                    </div>
                @else
                    <p class="texto-pequeno">Ainda sem avaliações.</p>
                @endif
            </section>

            @include('publico.parciais.avaliacoes', ['avaliacoes' => $avaliacoes, 'total' => $medico->total_avaliacoes])

            @include('publico.parciais.avaliar', [
                'acao' => route('avaliacoes.medico', $medico),
                'alvo' => 'médico',
                'minhaAvaliacao' => $minhaAvaliacao,
            ])
        </aside>
    </div>
</div>
@endsection
