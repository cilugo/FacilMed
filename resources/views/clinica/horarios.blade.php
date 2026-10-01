{{--
    Clínica → Horários dos médicos. Dados: Clinica\HorarioController@index.

    01/10/2026 (plano novo do grupo): antes era o próprio médico ("Meus
    horários"); agora a clínica cadastra quando cada médico atende em cada
    unidade dela. O médico só vê a agenda.

    O ALMOÇO NÃO TEM CAMPO: são dois blocos no mesmo dia (08–12 e 14–18).
    Quem recusa bloco fora do funcionamento da unidade, sobreposto (inclusive
    com outro lugar onde o médico atende) ou menor que uma consulta é o
    SalvarDisponibilidadeRequest — aqui só aparecem os erros.
--}}
@extends('layouts.painel')

@section('titulo', 'Horários dos médicos')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    use App\Support\Formatador;
    // Índice de Disponibilidade::DIAS = Carbon::dayOfWeek = índice de Formatador::DIAS.
    $nomeDia = fn (string $dia) => Formatador::DIAS[array_search($dia, $dias, true)] ?? $dia;
    // Segunda primeiro, domingo por último (é como a semana de trabalho é lida).
    $ordem = array_merge(array_slice($dias, 1), [$dias[0]]);
    $escolhido = (int) old('vinculo_id', request()->integer('vinculo'));
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Horários dos médicos</h1>
            <p class="fm-subtitulo">Quando cada médico atende em cada unidade. Os pacientes só veem horários dentro destes blocos.</p>
        </div>
    </div>

    <p class="fm-dica">
        <x-icone nome="lightbulb" />
        <span><strong>Intervalo de almoço:</strong> cadastre dois blocos no mesmo dia — por exemplo, 08:00–12:00 e
            14:00–18:00. O espaço entre eles fica livre.</span>
    </p>

    @if ($vinculos->isEmpty())
        <section class="fm-painel" style="margin-top: 18px;">
            <p class="fm-vazio fm-vazio--acao">
                Nenhum médico atende nas suas unidades ainda.
                <a href="{{ route('clinica.medicos.novo') }}" class="fm-pilula fm-pilula--pequena">Cadastrar médico <x-icone nome="chevron-right" /></a>
            </p>
        </section>
    @else

        {{-- ===================== NOVO BLOCO ===================== --}}
        <section class="fm-painel" style="margin-top: 18px;">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="plus" /> Adicionar horário</h2>
            </header>

            <form method="POST" action="{{ route('clinica.horarios.salvar') }}" class="fm-form fm-form--tres">
                @csrf

                <div class="fm-campo {{ $errors->has('vinculo_id') ? 'fm-campo--erro' : '' }}">
                    <label for="vinculo_id">Médico e unidade *</label>
                    <select id="vinculo_id" name="vinculo_id" required>
                        @foreach ($vinculos as $v)
                            <option value="{{ $v->id }}" @selected($escolhido === $v->id)>{{ $v->medico->user->name }} — {{ $v->local->nome }}</option>
                        @endforeach
                    </select>
                    @error('vinculo_id') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                </div>

                <div class="fm-campo {{ $errors->has('dia_semana') ? 'fm-campo--erro' : '' }}">
                    <label for="dia_semana">Dia da semana *</label>
                    <select id="dia_semana" name="dia_semana" required>
                        @foreach ($ordem as $dia)
                            <option value="{{ $dia }}" @selected(old('dia_semana', 'segunda') === $dia)>{{ $nomeDia($dia) }}</option>
                        @endforeach
                    </select>
                    @error('dia_semana') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                </div>

                <div class="fm-campo {{ $errors->has('duracao_consulta_minutos') ? 'fm-campo--erro' : '' }}">
                    <label for="duracao_consulta_minutos">Duração de cada consulta *</label>
                    <select id="duracao_consulta_minutos" name="duracao_consulta_minutos" required>
                        @foreach ($duracoes as $min)
                            <option value="{{ $min }}" @selected((int) old('duracao_consulta_minutos', 30) === $min)>{{ $min }} minutos</option>
                        @endforeach
                    </select>
                    @error('duracao_consulta_minutos') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                </div>

                <div class="fm-campo {{ $errors->has('hora_inicio') ? 'fm-campo--erro' : '' }}">
                    <label for="hora_inicio">Começa às *</label>
                    <input id="hora_inicio" name="hora_inicio" type="time" required value="{{ old('hora_inicio', '08:00') }}">
                    @error('hora_inicio') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                </div>

                <div class="fm-campo {{ $errors->has('hora_fim') ? 'fm-campo--erro' : '' }}">
                    <label for="hora_fim">Termina às *</label>
                    <input id="hora_fim" name="hora_fim" type="time" required value="{{ old('hora_fim', '12:00') }}">
                    @error('hora_fim') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                </div>

                <div class="fm-form__acoes" style="align-self: end;">
                    <button type="submit" class="fm-botao"><x-icone nome="plus" /> Adicionar</button>
                </div>
            </form>
        </section>

        {{-- ===================== BLOCOS POR MÉDICO E UNIDADE ===================== --}}
        @foreach ($vinculos as $v)
            @php $porDia = $v->disponibilidades->sortBy('hora_inicio')->groupBy('dia_semana'); @endphp

            <section class="fm-painel" id="vinculo-{{ $v->id }}">
                <header class="fm-painel__topo">
                    <h2 class="fm-painel__titulo"><x-icone nome="user" /> {{ $v->medico->user->name }}</h2>
                    <span class="fm-painel__periodo"><x-icone nome="pin" /> {{ $v->local->nome }}</span>
                </header>

                @if ($v->disponibilidades->isEmpty())
                    <p class="fm-vazio">Nenhum horário cadastrado — ninguém consegue agendar com este médico aqui ainda.</p>
                @else
                    <ul class="fm-semana">
                        @foreach ($ordem as $dia)
                            @continue(! $porDia->has($dia))
                            <li class="fm-semana__dia">
                                <strong class="fm-semana__nome">{{ $nomeDia($dia) }}</strong>
                                <div class="fm-chips">
                                    @foreach ($porDia[$dia] as $bloco)
                                        <span class="fm-chip {{ $bloco->ativo ? '' : 'fm-chip--cinza' }}">
                                            {{ Formatador::hora($bloco->hora_inicio) }}–{{ Formatador::hora($bloco->hora_fim) }}
                                            <small>{{ $bloco->duracao_consulta_minutos }} min</small>
                                            <form method="POST" action="{{ route('clinica.horarios.remover', $bloco) }}" class="fm-chip__form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="fm-chip__remover"
                                                        aria-label="Remover {{ $nomeDia($dia) }} {{ Formatador::hora($bloco->hora_inicio) }}–{{ Formatador::hora($bloco->hora_fim) }}"
                                                        title="Remover este horário">
                                                    <x-icone nome="x" />
                                                </button>
                                            </form>
                                        </span>
                                    @endforeach
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <p class="fm-campo__ajuda" style="margin-top: 10px;">
                        Remover um horário não cancela consultas já marcadas nele — elas continuam na agenda.
                    </p>
                @endif
            </section>
        @endforeach
    @endif

@endsection
