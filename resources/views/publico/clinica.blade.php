{{--
    Perfil público da clínica ou hospital. Dados: PerfilPublicoController@clinica.

    Descrição, especialidades, unidades (com a nota de cada uma) e médicos.
    01/10/2026: sem agendamento ("Agendar com a clínica" saiu). A foto de
    perfil da conta da clínica aparece no lugar do ícone.
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
                        @if ($clinica->user->foto_url)
                            <img src="{{ $clinica->user->foto_url }}" alt="" class="avatar__foto" onerror="this.remove()">
                        @endif
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
                <h2><x-icone nome="stethoscope" /> Especialidades</h2>
                @if ($especialidades->isEmpty())
                    <p class="texto-pequeno">Nenhuma especialidade disponível no momento.</p>
                @else
                    <div class="chips">
                        @foreach ($especialidades as $esp)
                            <a href="{{ route('busca.locais', ['especialidade' => $esp->slug]) }}" class="chip">{{ $esp->nome }}</a>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="caixa">
                <h2><x-icone nome="pin" /> Unidades</h2>
                @foreach ($unidades as $u)
                    <div class="local">
                        <h3><a href="{{ route('publico.local', $u) }}" style="text-decoration: underline;">{{ $u->nome }}</a></h3>
                        @if ($u->total_avaliacoes > 0)
                            <span class="medico-nota"><x-icone nome="star" /> {{ number_format((float) $u->media_avaliacoes, 1, ',', '') }}
                                <span style="font-weight: 400; color: #666;">({{ $u->total_avaliacoes }})</span></span>
                        @endif
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
                                <span class="avatar" style="background-color: {{ \App\Support\Formatador::corAvatar($i) }};">{{ \App\Support\Formatador::iniciais($m->nome) }}@if ($m->foto_url)<img src="{{ $m->foto_url }}" alt="" class="avatar__foto" onerror="this.remove()">@endif</span>
                                <span>
                                    <strong>{{ $m->nome }}</strong>
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
