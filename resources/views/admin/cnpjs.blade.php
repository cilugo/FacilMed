{{--
    Admin → Verificar CNPJ (01/10/2026). Dados: Admin\CnpjController@index.

    Substitui "Verificar CRM": quem cadastra médico agora é a clínica, e o
    admin acompanha o CNPJ das clínicas e hospitais. Só leitura; para tirar
    uma clínica do ar, o caminho é bloquear a conta em Usuários.

    Texto: "conferido na base simulada do PointMed". NUNCA "validado na Receita".
--}}
@extends('layouts.painel')

@section('titulo', 'Verificar CNPJ')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    use App\Support\Documento;
    $situacoes = ['ativa' => 'verde', 'baixada' => 'rosa', 'suspensa' => 'ambar', 'inapta' => 'rosa'];
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Verificar CNPJ</h1>
            <p class="fm-subtitulo">Situação do CNPJ das clínicas e hospitais da plataforma.</p>
        </div>
    </div>

    <p class="fm-dica">
        <x-icone nome="lightbulb" />
        <span>O CNPJ é <strong>conferido automaticamente no cadastro, na base simulada do PointMed</strong> (projeto
            acadêmico: nenhuma consulta é feita à Receita Federal de verdade). Aqui aparece a situação <strong>atual</strong>
            de cada CNPJ nessa base. Se algum deixou de estar ativo, bloqueie a conta da clínica em <em>Usuários</em>.</span>
    </p>

    <form method="GET" action="{{ route('admin.cnpjs') }}" class="fm-filtros">
        <div class="fm-campo">
            <label for="situacao">Mostrar</label>
            <select id="situacao" name="situacao">
                <option value="">Todas as clínicas</option>
                <option value="problema" @selected(request('situacao') === 'problema')>Só CNPJ com problema ({{ $problemas }})</option>
            </select>
        </div>
        <button type="submit" class="fm-botao fm-botao--pequeno">Filtrar</button>
    </form>

    <section class="fm-painel">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="badge" /> CNPJs</h2>
            <span class="fm-etiqueta fm-etiqueta--{{ $problemas === 0 ? 'verde' : 'ambar' }}">
                {{ $problemas === 0 ? 'Todos ativos na base' : $problemas . ($problemas === 1 ? ' com problema' : ' com problema') }}
            </span>
        </header>

        @if ($linhas->isNotEmpty())
            <ul class="fm-lista">
                @foreach ($linhas as $l)
                    @php $c = $l->clinica; @endphp
                    <li class="fm-conta">
                        <div class="fm-conta__linha">
                            <x-avatar :nome="$c->nome_fantasia" :foto="$c->user?->foto_url" />
                            <div class="fm-conta__info">
                                <strong>{{ $c->nome_fantasia }}</strong>
                                <span>CNPJ {{ Documento::cnpj($c->cnpj) }} · {{ $c->razao_social }}</span>
                            </div>
                            <div class="fm-chips fm-conta__etiquetas">
                                @if ($l->registro)
                                    <span class="fm-etiqueta fm-etiqueta--{{ $situacoes[$l->registro->situacao] ?? 'cinza' }}">
                                        {{ ucfirst($l->registro->situacao) }} na base simulada
                                    </span>
                                @else
                                    <span class="fm-etiqueta fm-etiqueta--rosa">Não encontrado na base simulada</span>
                                @endif
                                @unless ($c->user?->estaAtivo())
                                    <span class="fm-chip fm-chip--cinza">Conta {{ $c->user?->status === 'bloqueado' ? 'bloqueada' : 'inativa' }}</span>
                                @endunless
                            </div>
                            <div class="fm-conta__acoes">
                                <a href="{{ route('admin.usuarios', ['busca' => $c->user?->email]) }}" class="fm-pilula fm-pilula--pequena">Conta de acesso <x-icone nome="chevron-right" /></a>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="fm-vazio">{{ request('situacao') === 'problema' ? 'Nenhum CNPJ com problema. Todos estão ativos na base simulada.' : 'Nenhuma clínica cadastrada.' }}</p>
        @endif
    </section>

@endsection
