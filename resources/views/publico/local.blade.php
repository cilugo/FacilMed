{{--
    Página do local (29/09/2026, plano do app). Dados: PerfilPublicoController@local.

    Uma unidade de clínica, um hospital ou o consultório de um médico: endereço,
    contato, horários, nota média, formas de pagamento, especialidades com preço
    e os médicos disponíveis, cada um com "Ver horários" (o agendamento de sempre).

    Nota do local = média das avaliações das consultas feitas aqui. Comentário
    nunca aparece (AGENTS.md §3).
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
    $usuario = auth()->user();
    $podeAgendar = ! $usuario || $usuario->tipo === 'paciente';
    // Mantém a origem (distância) ao trocar o filtro de especialidade.
    $manter = $origem['params'] ?? [];
@endphp

@section('conteudo')
<div class="container perfil">

    <a href="{{ route('busca.locais', array_filter(['especialidade' => $slug]) + $manter) }}" class="voltar">
        <x-icone nome="arrow-left" /> Locais perto de você
    </a>

    <div class="perfil-grade">
        <div>
            <section class="caixa">
                <div class="perfil-cabeca">
                    <div class="avatar avatar--grande" style="background-color: #183e9f;">
                        <x-icone :nome="$local->tipo === 'hospital' ? 'hospital' : 'building'" style="width: 40px; height: 40px;" />
                    </div>
                    <div>
                        <h1>{{ $local->nome }}</h1>
                        <div class="dados">
                            <span>{{ $tipos[$local->tipo] ?? $local->tipo }}</span>
                            @if ($local->clinica)
                                <span><a href="{{ route('publico.clinica', $local->clinica) }}" style="text-decoration: underline;">{{ $local->clinica->nome_fantasia }}</a></span>
                            @elseif ($local->medico)
                                <span>Consultório de <a href="{{ route('publico.medico', $local->medico) }}" style="text-decoration: underline;">{{ $local->medico->user->name }}</a></span>
                            @endif
                            @if ($distancia !== null)
                                <span class="distancia"><x-icone nome="localizar" /> {{ \App\Support\Localizacao::formatar($distancia) }} {{ $origem['descricao'] }}</span>
                            @endif
                        </div>
                        <p class="local-nota">
                            @if ($nota)
                                <span class="medico-nota"><x-icone nome="star" /> {{ number_format((float) $nota->media, 1, ',', '') }}</span>
                                <span class="texto-pequeno">{{ $nota->total }} {{ (int) $nota->total === 1 ? 'avaliação' : 'avaliações' }} de consultas feitas aqui</span>
                            @else
                                <span class="texto-pequeno">Ainda sem avaliações.</span>
                            @endif
                        </p>
                    </div>
                </div>
                <a href="#medicos" class="btn btn-primary" style="margin-top: 18px;"><x-icone nome="doctors" /> Ver médicos disponíveis</a>
            </section>

            <section class="caixa">
                <h2><x-icone nome="pin" /> Endereço e contato</h2>
                <p>{{ $local->endereco_completo }}</p>
                @if ($local->cep)
                    <p class="local__endereco">CEP {{ substr($local->cep, 0, 5) }}-{{ substr($local->cep, 5) }}</p>
                @endif
                @if ($local->telefone)
                    <p class="local__endereco">Telefone: {{ \App\Support\Formatador::telefone($local->telefone) }}</p>
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

            <section class="caixa" id="medicos">
                <h2><x-icone nome="doctors" /> Médicos disponíveis</h2>

                @if ($especialidades->count() > 1)
                    <div class="chips" style="margin-top: 0;">
                        <a href="{{ route('publico.local', ['local' => $local] + $manter) }}#medicos" class="chip {{ $slug ? 'chip--cinza' : '' }}">Todas</a>
                        @foreach ($especialidades as $e)
                            <a href="{{ route('publico.local', ['local' => $local, 'especialidade' => $e->especialidade->slug] + $manter) }}#medicos"
                               class="chip {{ $slug === $e->especialidade->slug ? '' : 'chip--cinza' }}">{{ $e->especialidade->nome }}</a>
                        @endforeach
                    </div>
                @endif

                @if ($medicos->isEmpty())
                    <p class="texto-pequeno">
                        Nenhum médico {{ $escolhida ? 'de ' . $escolhida->nome . ' ' : '' }}disponível para agendamento neste local no momento.
                    </p>
                @else
                    <div class="lista-local-medicos">
                        @foreach ($medicos as $i => $v)
                            @php $precos = $v->precosOferecidos(); @endphp
                            <div class="local-medico">
                                <span class="avatar" style="background-color: {{ \App\Support\Formatador::corAvatar($i) }};">
                                    {{ \App\Support\Formatador::iniciais($v->medico->user->name) }}
                                </span>
                                <div>
                                    <strong><a href="{{ route('publico.medico', $v->medico) }}">{{ $v->medico->user->name }}</a></strong>
                                    <span class="crm">CRM {{ $v->medico->crm }}/{{ $v->medico->uf }}</span>
                                    @if ($v->medico->total_avaliacoes > 0)
                                        · <span class="medico-nota"><x-icone nome="star" /> {{ number_format((float) $v->medico->media_avaliacoes, 1, ',', '') }}
                                            <span style="font-weight: 400; color: #666;">({{ $v->medico->total_avaliacoes }})</span></span>
                                    @endif
                                    <div class="chips">
                                        @foreach ($precos as $p)
                                            <span class="chip">{{ $p->especialidade->nome }}@if ($v->aceita_particular) · {{ $moeda($p->valor) }}@endif</span>
                                        @endforeach
                                    </div>
                                </div>
                                @if ($podeAgendar)
                                    <a href="{{ route('agendamento.horario', $v) }}" class="btn btn-primary">Ver horários</a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <aside>
            <section class="caixa">
                <h2><x-icone nome="money" /> Formas de pagamento</h2>
                <p>{{ $particular ? 'Atende particular.' : 'Não atende particular.' }}</p>

                @if ($convenios->isNotEmpty())
                    <p class="local-subtitulo">Convênios aceitos pelos médicos daqui</p>
                    <div class="chips">
                        @foreach ($convenios as $conv)
                            <span class="chip chip--verde">{{ $conv->nome }}</span>
                        @endforeach
                    </div>
                    <p class="texto-pequeno">Confirme na recepção se o seu plano é aceito neste endereço.</p>
                @else
                    <p class="texto-pequeno">Nenhum convênio aceito neste local.</p>
                @endif
            </section>

            @if ($especialidades->isNotEmpty())
                <section class="caixa">
                    <h2><x-icone nome="stethoscope" /> Especialidades</h2>
                    <table class="tabela-precos">
                        @foreach ($especialidades as $e)
                            <tr>
                                <td>{{ $e->especialidade->nome }}</td>
                                <td>{{ $e->aPartirDe !== null ? 'a partir de ' . $moeda($e->aPartirDe) : 'só convênio' }}</td>
                            </tr>
                        @endforeach
                    </table>
                </section>
            @endif
        </aside>
    </div>
</div>
@endsection
