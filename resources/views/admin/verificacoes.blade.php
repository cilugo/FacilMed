{{--
    Admin → Verificar CRM. Dados: Admin\VerificacaoController@index (README §7.3).

    Desde 24/09 o CRM é conferido NA HORA DO CADASTRO na base simulada, então
    a fila de pendentes costuma estar vazia. A tela vira histórico e serve para
    REJEITAR (tirar da plataforma) — o que cancela as consultas futuras.

    Texto: "conferido na base simulada do FacilMed". NUNCA "validado no CFM".
--}}
@extends('layouts.painel')

@section('titulo', 'Verificar CRM')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    use App\Support\Formatador;
    $formVolta = old('_form');
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Verificar CRM</h1>
            <p class="fm-subtitulo">Situação do CRM dos médicos da plataforma.</p>
        </div>
    </div>

    <p class="fm-dica">
        <x-icone nome="lightbulb" />
        <span>O CRM é <strong>conferido automaticamente no cadastro, na base simulada do FacilMed</strong> (projeto
            acadêmico: nenhuma consulta é feita ao CFM de verdade). Por isso a fila costuma estar vazia. Aqui você acompanha
            o histórico e pode <strong>rejeitar</strong> um médico — ele some da busca e as consultas futuras dele são canceladas.</span>
    </p>

    {{-- ===================== PENDENTES ===================== --}}
    <section class="fm-painel" style="margin-top: 18px;">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="clock" /> Aguardando decisão</h2>
            <span class="fm-etiqueta fm-etiqueta--{{ $pendentes->isEmpty() ? 'verde' : 'ambar' }}">{{ $pendentes->count() }}</span>
        </header>

        @forelse ($pendentes as $m)
            @include('admin.parciais.verificacao-linha', ['m' => $m, 'pendente' => true])
        @empty
            <p class="fm-vazio">Nenhum CRM pendente. Os cadastros novos são conferidos na hora.</p>
        @endforelse
    </section>

    {{-- ===================== RECENTES ===================== --}}
    <section class="fm-painel">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="badge" /> Decisões recentes</h2>
            <span class="fm-painel__periodo">últimas 10</span>
        </header>

        @forelse ($recentes as $m)
            @include('admin.parciais.verificacao-linha', ['m' => $m, 'pendente' => false])
        @empty
            <p class="fm-vazio">Nenhum médico cadastrado ainda.</p>
        @endforelse
    </section>

@endsection
