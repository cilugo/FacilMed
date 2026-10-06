{{--
    Clínica → Meus médicos. Dados: Clinica\MedicoController@index (README §7.2).

    Um cartão por vínculo (médico × unidade). 01/10/2026: o médico não tem
    conta — a clínica edita o perfil dele aqui ("Editar perfil"). O preço
    cadastrado... (05/10: sem preço por médico; a faixa é da unidade.)
--}}
@extends('layouts.painel')

@section('titulo', 'Meus médicos')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    use App\Support\Formatador;
    $reais = fn ($v) => number_format((float) $v, 2, ',', '.');
    $unidadeAtual = (int) request('unidade') ?: null;
    $lista = $unidadeAtual ? $vinculos->where('local_id', $unidadeAtual) : $vinculos;
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Meus médicos</h1>
            <p class="fm-subtitulo">Quem atende nas unidades da clínica.</p>
        </div>
        <a href="{{ route('clinica.medicos.novo') }}" class="fm-botao"><x-icone nome="plus" /> Cadastrar médico</a>
    </div>

    @if ($unidades->count() > 1)
        <nav class="fm-abas" aria-label="Filtrar por unidade" style="margin-top: 18px; flex-wrap: wrap;">
            <a href="{{ route('clinica.medicos') }}" class="fm-aba {{ $unidadeAtual ? '' : 'is-ativa' }}">Todas</a>
            @foreach ($unidades as $u)
                <a href="{{ route('clinica.medicos', ['unidade' => $u->id]) }}" class="fm-aba {{ $unidadeAtual === $u->id ? 'is-ativa' : '' }}">{{ $u->nome }}</a>
            @endforeach
        </nav>
    @endif

    @if ($lista->isNotEmpty())
        <div class="fm-locais">
            @foreach ($lista as $i => $v)
                <article class="fm-painel fm-medico" x-data="{ saindo: false }">
                    <div class="fm-medico__topo">
                        <x-avatar :nome="$v->medico->nome" :foto="$v->medico->foto_url" class="fm-avatar--cor" style="background: {{ Formatador::corAvatar($v->medico->id) }};" />
                        <div class="fm-medico__nome">
                            <strong>{{ $v->medico->nome }}</strong>
                            <span>CRM {{ $v->medico->crm }}/{{ $v->medico->uf }}</span>
                        </div>
                    </div>

                    <p class="fm-local__endereco"><x-icone nome="building" /> {{ $v->local->nome }}</p>

                    <div class="fm-chips" style="margin-top: 10px;">
                        @foreach ($v->medico->especialidades as $esp)
                            <span class="fm-chip">{{ $esp->nome }}</span>
                        @endforeach
                        @if ($v->aceita_convenio)
                            <span class="fm-chip fm-chip--cinza">Atende convênio</span>
                        @endif
                    </div>

                    <div class="fm-acoes-topo" style="margin-top: 12px;">
                        <a href="{{ route('clinica.medicos.editar', $v->medico) }}" class="fm-pilula fm-pilula--pequena">Editar perfil <x-icone nome="chevron-right" /></a>
                        <button type="button" class="fm-botao fm-botao--perigo fm-botao--pequeno" @click="saindo = !saindo" :aria-expanded="saindo">Desvincular</button>
                    </div>

                    <form method="POST" action="{{ route('clinica.medicos.desvincular', $v) }}" class="fm-form fm-form--caixa" x-show="saindo" x-cloak>
                        @csrf
                        @method('DELETE')
                        <p style="font-size: 14px;">{{ $v->medico->nome }} deixa de aparecer em <strong>{{ $v->local->nome }}</strong>.
                            O perfil dele continua no PointMed (e nas outras unidades onde ele atende).</p>
                        <div class="fm-form__acoes">
                            <button type="button" class="fm-botao fm-botao--suave fm-botao--pequeno" @click="saindo = false">Voltar</button>
                            <button type="submit" class="fm-botao fm-botao--perigo fm-botao--pequeno">Confirmar desvínculo</button>
                        </div>
                    </form>
                </article>
            @endforeach
        </div>
    @else
        <section class="fm-painel" style="margin-top: 18px;">
            <p class="fm-vazio fm-vazio--acao">
                Nenhum médico {{ $unidadeAtual ? 'nesta unidade' : 'vinculado à clínica' }} ainda.
                <a href="{{ route('clinica.medicos.novo') }}" class="fm-pilula fm-pilula--pequena">Cadastrar médico <x-icone nome="chevron-right" /></a>
            </p>
        </section>
    @endif

@endsection
