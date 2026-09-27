{{--
    Agendamento pela clínica sem vaga. Dados: AgendamentoController@porEspecialidade.
    Aparece quando nenhum médico da clínica, com essa especialidade, tem
    horário livre (na data escolhida, ou em toda a janela de agendamento).
--}}
@extends('layouts.painel')

@section('titulo', 'Sem vagas')

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Sem vagas</h1>
            <p class="fm-subtitulo">{{ $especialidade->nome }} · {{ $clinica->nome_fantasia }}</p>
        </div>
    </div>

    <section class="fm-painel fm-vazio--acao" style="text-align: center; padding: 40px 24px;">
        <p style="font-size: 17px; color: var(--fm-titulo); font-weight: 700; margin-bottom: 6px;">
            @if ($dataTentada)
                Nenhum médico de {{ $especialidade->nome }} tem horário livre em
                {{ \App\Support\Formatador::dataExtensa(\Carbon\Carbon::parse($dataTentada)) }}.
            @else
                No momento nenhum médico de {{ $especialidade->nome }} desta clínica tem horário livre.
            @endif
        </p>
        <p class="fm-subtitulo" style="margin-bottom: 20px;">Tente outra data ou procure outro médico.</p>

        <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
            @if ($dataTentada)
                <a href="{{ route('agendamento.especialidade', [$clinica, $especialidade]) }}" class="fm-botao">Ver a primeira data com vaga</a>
            @endif
            <a href="{{ route('busca.index', ['especialidade' => $especialidade->slug]) }}" class="fm-botao fm-botao--suave">Outros médicos de {{ $especialidade->nome }}</a>
        </div>
    </section>

@endsection
