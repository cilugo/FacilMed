{{--
    Página do local (29/09/2026, plano do app). Dados: PerfilPublicoController@local.

    Uma unidade de clínica ou um hospital: endereço, contato, horários, nota
    média, formas de pagamento com a faixa de preço da unidade ($ a $$$$,
    escolhida pela clínica desde 05/10), especialidades e médicos disponíveis.

    01/10/2026: sem agendamento. O usuário avalia o local aqui mesmo
    (publico/parciais/avaliar). 05/10: comentários públicos
    (publico/parciais/avaliacoes).
--}}
@extends('layouts.site')

@section('titulo', $local->nome)
@section('menu', 'locais')

@php
    $tipos = ['clinica' => 'Clínica', 'hospital' => 'Hospital', 'consultorio' => 'Consultório'];
    $ordemDias = ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado', 'domingo'];
    $nomeDia = ['segunda' => 'Segunda', 'terca' => 'Terça', 'quarta' => 'Quarta', 'quinta' => 'Quinta', 'sexta' => 'Sexta', 'sabado' => 'Sábado', 'domingo' => 'Domingo'];
    $horarios = $local->horarios->sortBy(fn ($h) => array_search($h->dia_semana, $ordemDias));
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
                            @endif
                            @if ($distancia !== null)
                                <span class="distancia"><x-icone nome="localizar" /> {{ \App\Support\Localizacao::formatar($distancia) }} {{ $origem['descricao'] }}</span>
                            @endif
                        </div>
                        <p class="local-nota">
                            @if ($local->total_avaliacoes > 0)
                                <span class="medico-nota"><x-icone nome="star" /> {{ number_format((float) $local->media_avaliacoes, 1, ',', '') }}</span>
                                <span class="texto-pequeno">{{ $local->total_avaliacoes }} {{ $local->total_avaliacoes === 1 ? 'avaliação' : 'avaliações' }}</span>
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
                            <a href="{{ route('publico.local', ['local' => $local, 'especialidade' => $e->slug] + $manter) }}#medicos"
                               class="chip {{ $slug === $e->slug ? '' : 'chip--cinza' }}">{{ $e->nome }}</a>
                        @endforeach
                    </div>
                @endif

                @if ($medicos->isEmpty())
                    <p class="texto-pequeno">
                        Nenhum médico {{ $escolhida ? 'de ' . $escolhida->nome . ' ' : '' }}disponível neste local no momento.
                    </p>
                @else
                    <div class="lista-local-medicos">
                        @foreach ($medicos as $i => $v)
                            <div class="local-medico">
                                <span class="avatar" style="background-color: {{ \App\Support\Formatador::corAvatar($i) }};">
                                    {{ \App\Support\Formatador::iniciais($v->medico->nome) }}
                                    @if ($v->medico->foto_url)
                                        <img src="{{ $v->medico->foto_url }}" alt="" class="avatar__foto" onerror="this.remove()">
                                    @endif
                                </span>
                                <div>
                                    <strong><a href="{{ route('publico.medico', $v->medico) }}">{{ $v->medico->nome }}</a></strong>
                                    <span class="crm">CRM {{ $v->medico->crm }}/{{ $v->medico->uf }}</span>
                                    @if ($v->medico->total_avaliacoes > 0)
                                        · <span class="medico-nota"><x-icone nome="star" /> {{ number_format((float) $v->medico->media_avaliacoes, 1, ',', '') }}
                                            <span style="font-weight: 400; color: #666;">({{ $v->medico->total_avaliacoes }})</span></span>
                                    @endif
                                    @if ($v->medico->anos_atuacao > 0)
                                        <span class="texto-pequeno"> · {{ $v->medico->anos_atuacao }} {{ $v->medico->anos_atuacao === 1 ? 'ano' : 'anos' }} de carreira</span>
                                    @endif
                                    <div class="chips">
                                        @foreach ($v->especialidadesOferecidas() as $esp)
                                            <span class="chip">{{ $esp->nome }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                <a href="{{ route('publico.medico', $v->medico) }}" class="btn btn-outline">Ver perfil</a>
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
                @if ($particular && $local->faixa_preco)
                    <p>Faixa de preço: <x-faixa-preco :nivel="$local->faixa_preco" detalhada /></p>
                @endif

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
                    <div class="chips">
                        @foreach ($especialidades as $esp)
                            <a href="{{ route('publico.local', ['local' => $local, 'especialidade' => $esp->slug]) }}#medicos" class="chip">{{ $esp->nome }}</a>
                        @endforeach
                    </div>
                </section>
            @endif

            @include('publico.parciais.avaliacoes', ['avaliacoes' => $avaliacoes, 'total' => $local->total_avaliacoes])

            @include('publico.parciais.avaliar', [
                'acao' => route('avaliacoes.local', $local),
                'alvo' => 'local',
                'minhaAvaliacao' => $minhaAvaliacao,
            ])
        </aside>
    </div>
</div>
@endsection
