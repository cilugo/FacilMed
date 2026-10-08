{{--
    Clínica → Convênios (item do menu lateral).
    Dados de App\Http\Controllers\Clinica\ConvenioController@index.

    Duas partes:
      1. Cobertura: os convênios que a clínica atende HOJE, derivados dos
         médicos vinculados (quem aceita convênio é o médico).
      2. Médicos por unidade: a clínica liga/desliga "atende por convênio
         nesta unidade" (vinculos.aceita_convenio).

    A clínica NÃO edita a lista de convênios do médico — ela vale para
    todos os lugares onde ele atende. Isso está escrito na tela.
--}}
@extends('layouts.painel')

@section('titulo', 'Convênios')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Convênios</h1>
            <p class="fm-subtitulo">Os convênios atendidos nas suas unidades, pelos médicos vinculados à clínica.</p>
        </div>
    </div>

    <div class="fm-grade fm-grade--cartoes fm-grade--tres">
        @foreach ($cartoes as $c)
            @include('painel.parciais.cartao', ['c' => $c])
        @endforeach
    </div>

    <p class="fm-dica">
        <x-icone nome="lightbulb" />
        <span>
            No PointMed, <strong>quem aceita o convênio é o médico</strong>. Aqui você escolhe, em cada unidade,
            se o médico atende por convênio. Para mudar <em>quais</em> convênios ele aceita, use
            <a href="{{ route('clinica.medicos') }}">Meus médicos → Editar perfil</a>. Os convênios da plataforma são fictícios, criados para demonstração.
        </span>
    </p>

    {{-- ============================ COBERTURA ============================ --}}
    <section class="fm-painel" style="margin-top: 18px;">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="shield" /> Convênios atendidos</h2>
        </header>

        @if ($cobertura->isNotEmpty())
            <div class="fm-cobertura">
                @foreach ($cobertura as $item)
                    <article class="fm-cobertura__item">
                        <div class="fm-cobertura__topo">
                            <span class="fm-cobertura__nome">
                                <x-icone nome="shield" />
                                {{ $item['convenio']->nome }}
                            </span>

                            @if ($item['convenio']->ativo)
                                <span class="fm-etiqueta fm-etiqueta--verde">Atendido</span>
                            @else
                                <span class="fm-etiqueta fm-etiqueta--cinza" title="O administrador desativou este convênio na plataforma">
                                    Desativado na plataforma
                                </span>
                            @endif
                        </div>

                        <div>
                            <p class="fm-cobertura__rotulo">Planos</p>
                            @if ($item['planos']->isNotEmpty())
                                <div class="fm-chips" style="margin-top: 6px;">
                                    @foreach ($item['planos'] as $plano)
                                        <span class="fm-chip">{{ $plano->nome }} <small>{{ $plano->tipo_rotulo }}</small></span>
                                    @endforeach
                                </div>
                            @else
                                <p class="fm-campo__ajuda">Nenhum plano ativo.</p>
                            @endif
                        </div>

                        <div>
                            <p class="fm-cobertura__rotulo">Atendido por</p>
                            <div class="fm-chips" style="margin-top: 6px;">
                                @foreach ($item['medicos'] as $nomeMedico)
                                    <span class="fm-chip fm-chip--cinza">{{ $nomeMedico }}</span>
                                @endforeach
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <p class="fm-vazio">
                Nenhum convênio atendido ainda. Ligue "Atende por convênio" para um médico abaixo —
                ele precisa ter convênios cadastrados no perfil.
            </p>
        @endif
    </section>

    {{-- ============================ MÉDICOS POR UNIDADE ============================ --}}
    <section class="fm-painel">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="doctors" /> Médicos por unidade</h2>
            @if (\Illuminate\Support\Facades\Route::has('clinica.medicos'))
                <a href="{{ route('clinica.medicos') }}" class="fm-pilula fm-pilula--pequena">
                    Meus médicos <x-icone nome="chevron-right" />
                </a>
            @endif
        </header>

        @if ($vinculos->isNotEmpty())
            <div class="fm-rolagem">
                <table class="fm-tabela-crud">
                    <thead>
                        <tr>
                            <th>Médico</th>
                            <th>Unidade</th>
                            <th>Convênios que o médico aceita</th>
                            <th>Atende por convênio aqui</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vinculos as $v)
                            <tr>
                                <td><strong>{{ $v->medico->nome }}</strong></td>
                                <td>{{ $v->local->nome }}</td>
                                <td>
                                    @if ($v->medico->convenios->isNotEmpty())
                                        <div class="fm-chips">
                                            @foreach ($v->medico->convenios as $conv)
                                                <span class="fm-chip {{ $conv->ativo ? '' : 'fm-chip--cinza' }}">{{ $conv->nome }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="fm-campo__ajuda">Nenhum cadastrado</span>
                                    @endif
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('clinica.convenios.salvar') }}">
                                        @csrf
                                        <input type="hidden" name="vinculo_id" value="{{ $v->id }}">
                                        <input type="hidden" name="aceita_convenio" value="{{ $v->aceita_convenio ? 0 : 1 }}">
                                        <button type="submit" class="fm-switch" role="switch"
                                                aria-checked="{{ $v->aceita_convenio ? 'true' : 'false' }}"
                                                aria-label="Atende por convênio: {{ $v->medico->nome }} em {{ $v->local->nome }}">
                                            <span class="fm-switch__trilho"></span>
                                            {{ $v->aceita_convenio ? 'Sim' : 'Não' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="fm-vazio">Nenhum médico vinculado às suas unidades ainda.</p>
        @endif
    </section>

@endsection
