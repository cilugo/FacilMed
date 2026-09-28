{{--
    Perfil público do médico. Dados: PerfilPublicoController@medico.

    Mostra onde ele atende, o preço de cada especialidade em cada lugar e
    a distribuição das notas. Comentários das avaliações NÃO aparecem:
    são privados (AGENTS.md §6).
--}}
@extends('layouts.site')

@section('titulo', $medico->user->name)
@section('menu', 'busca')

@php
    $moeda = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    // Já vêm só os lugares que recebem agendamento (PerfilPublicoController).
    $vinculos = $medico->vinculos;
    $ordemDias = ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado', 'domingo'];
    $nomeDia = ['segunda' => 'Segunda', 'terca' => 'Terça', 'quarta' => 'Quarta', 'quinta' => 'Quinta', 'sexta' => 'Sexta', 'sabado' => 'Sábado', 'domingo' => 'Domingo'];
    $usuario = auth()->user();
    $podeAgendar = ! $usuario || $usuario->tipo === 'paciente';
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
                        {{ \App\Support\Formatador::iniciais($medico->user->name) }}
                    </div>
                    <div>
                        <h1>{{ $medico->user->name }}</h1>
                        <div class="dados">
                            <span>CRM {{ $medico->crm }}/{{ $medico->uf }}</span>
                            @if ($medico->anos_atuacao > 0)
                                <span>{{ $medico->anos_atuacao }} anos de atuação</span>
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
                    CRM conferido na base simulada do FacilMed.
                </p>
            </section>

            <section class="caixa">
                <h2><x-icone nome="pin" /> Onde atende</h2>

                @forelse ($vinculos as $v)
                    <div class="local">
                        <div class="local__topo">
                            <div>
                                <h3>{{ $v->local->nome }}</h3>
                                <p class="local__endereco">{{ $v->local->endereco_completo }}</p>
                                <div class="chips">
                                    <span class="chip chip--cinza">{{ $v->local->tipo === 'hospital' ? 'Hospital' : 'Clínica' }}</span>
                                    @if ($v->aceita_particular)<span class="chip">Particular</span>@endif
                                    @if ($v->aceita_convenio)<span class="chip chip--verde">Convênio</span>@endif
                                </div>
                            </div>
                            @if ($podeAgendar)
                                <a href="{{ route('agendamento.horario', $v) }}" class="btn btn-primary">Agendar aqui</a>
                            @endif
                        </div>

                        @php $precos = $v->precosOferecidos(); @endphp
                        @if ($precos->isNotEmpty())
                            <table class="tabela-precos">
                                @foreach ($precos as $preco)
                                    <tr>
                                        <td>{{ $preco->especialidade->nome }}</td>
                                        <td>{{ $moeda($preco->valor) }}</td>
                                    </tr>
                                @endforeach
                            </table>
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
                    <p class="texto-pequeno">Este médico ainda não informou onde atende.</p>
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
                    <p class="texto-pequeno" style="margin-top: 12px;">Só quem teve consulta realizada pode avaliar.</p>
                @else
                    <p class="texto-pequeno">Ainda sem avaliações.</p>
                @endif
            </section>

            @unless ($podeAgendar)
                <div class="site-aviso site-aviso--info" style="margin-top: 20px;">
                    Você entrou como {{ \App\Support\Formatador::PAPEIS[$usuario->tipo] ?? $usuario->tipo }}. Só contas de paciente agendam consultas.
                </div>
            @endunless
        </aside>
    </div>
</div>
@endsection
