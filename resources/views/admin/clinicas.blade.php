{{--
    Admin → Clínicas e hospitais. Dados: Admin\ClinicaController@index (README §7.3).

    Consulta: dados de cadastro (CNPJ conferido na base simulada), situação da
    conta e unidades. Para bloquear uma clínica, o caminho é a tela Usuários
    (o link já vai com o e-mail da conta na busca).
--}}
@extends('layouts.painel')

@section('titulo', 'Clínicas e hospitais')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    use App\Support\Documento;
    $situacoes = ['ativo' => ['Ativa', 'verde'], 'bloqueado' => ['Bloqueada', 'rosa'], 'inativo' => ['Inativa', 'cinza']];
    $tipos = ['clinica' => 'Clínica', 'hospital' => 'Hospital', 'consultorio' => 'Consultório'];
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Clínicas e hospitais</h1>
            <p class="fm-subtitulo">Estabelecimentos cadastrados e as unidades de cada um.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.clinicas') }}" class="fm-filtros">
        <div class="fm-campo" style="flex-grow: 3;">
            <label for="busca">Buscar</label>
            <input id="busca" name="busca" value="{{ request('busca') }}" placeholder="Nome fantasia ou razão social" maxlength="100">
        </div>
        <button type="submit" class="fm-botao fm-botao--pequeno">Buscar</button>
        @if (request()->filled('busca'))
            <a href="{{ route('admin.clinicas') }}" class="fm-botao fm-botao--suave fm-botao--pequeno">Limpar</a>
        @endif
    </form>

    @if ($clinicas->isNotEmpty())
        <div class="fm-locais">
            @foreach ($clinicas as $c)
                @php [$rotuloSit, $tomSit] = $situacoes[$c->user->status] ?? [ucfirst($c->user->status), 'cinza']; @endphp
                <article class="fm-painel fm-local">
                    <header class="fm-painel__topo">
                        <h2 class="fm-painel__titulo"><x-icone nome="building" /> {{ $c->nome_fantasia }}</h2>
                        <span class="fm-etiqueta fm-etiqueta--{{ $tomSit }}">{{ $rotuloSit }}</span>
                    </header>

                    <p class="fm-local__dono">{{ $c->razao_social }}</p>
                    <p class="fm-campo__ajuda">CNPJ {{ Documento::cnpj($c->cnpj) }} · conferido na base simulada</p>

                    <ul class="fm-horario-lista" style="grid-template-columns: minmax(0, 1fr);">
                        @forelse ($c->locais as $l)
                            <li>
                                <span>{{ $l->nome }}</span>
                                <strong>{{ $tipos[$l->tipo] ?? $l->tipo }} · {{ $l->cidade }}/{{ $l->uf }}@unless ($l->ativo) · desativada @endunless</strong>
                            </li>
                        @empty
                            <li><em>Sem unidade cadastrada</em></li>
                        @endforelse
                    </ul>

                    <div class="fm-acoes-topo" style="margin-top: 12px;">
                        @if ($c->user->estaAtivo())
                            <a href="{{ route('publico.clinica', $c) }}" class="fm-pilula fm-pilula--pequena" target="_blank" rel="noopener">Perfil público <x-icone nome="chevron-right" /></a>
                        @endif
                        <a href="{{ route('admin.usuarios', ['busca' => $c->user->email]) }}" class="fm-pilula fm-pilula--pequena">Conta de acesso <x-icone nome="chevron-right" /></a>
                    </div>
                </article>
            @endforeach
        </div>

        {{ $clinicas->links('painel.parciais.paginacao') }}
    @else
        <section class="fm-painel" style="margin-top: 18px;">
            <p class="fm-vazio">Nenhuma clínica{{ request()->filled('busca') ? ' encontrada com essa busca' : ' cadastrada' }}.</p>
        </section>
    @endif

@endsection
