{{--
    Campos horarios[dia][abre|fecha] do horário de FUNCIONAMENTO de um lugar
    (consultório do médico, unidade da clínica). Dia em branco = fechado.

    Recebe:
      $atuais  — ['segunda' => ['abre' => '08:00', 'fecha' => '18:00'], ...] (opcional)
      $comOld  — true se este é o formulário que voltou com erro (usa old() e mostra erros).
                 Numa tela com vários formulários de horário, só um pode usar old().
--}}
@php
    use App\Models\HorarioFuncionamento;
    use App\Support\Formatador;
    $atuais = $atuais ?? [];
    $comOld = $comOld ?? true;
    // Segunda primeiro, domingo por último.
    $ordemDias = array_merge(array_slice(HorarioFuncionamento::DIAS, 1), [HorarioFuncionamento::DIAS[0]]);
@endphp

<div class="fm-funcionamento__grade">
    @foreach ($ordemDias as $dia)
        @php
            $idx  = array_search($dia, HorarioFuncionamento::DIAS, true);
            $abre = $comOld ? old("horarios.$dia.abre", $atuais[$dia]['abre'] ?? '') : ($atuais[$dia]['abre'] ?? '');
            $fecha = $comOld ? old("horarios.$dia.fecha", $atuais[$dia]['fecha'] ?? '') : ($atuais[$dia]['fecha'] ?? '');
            $erro = $comOld ? ($errors->first("horarios.$dia.fecha") ?: $errors->first("horarios.$dia.abre")) : null;
        @endphp
        <div class="fm-funcionamento__dia {{ $erro ? 'fm-campo--erro' : '' }}">
            <span>{{ Formatador::DIAS_CURTOS[$idx] }}</span>
            <input type="time" name="horarios[{{ $dia }}][abre]" value="{{ $abre }}" aria-label="{{ Formatador::DIAS[$idx] }}: abre">
            <input type="time" name="horarios[{{ $dia }}][fecha]" value="{{ $fecha }}" aria-label="{{ Formatador::DIAS[$idx] }}: fecha">
            @if ($erro)
                <span class="fm-campo__erro">{{ $erro }}</span>
            @endif
        </div>
    @endforeach
</div>
