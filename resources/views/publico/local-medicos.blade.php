{{--
    Médicos disponíveis no local (01/10/2026, "Tela 5" do PDF do grupo).
    Dados: PerfilPublicoController@medicosDoLocal.

    Um cartão por médico: foto (ou iniciais), nome, nota, especialidades com
    preço e as informações do médico (bio), com "Ver horários" — o
    agendamento de sempre. Só médico que dá para agendar aqui (a mesma regra
    da busca).
--}}
@extends('layouts.site')

@section('titulo', 'Médicos disponíveis · ' . $local->nome)
@section('menu', 'locais')

@php
    $moeda = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $origemParams = $origem['params'] ?? [];
    $usuario = auth()->user();
    $podeAgendar = ! $usuario || $usuario->tipo === 'paciente';
@endphp

@section('conteudo')
<div class="container perfil">

    <a href="{{ route('publico.local', ['local' => $local] + array_filter(['especialidade' => $slug]) + $origemParams) }}" class="voltar">
        <x-icone nome="arrow-left" /> {{ $local->nome }}
    </a>

    <h1 class="titulo-pagina">Médicos disponíveis</h1>
    <p class="texto-pequeno">{{ $escolhida ? $escolhida->nome . ' · ' : '' }}{{ $local->nome }}</p>

    @if ($especialidades->count() > 1)
        <nav class="filtros-rapidos" aria-label="Especialidade">
            <a href="{{ route('publico.local.medicos', ['local' => $local] + $origemParams) }}" class="filtro {{ $slug ? '' : 'is-ativo' }}">Todas</a>
            @foreach ($especialidades as $e)
                <a href="{{ route('publico.local.medicos', ['local' => $local, 'especialidade' => $e->slug] + $origemParams) }}"
                   class="filtro {{ $slug === $e->slug ? 'is-ativo' : '' }}">{{ $e->nome }}</a>
            @endforeach
        </nav>
    @endif

    <p class="resultado-info">
        <span><strong>{{ $medicos->count() }}</strong> {{ $medicos->count() === 1 ? 'médico atende' : 'médicos atendem' }}{{ $escolhida ? ' ' . $escolhida->nome : '' }} neste local</span>
    </p>

    @if ($medicos->isEmpty())
        <div class="vazio">
            <h2>Nenhum médico {{ $escolhida ? 'de ' . $escolhida->nome . ' ' : '' }}disponível aqui</h2>
            <p>Veja outras especialidades deste local ou volte para a busca.</p>
        </div>
    @else
        <div class="lista-locais">
            @foreach ($medicos as $i => $v)
                @php $m = $v->medico; $foto = $m->fotoUrl(); @endphp
                <article class="card-medico-local">
                    <div class="card-medico-local__topo">
                        <span class="avatar" style="background-color: {{ \App\Support\Formatador::corAvatar($i) }};">
                            {{ \App\Support\Formatador::iniciais($m->user->name) }}
                            @if ($foto)
                                <img src="{{ $foto }}" alt="" onerror="this.remove()">
                            @endif
                        </span>
                        <div>
                            <h2><a href="{{ route('publico.medico', $m) }}">{{ $m->user->name }}</a></h2>
                            <p class="local-nota" style="margin-top: 2px;">
                                @if ($m->total_avaliacoes > 0)
                                    <span class="medico-nota"><x-icone nome="star" /> {{ number_format((float) $m->media_avaliacoes, 1, ',', '') }}</span>
                                    <span class="texto-pequeno">{{ $m->total_avaliacoes }} {{ $m->total_avaliacoes === 1 ? 'avaliação' : 'avaliações' }}</span>
                                @else
                                    <span class="texto-pequeno">Ainda sem avaliações</span>
                                @endif
                                <span class="crm">· CRM {{ $m->crm }}/{{ $m->uf }}</span>
                            </p>
                            <p class="texto-pequeno" style="margin-top: 6px;">Atua em</p>
                            <div class="chips" style="margin-top: 4px;">
                                @foreach ($v->precosOferecidos() as $p)
                                    <span class="chip">{{ $p->especialidade->nome }}@if ($v->aceita_particular) · {{ $moeda($p->valor) }}@endif</span>
                                @endforeach
                            </div>
                        </div>
                        @if ($podeAgendar)
                            <a href="{{ route('agendamento.horario', $v) }}" class="btn btn-primary">Ver horários</a>
                        @endif
                    </div>
                    @if ($m->bio || $m->anos_atuacao)
                        <p class="card-medico-local__bio">
                            {{ $m->bio }}
                            @if ($m->anos_atuacao) <span class="texto-pequeno">· {{ $m->anos_atuacao }} {{ $m->anos_atuacao == 1 ? 'ano' : 'anos' }} de atuação</span>@endif
                        </p>
                    @endif
                    <p class="texto-pequeno">
                        {{ $v->aceita_particular ? 'Atende particular' : 'Não atende particular' }}{{ $v->aceita_convenio && $m->convenios->isNotEmpty() ? ' · Convênios: ' . $m->convenios->pluck('nome')->join(', ') : '' }}
                    </p>
                </article>
            @endforeach
        </div>
        @if (collect($medicos)->contains('aceita_convenio', true))
            <p class="texto-pequeno" style="padding-bottom: 40px;">Confirme na recepção se o seu plano é aceito neste endereço.</p>
        @endif
    @endif
</div>
@endsection
