{{--
    Clínica → Ausências. Dados: Clinica\AusenciaController@index.

    01/10/2026 (plano novo do grupo): antes era o próprio médico; agora a
    clínica registra as ausências dos médicos nas unidades dela (férias,
    congresso, imprevisto). Horário dentro de uma ausência nunca é oferecido.

    NENHUMA CONSULTA É CANCELADA EM SILÊNCIO: sem marcar "cancelar as
    consultas", o back-end registra a ausência mas mantém as consultas e
    devolve session('erro') explicando.
--}}
@extends('layouts.painel')

@section('titulo', 'Ausências')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    use App\Support\Formatador;
    $dataHora = fn ($d) => Formatador::dataCurta($d) . ' ' . $d->format('H:i');
    $futuras  = $bloqueios->filter(fn ($b) => $b->fim->isFuture());
    $passadas = $bloqueios->reject(fn ($b) => $b->fim->isFuture());
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Ausências</h1>
            <p class="fm-subtitulo">Períodos em que um médico não atende numa unidade. Esses horários somem do agendamento.</p>
        </div>
    </div>

    @if ($vinculos->isEmpty())
        <section class="fm-painel" style="margin-top: 18px;">
            <p class="fm-vazio">Nenhum médico atende nas suas unidades ainda.</p>
        </section>
    @else
        {{-- ===================== NOVA AUSÊNCIA ===================== --}}
        <section class="fm-painel" style="margin-top: 18px;">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="plus" /> Registrar ausência</h2>
            </header>

            <form method="POST" action="{{ route('clinica.ausencias.salvar') }}" class="fm-form fm-form--duas">
                @csrf

                <div class="fm-campo fm-campo--largo {{ $errors->has('vinculo_id') ? 'fm-campo--erro' : '' }}">
                    <label for="vinculo_id">Médico e unidade *</label>
                    <select id="vinculo_id" name="vinculo_id" required>
                        @foreach ($vinculos as $v)
                            <option value="{{ $v->id }}" @selected(old('vinculo_id') == $v->id)>{{ $v->medico->user->name }} — {{ $v->local->nome }}</option>
                        @endforeach
                    </select>
                    @error('vinculo_id') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                </div>

                <div class="fm-campo {{ $errors->has('inicio') ? 'fm-campo--erro' : '' }}">
                    <label for="inicio">Começa em *</label>
                    <input id="inicio" name="inicio" type="datetime-local" required value="{{ old('inicio') }}">
                    @error('inicio') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                </div>

                <div class="fm-campo {{ $errors->has('fim') ? 'fm-campo--erro' : '' }}">
                    <label for="fim">Termina em *</label>
                    <input id="fim" name="fim" type="datetime-local" required value="{{ old('fim') }}">
                    @error('fim') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                </div>

                <div class="fm-campo fm-campo--largo {{ $errors->has('motivo') ? 'fm-campo--erro' : '' }}">
                    <label for="motivo">Motivo</label>
                    <input id="motivo" name="motivo" maxlength="255" value="{{ old('motivo') }}" placeholder="Ex.: férias, congresso">
                    <span class="fm-campo__ajuda">Se houver consulta cancelada, o motivo vai no aviso ao paciente.</span>
                    @error('motivo') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                </div>

                <label class="fm-marcar fm-campo--largo">
                    <input type="checkbox" name="cancelar_consultas" value="1" @checked(old('cancelar_consultas'))>
                    <span>
                        <strong>Cancelar as consultas marcadas nesse período</strong><br>
                        Os pacientes recebem um e-mail. Se você não marcar e houver consultas, a ausência é registrada
                        mas as consultas continuam de pé — aí você cancela uma por uma pela agenda.
                    </span>
                </label>

                <div class="fm-form__acoes">
                    <button type="submit" class="fm-botao">Registrar ausência</button>
                </div>
            </form>
        </section>
    @endif

    {{-- ===================== LISTA ===================== --}}
    <section class="fm-painel">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="pause" /> Próximas e em andamento</h2>
        </header>

        @if ($futuras->isNotEmpty())
            <div class="fm-rolagem">
                <table class="fm-tabela-crud">
                    <thead>
                        <tr><th>Médico</th><th>Onde</th><th>De</th><th>Até</th><th>Motivo</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($futuras as $b)
                            <tr>
                                <td><strong>{{ $b->medico->user->name }}</strong></td>
                                <td>{{ $b->vinculo?->local->nome }}</td>
                                <td>{{ $dataHora($b->inicio) }}</td>
                                <td>{{ $dataHora($b->fim) }}</td>
                                <td>{{ $b->motivo ?: '—' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('clinica.ausencias.remover', $b) }}" class="fm-tabela-crud__acoes">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="fm-botao fm-botao--perigo fm-botao--pequeno">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="fm-vazio">Nenhuma ausência marcada.</p>
        @endif
    </section>

    @if ($passadas->isNotEmpty())
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="clock" /> Anteriores</h2>
            </header>
            <ul class="fm-lista">
                @foreach ($passadas->take(10) as $b)
                    <li class="fm-linha">
                        <span class="fm-linha__data">{{ Formatador::dataCurta($b->inicio) }}</span>
                        <div class="fm-linha__info">
                            <strong>{{ $b->medico->user->name }} · {{ $b->motivo ?: 'Ausência' }}</strong>
                            <span>até {{ $dataHora($b->fim) }} · {{ $b->vinculo?->local->nome }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

@endsection
