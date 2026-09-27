{{--
    E-mail dos avisos de consulta. Dados: App\Mail\AvisoDeConsulta.
    Sem conteúdo clínico e sem as observações do paciente (AGENTS.md §6).
--}}
<x-mail::message>
@if ($para === 'medico')
# Olá, {{ $c->medico->user->name }}
@else
# Olá, {{ \Illuminate\Support\Str::of($c->paciente->user->name)->before(' ') }}
@endif

@switch($tipo)
@case('confirmacao')
Sua consulta está **agendada**.
@break
@case('lembrete_24h')
Lembrete: sua consulta é **amanhã**.
@break
@case('cancelamento')
@if ($para === 'medico')
O paciente **cancelou** a consulta abaixo. O horário voltou a ficar livre na sua agenda.
@else
Sua consulta foi **cancelada**.@if ($c->motivo_cancelamento) Motivo: {{ $c->motivo_cancelamento }}.@endif
@endif
@break
@case('remarcacao')
O paciente **remarcou** a consulta abaixo para outro horário. Este horário voltou a ficar livre.
@break
@endswitch

<x-mail::panel>
**Quando:** {{ $quando }}<br>
@if ($para === 'medico')
**Paciente:** {{ $c->paciente->user->name }}<br>
@else
**Médico(a):** {{ $c->medico->user->name }}<br>
@endif
**Especialidade:** {{ $c->especialidade->nome }}<br>
**Onde:** {{ $c->vinculo->local->nome }} — {{ $c->vinculo->local->endereco_completo }}
</x-mail::panel>

@if ($para === 'paciente' && in_array($tipo, ['confirmacao', 'lembrete_24h']))
@if ($c->forma_pagamento === 'convenio')
Leve a carteirinha do convênio. Confirme na recepção se o seu plano é aceito neste endereço.
@endif

Não vai poder ir? Cancele pelo FacilMed: o horário fica livre para outra pessoa.
@endif

<x-mail::button :url="$url">
{{ $para === 'medico' ? 'Ver agenda' : 'Ver consulta' }}
</x-mail::button>

FacilMed — projeto acadêmico. Médicos, clínicas e convênios são fictícios.
</x-mail::message>
