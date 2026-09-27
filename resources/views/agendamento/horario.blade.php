{{--
    Agendamento, passo 1 de 2: escolher especialidade, dia, horário e forma de pagamento.

    Duas origens (AgendamentoController):
    - escolherHorario: paciente escolheu um MÉDICO + endereço (vínculo).
      Os dias com vaga já vêm prontos ($diasComVaga); outra data é
      buscada na hora em /agendar/{vinculo}/horarios (JSON).
    - porEspecialidade: paciente escolheu só a ESPECIALIDADE numa clínica
      e o AlocadorDeMedico encaixou um médico ($alocadoPelaClinica).
      Trocar a data recarrega a página, porque o médico pode mudar.

    Os horários mostrados são a mesma lista que o servidor confere ao
    gravar (CalculadoraDeHorarios) — a tela nunca inventa horário.
--}}
@extends('layouts.painel')

@section('titulo', 'Agendar consulta')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/agendamento.css') }}">
@endpush

@php
    use App\Support\Formatador;
    use Carbon\Carbon;

    $modoClinica = $alocadoPelaClinica ?? false;
    $precos = $vinculo->precos->where('ativo', true)->pluck('valor', 'especialidade_id')->map(fn ($v) => (float) $v);

    $dias = $modoClinica
        ? (isset($dataEscolhida) && ! empty($horarios) ? [$dataEscolhida => $horarios] : [])
        : ($diasComVaga ?? []);

    $rotulosDias = collect(array_keys($dias))->mapWithKeys(function ($d) {
        $c = Carbon::parse($d);
        return [$d => [
            'semana' => Formatador::DIAS_CURTOS[$c->dayOfWeek],
            'dia'    => $c->format('d'),
            'mes'    => Formatador::MESES_CURTOS[$c->month - 1] ?? $c->format('m'),
            'longo'  => Formatador::dataExtensa($c),
        ]];
    });

    $primeiroDia = array_key_first($dias);
    $minimo = now()->addHours((int) config('agendamento.antecedencia_minima_horas'))->toDateString();
    $maximo = now()->addDays((int) $janelaDias)->toDateString();

    $config = [
        'modoClinica'   => $modoClinica,
        'dias'          => (object) $dias,
        'rotulos'       => $rotulosDias,
        'dia'           => $primeiroDia,
        'esp'           => old('especialidade_id', $especialidades->first()?->id),
        'precos'        => $precos,
        'nomesEsp'      => $especialidades->pluck('nome', 'id'),
        'forma'         => $vinculo->aceita_particular ? 'particular' : 'convenio',
        'urlHorarios'   => route('agendamento.horarios-disponiveis', $vinculo),
        'urlClinica'    => $modoClinica ? route('agendamento.especialidade', [$clinica, $especialidades->first()]) : null,
    ];
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Agendar consulta</h1>
            <p class="fm-subtitulo">Passo 1 de 2 — escolha o dia e o horário.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="fm-flash fm-flash--erro" role="alert">{{ $errors->first() }}</div>
    @endif

    @if (request('remarcar'))
        <p class="fm-dica">
            <x-icone nome="info" />
            <span>Você está <strong>remarcando</strong> uma consulta. O horário antigo só é liberado quando você confirmar o novo.</span>
        </p>
    @endif

    @if ($modoClinica)
        <p class="fm-dica">
            <x-icone nome="lightbulb" />
            <span>A <strong>{{ $clinica->nome_fantasia }}</strong> encaixou você com o médico que tem vaga nesse dia.
                Se trocar a data, o médico pode mudar.</span>
        </p>
    @endif

    <div class="ag-grade" x-data="agendamento(@js($config))">

        <section class="fm-painel">
            {{-- Médico e local --}}
            <div class="ag-medico">
                <span class="fm-avatar fm-avatar--grande">{{ Formatador::iniciais($vinculo->medico->user->name) }}</span>
                <div>
                    <strong>{{ $vinculo->medico->user->name }}</strong>
                    <span>{{ $vinculo->local->nome }} · {{ $vinculo->local->endereco_completo }}</span>
                </div>
            </div>

            {{-- 1. Especialidade --}}
            <div class="ag-bloco" style="margin-top: 20px;">
                <p class="ag-passo">1. Especialidade</p>
                @if ($especialidades->isEmpty())
                    <p class="fm-vazio">Este médico ainda não tem preço cadastrado neste endereço, então não recebe agendamento aqui.</p>
                @else
                    <div class="ag-opcoes">
                        @foreach ($especialidades as $esp)
                            <button type="button" class="ag-opcao" :class="esp == {{ $esp->id }} && 'ag-opcao--ativa'" @click="esp = {{ $esp->id }}">
                                {{ $esp->nome }}
                                @if (isset($precos[$esp->id]))
                                    <small>R$ {{ number_format($precos[$esp->id], 2, ',', '.') }}</small>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- 2. Dia --}}
            <div class="ag-bloco">
                <p class="ag-passo">2. Dia</p>
                <div class="ag-dias" x-show="Object.keys(rotulos).length">
                    <template x-for="(r, d) in rotulos" :key="d">
                        <button type="button" class="ag-dia" :class="dia === d && 'ag-dia--ativo'" @click="escolherDia(d)">
                            <small x-text="r.semana"></small>
                            <strong x-text="r.dia"></strong>
                            <span x-text="r.mes"></span>
                        </button>
                    </template>
                </div>
                <p class="fm-vazio" x-show="!Object.keys(rotulos).length">
                    Sem vagas nos próximos dias. Tente escolher uma data mais para frente.
                </p>
                <label class="ag-outra-data">
                    Outra data:
                    <input type="date" min="{{ $minimo }}" max="{{ $maximo }}" @change="escolherDia($event.target.value)">
                </label>
            </div>

            {{-- 3. Horário --}}
            <div class="ag-bloco">
                <p class="ag-passo">3. Horário <span x-show="dia" x-text="'— ' + (rotulos[dia]?.longo ?? '')" style="text-transform: none; font-weight: 600;"></span></p>
                <p class="ag-carregando" x-show="carregando">Buscando horários…</p>
                <div class="ag-horarios" x-show="!carregando && horarios.length">
                    <template x-for="h in horarios" :key="h">
                        <button type="button" class="ag-horario" :class="horario === h && 'ag-horario--ativo'" @click="horario = h" x-text="h"></button>
                    </template>
                </div>
                <p class="fm-vazio" x-show="!carregando && dia && !horarios.length">Nenhum horário livre nesse dia. Escolha outro.</p>
                <p class="fm-vazio" x-show="!carregando && !dia">Escolha um dia para ver os horários.</p>
            </div>

            {{-- 4. Pagamento --}}
            <div class="ag-bloco">
                <p class="ag-passo">4. Forma de pagamento</p>
                <div class="ag-opcoes">
                    <button type="button" class="ag-opcao" :class="forma === 'particular' && 'ag-opcao--ativa'"
                            @click="forma = 'particular'" @disabled(! $vinculo->aceita_particular)>
                        <x-icone nome="money" /> Particular
                    </button>
                    <button type="button" class="ag-opcao" :class="forma === 'convenio' && 'ag-opcao--ativa'"
                            @click="forma = 'convenio'" @disabled(! $vinculo->aceita_convenio)>
                        <x-icone nome="shield" /> Convênio
                    </button>
                </div>
                @unless ($vinculo->aceita_convenio)
                    <p class="fm-meta" style="margin-top: 8px;">Este médico não atende por convênio neste endereço.</p>
                @endunless
            </div>
        </section>

        {{-- Resumo + botão --}}
        <aside class="fm-painel ag-resumo">
            <h2 class="fm-painel__titulo" style="margin-bottom: 16px;"><x-icone nome="list" /> Resumo</h2>
            <dl>
                <div><dt>Especialidade</dt><dd x-text="nomesEsp[esp] ?? '—'"></dd></div>
                <div><dt>Quando</dt><dd x-text="dia && horario ? (rotulos[dia]?.longo ?? dia) + ' às ' + horario : 'Escolha dia e horário'"></dd></div>
                <div><dt>Pagamento</dt><dd x-text="forma === 'convenio' ? 'Convênio' : 'Particular'"></dd></div>
                <div>
                    <dt>Valor</dt>
                    <dd class="ag-valor" x-text="forma === 'convenio' ? 'Pelo convênio' : (precos[esp] !== undefined ? dinheiro(precos[esp]) : '—')"></dd>
                </div>
            </dl>

            <form method="GET" action="{{ route('agendamento.confirmar', $vinculo) }}">
                <input type="hidden" name="especialidade_id" :value="esp">
                <input type="hidden" name="data_consulta" :value="dia">
                <input type="hidden" name="horario" :value="horario">
                <input type="hidden" name="forma_pagamento" :value="forma">
                @if ($modoClinica)
                    <input type="hidden" name="origem" value="clinica">
                @endif
                @if (request('remarcar'))
                    <input type="hidden" name="remarcar_consulta_id" value="{{ (int) request('remarcar') }}">
                @endif
                <button type="submit" class="fm-botao" :disabled="!esp || !dia || !horario">
                    Continuar <x-icone nome="chevron-right" />
                </button>
            </form>
        </aside>
    </div>

