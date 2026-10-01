{{--
    Médico → Meu perfil. Dados: Medico\PerfilController@edit (README §7.1).

    01/10/2026 (plano novo do grupo): o médico só VÊ. Dados, especialidades e
    convênios aparecem só para leitura — quem atualiza é a clínica (Meus médicos
    → Editar). Aqui ele só troca a senha.

    SENHA TEMPORÁRIA: médico cadastrado pela clínica entra com uma senha
    provisória, e o middleware ExigirTrocaDeSenha só libera esta tela até ele
    trocar. Nesse caso só o formulário de senha aparece, em destaque.
--}}
@extends('layouts.painel')

@section('titulo', 'Meu perfil')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    use App\Support\Formatador;
    $user = $medico->user;
    $principal = $medico->especialidades->firstWhere('pivot.principal', true);
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Meu perfil</h1>
            <p class="fm-subtitulo">Como você aparece para os pacientes.</p>
        </div>
        @if ($medico->status_verificacao === 'verificado')
            <a href="{{ route('publico.medico', $medico) }}" class="fm-pilula" target="_blank" rel="noopener">
                Ver meu perfil público <x-icone nome="chevron-right" />
            </a>
        @endif
    </div>

    @if (session('status') === 'password-updated')
        <div class="fm-flash fm-flash--ok" role="status">Senha trocada.</div>
    @endif

    @if ($medico->senha_temporaria)
        {{-- Até trocar a senha, o middleware ExigirTrocaDeSenha bloqueia todo o resto. --}}
        <p class="fm-dica" style="margin-top: 18px;">
            <x-icone nome="lightbulb" />
            <span>Você entrou com a <strong>senha provisória</strong> que a clínica te passou. Crie uma senha sua para
                liberar a sua agenda.</span>
        </p>
        @include('painel.parciais.senha', ['destaque' => true])
    @else
        <p class="fm-dica" style="margin-top: 18px;">
            <x-icone nome="lightbulb" />
            <span>Seus dados, horários e ausências são cadastrados pela clínica onde você atende. Se algo estiver errado,
                fale com a recepção dela.</span>
        </p>

        <section class="fm-painel" style="margin-top: 18px;">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="user" /> Dados profissionais</h2>
                @if ($medico->status_verificacao === 'verificado')
                    <span class="fm-etiqueta fm-etiqueta--verde" title="Conferido na base simulada do FacilMed">CRM conferido</span>
                @endif
            </header>

            <dl class="fm-dados">
                <div><dt>Nome</dt><dd>{{ $user->name }}</dd></div>
                <div><dt>E-mail (login)</dt><dd>{{ $user->email }}</dd></div>
                <div><dt>CRM</dt><dd>{{ $medico->crm }}/{{ $medico->uf }}</dd></div>
                <div><dt>Anos de atuação</dt><dd>{{ $medico->anos_atuacao ?: '—' }}</dd></div>
                <div><dt>Telefone profissional</dt><dd>{{ $medico->telefone_profissional ? Formatador::telefone($medico->telefone_profissional) : '—' }}</dd></div>
                <div><dt>Especialidades</dt><dd>
                    {{ $medico->especialidades->sortByDesc('pivot.principal')->pluck('nome')->join(', ') ?: '—' }}
                    @if ($principal) <small>(principal: {{ $principal->nome }})</small> @endif
                </dd></div>
                <div><dt>Convênios</dt><dd>{{ $medico->convenios->pluck('nome')->join(', ') ?: 'Nenhum' }}</dd></div>
                <div class="fm-dados__largo"><dt>Sobre você</dt><dd>{{ $medico->bio ?: '—' }}</dd></div>
            </dl>
        </section>

        @include('painel.parciais.senha', ['destaque' => false])
    @endif

@endsection
