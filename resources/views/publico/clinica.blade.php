{{--
    Perfil público da clínica ou hospital. Dados: PerfilPublicoController@clinica.

    Duas formas de agendar:
    - "Agendar com a clínica": o paciente escolhe só a especialidade e a
      clínica encaixa o médico com a primeira vaga (AlocadorDeMedico).
    - Com um médico específico, pelo perfil dele.
--}}
@extends('layouts.site')

@section('titulo', $clinica->nome_fantasia)
@section('menu', 'busca')

@php
    $unidades = $clinica->locais->where('ativo', true)->values();
    // $medicos vem pronto do controller (só médico visível — regra fora da Blade).
    $ordemDias = ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado', 'domingo'];
    $nomeDia = ['segunda' => 'Segunda', 'terca' => 'Terça', 'quarta' => 'Quarta', 'quinta' => 'Quinta', 'sexta' => 'Sexta', 'sabado' => 'Sábado', 'domingo' => 'Domingo'];
    $ehHospital = $unidades->contains('tipo', 'hospital');
    $usuario = auth()->user();
    $podeAgendar = ! $usuario || $usuario->tipo === 'paciente';
@endphp

@section('conteudo')
<div class="container perfil">

    <a href="{{ route('busca.index') }}" class="voltar"><x-icone nome="arrow-left" /> Encontrar médicos</a>

    <div class="perfil-grade">
        <div>
            <section class="caixa">
                <div class="perfil-cabeca">
                    <div class="avatar avatar--grande" style="background-color: #183e9f;">
                        <x-icone :nome="$ehHospital ? 'hospital' : 'building'" style="width: 40px; height: 40px;" />
                    </div>
                    <div>
                        <h1>{{ $clinica->nome_fantasia }}</h1>
                        <div class="dados">
                            <span>{{ $ehHospital ? 'Hospital' : 'Clínica' }}</span>
                            <span>{{ $unidades->count() }} {{ $unidades->count() === 1 ? 'unidade' : 'unidades' }}</span>
                            <span>{{ $medicos->count() }} {{ $medicos->count() === 1 ? 'médico' : 'médicos' }}</span>
                        </div>
                    </div>
                </div>
                @if ($clinica->descricao)
                    <p class="perfil-bio">{{ $clinica->descricao }}</p>
                @endif
            </section>

            <section class="caixa">
                <h2><x-icone nome="calendar-check" /> Agendar com a clínica</h2>
                <p class="texto-pequeno" style="margin-bottom: 14px;">
                    Escolha só a especialidade: a clínica encaixa você com o médico que tiver o primeiro horário livre.
                </p>
                @if ($especialidades->isEmpty())
                    <p class="texto-pequeno">Nenhuma especialidade disponível no momento.</p>
                @else
                    <div class="chips">
                        @foreach ($especialidades as $esp)
                            @if ($podeAgendar)
                                <a href="{{ route('agendamento.especialidade', [$clinica, $esp]) }}" class="btn btn-outline">{{ $esp->nome }}</a>
                            @else
                                <span class="chip">{{ $esp->nome }}</span>
                            @endif
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="caixa">
                <h2><x-icone nome="pin" /> Unidades</h2>
                @foreach ($unidades as $u)
                    <div class="local">
                        <h3>{{ $u->nome }}</h3>
                        <p class="local__endereco">{{ $u->endereco_completo }}</p>
                        @if ($u->telefone)
                            <p class="local__endereco">Telefone: {{ \App\Support\Formatador::telefone($u->telefone) }}</p>
                        @endif
                        @php $horarios = $u->horarios->sortBy(fn ($h) => array_search($h->dia_semana, $ordemDias)); @endphp
                        @if ($horarios->isNotEmpty())
                            <div class="horarios-lista">
                                @foreach ($horarios as $h)
                                    <span>{{ $nomeDia[$h->dia_semana] ?? $h->dia_semana }}</span>
                                    <span>{{ substr($h->abre, 0, 5) }} às {{ substr($h->fecha, 0, 5) }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </section>
        </div>

        <aside>
            <section class="caixa">
                <h2><x-icone nome="doctors" /> Médicos</h2>
                @if ($medicos->isEmpty())
                    <p class="texto-pequeno">Nenhum médico vinculado ainda.</p>
                @else
                    <div class="lista-simples">
                        @foreach ($medicos as $i => $m)
                            <a href="{{ route('publico.medico', $m) }}">
                                <span class="avatar" style="background-color: {{ \App\Support\Formatador::corAvatar($i) }};">{{ \App\Support\Formatador::iniciais($m->user->name) }}</span>
                                <span>
                                    <strong>{{ $m->user->name }}</strong>
                                    <small>{{ $m->especialidades->pluck('nome')->join(' · ') }}</small>
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        </aside>
    </div>
</div>
@endsection