@endsection

@push('scripts')
<script>
    function agendamento(cfg) {
        return {
            ...cfg,
            horario: '',
            carregando: false,
            horarios: cfg.dia ? (cfg.dias[cfg.dia] ?? []) : [],

            dinheiro(v) {
                return Number(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            },

            rotuloDe(d) {
                const [a, m, dd] = d.split('-').map(Number);
                const data = new Date(a, m - 1, dd);
                const semana = data.toLocaleDateString('pt-BR', { weekday: 'short' }).replace('.', '');
                const mes = data.toLocaleDateString('pt-BR', { month: 'short' }).replace('.', '');
                const longo = data.toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long' });
                return { semana, dia: String(dd).padStart(2, '0'), mes, longo: longo.charAt(0).toUpperCase() + longo.slice(1) };
            },

            async escolherDia(d) {
                if (!d) return;

                // Pela clínica, o médico encaixado depende do dia: recarrega a página.
                if (this.modoClinica) {
                    window.location = this.urlClinica + '?data=' + d;
                    return;
                }

                this.dia = d;
                this.horario = '';
                if (!this.rotulos[d]) this.rotulos = { ...this.rotulos, [d]: this.rotuloDe(d) };

                if (this.dias[d]) { this.horarios = this.dias[d]; return; }

                this.carregando = true;
                try {
                    const resp = await fetch(this.urlHorarios + '?data=' + d, { headers: { 'Accept': 'application/json' } });
                    const json = await resp.json();
                    this.horarios = json.horarios ?? [];
                    this.dias[d] = this.horarios;
                } catch (e) {
                    this.horarios = [];
                } finally {
                    this.carregando = false;
                }
            },
        };
    }
</script>
@endpush
