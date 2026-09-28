{{--
    Médico → Meus horários. Dados: Medico\DisponibilidadeController@index (README §7.1).

    Blocos da semana por lugar. O ALMOÇO NÃO TEM CAMPO: são dois blocos no
    mesmo dia (08–12 e 14–18). A tela explica isso logo no topo, senão o
    médico procura um campo "almoço" que não existe.

    Quem recusa bloco fora do funcionamento, sobreposto ou menor que uma
    consulta é o SalvarDisponibilidadeRequest — aqui só aparecem os erros.
--}}
@extends('layouts.painel')

@section('titulo', 'Meus horários')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    use App\Support\Formatador;
    // Índice de Disponibilidade::DIAS = Carbon::dayOfWeek = índice de Formatador::DIAS.
    $nomeDia = fn (string $dia) => Formatador::DIAS[array_search($dia, $dias, true)] ?? $dia;
    // Segunda primeiro, domingo por último (é como a semana de trabalho é lida).
    $ordem = array_merge(array_slice($dias, 1), [$dias[0]]);
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Meus horários</h1>
            <p class="fm-subtitulo">Quando você atende em cada lugar. Os pacientes só veem horários dentro destes blocos.</p>
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
                Você ainda não tem lugar de atendimento.
                <a href="{{ route('medico.locais') }}" class="fm-pilula fm-pilula--pequena">Cadastrar onde eu atendo <x-icone nome="chevron-right" /></a>
            </p>
        </section>
    @else

        {{-- ===================== NOVO BLOCO ===================== --}}
        <section class="fm-painel" style="margin-top: 18px;">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="plus" /> Adicionar horário</h2>
            </header>

            <form method="POST" action="{{ route('medico.disponibilidade.salvar') }}" class="fm-form fm-form--tres">
                @csrf

                <div class="fm-campo {{ $errors->has('vinculo_id') ? 'fm-campo--erro' : '' }}">
                    <label for="vinculo_id">Lugar *</label>
                    <select id="vinculo_id" name="vinculo_id" required>
                        @foreach ($vinculos as $v)
                            <option value="{{ $v->id }}" @selected(old('vinculo_id') == $v->id)>{{ $v->local->nome }}</option>
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

        {{-- ===================== BLOCOS POR LUGAR ===================== --}}
        @foreach ($vinculos as $v)
            @php $porDia = $v->disponibilidades->sortBy('hora_inicio')->groupBy('dia_semana'); @endphp

            <section class="fm-painel">
                <header class="fm-painel__topo">
                    <h2 class="fm-painel__titulo"><x-icone nome="pin" /> {{ $v->local->nome }}</h2>
                    @unless ($v->ativo)
                        <span class="fm-etiqueta fm-etiqueta--cinza">Vínculo encerrado</span>
                    @endunless
                </header>

                @if ($v->disponibilidades->isEmpty())
                    <p class="fm-vazio">Nenhum horário cadastrado neste lugar — ninguém consegue agendar aqui ainda.</p>
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
                                            <form method="POST" action="{{ route('medico.disponibilidade.remover', $bloco) }}" class="fm-chip__form">
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
                        Remover um horário não cancela consultas já marcadas nele — elas continuam na sua agenda.
                    </p>
                @endif
            </section>
        @endforeach
    @endif

@endsection
