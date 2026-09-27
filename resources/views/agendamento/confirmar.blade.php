{{--
    Agendamento, passo 2 de 2: conferir e confirmar.
    Dados: AgendamentoController@confirmar. Grava em AgendamentoController@salvar.

    O valor mostrado é calculado no servidor. O formulário NÃO manda
    valor: quem decide o preço na hora de gravar é o servidor de novo
    (no protótipo antigo dava para agendar por R$ 0,00 editando o HTML).
--}}
@extends('layouts.painel')

@section('titulo', 'Confirmar consulta')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/agendamento.css') }}">
@endpush

@php
    use App\Support\Formatador;
    $aceitos = $vinculo->medico->convenios()->where('convenios.ativo', true)->pluck('convenios.id');
    $planosAceitos = $planos->filter(fn ($p) => $aceitos->contains($p->plano->convenio_id));
    $ehConvenio = $formaPagamento === 'convenio';
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">{{ request('remarcar_consulta_id') ? 'Confirmar remarcação' : 'Confirmar consulta' }}</h1>
            <p class="fm-subtitulo">Passo 2 de 2 — confira os dados e confirme.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="fm-flash fm-flash--erro" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('agendamento.salvar') }}" class="ag-grade">
        @csrf
        <input type="hidden" name="vinculo_id" value="{{ $vinculo->id }}">
        <input type="hidden" name="especialidade_id" value="{{ $especialidade->id }}">
        <input type="hidden" name="data_consulta" value="{{ $data->toDateString() }}">
        <input type="hidden" name="horario" value="{{ $horario }}">
        <input type="hidden" name="forma_pagamento" value="{{ $formaPagamento }}">
        @if (request('remarcar_consulta_id') || old('remarcar_consulta_id'))
            <input type="hidden" name="remarcar_consulta_id" value="{{ (int) (request('remarcar_consulta_id') ?: old('remarcar_consulta_id')) }}">
        @endif
        @if (request('origem') === 'clinica')
            <input type="hidden" name="origem" value="clinica">
        @endif

        <section class="fm-painel">
            <div class="ag-medico">
                <span class="fm-avatar fm-avatar--grande">{{ Formatador::iniciais($vinculo->medico->user->name) }}</span>
                <div>
                    <strong>{{ $vinculo->medico->user->name }}</strong>
                    <span>{{ $especialidade->nome }}</span>
                </div>
            </div>

            <div class="ag-bloco" style="margin-top: 20px;">
                <ul class="fm-info fm-info--lista" style="list-style: none; padding: 0; margin: 0; display: grid; gap: 14px;">
                    <li class="fm-info__item">
                        <span class="fm-info__icone fm-tom-azul"><x-icone nome="calendar" /></span>
                        <div><span class="fm-info__rotulo">Quando</span>
                            <strong class="fm-info__valor">{{ Formatador::dataExtensa($data) }}, às {{ $horario }}</strong></div>
                    </li>
                    <li class="fm-info__item">
                        <span class="fm-info__icone fm-tom-verde"><x-icone :nome="$vinculo->local->tipo === 'hospital' ? 'hospital' : 'pin'" /></span>
                        <div><span class="fm-info__rotulo">Onde</span>
                            <strong class="fm-info__valor">{{ $vinculo->local->nome }}</strong>
                            <span class="fm-info__nota">{{ $vinculo->local->endereco_completo }}</span></div>
                    </li>
                </ul>
            </div>

            @if ($ehConvenio)
                <div class="ag-bloco">
                    <p class="ag-passo">Carteirinha</p>
                    @if ($planos->isEmpty())
                        <p class="fm-vazio">
                            Você não tem carteirinha ativa. <a href="{{ route('paciente.planos') }}" style="font-weight: 700;">Cadastrar carteirinha</a>
                            ou volte e escolha particular.
                        </p>
                    @elseif ($planosAceitos->isEmpty())
                        <p class="fm-vazio">Este médico não atende o(s) convênio(s) das suas carteirinhas. Volte e escolha particular.</p>
                    @else
                        <div class="ag-carteiras">
                            @foreach ($planos as $pp)
                                @php $aceito = $aceitos->contains($pp->plano->convenio_id); @endphp
                                <label class="ag-carteira" @if (! $aceito) style="opacity: .5; cursor: not-allowed;" @endif>
                                    <input type="radio" name="paciente_plano_id" value="{{ $pp->id }}" @disabled(! $aceito)
                                           @checked(old('paciente_plano_id', $planosAceitos->first()?->id) == $pp->id)>
                                    <span>
                                        <strong>{{ $pp->plano->convenio->nome }} — {{ $pp->plano->nome }}</strong>
                                        <small>Nº {{ $pp->numero_carteirinha }}{{ $aceito ? '' : ' · não aceito por este médico' }}</small>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @if ($avisoConvenio)
                            <p class="fm-meta" style="margin-top: 10px;">Leve a carteirinha no dia. Confirme na recepção se o seu plano é aceito neste endereço.</p>
                        @endif
                    @endif
                    @error('paciente_plano_id') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                </div>
            @endif

            <div class="ag-bloco">
                <label for="observacoes" class="ag-passo" style="display: block;">Observações para o médico (opcional)</label>
                <textarea id="observacoes" name="observacoes" class="ag-textarea" maxlength="500"
                          placeholder="Ex.: é retorno, preciso de atendimento em cadeira de rodas...">{{ old('observacoes') }}</textarea>
                <p class="fm-meta">Não escreva sintomas ou informações de saúde aqui.</p>
            </div>
        </section>

        <aside class="fm-painel ag-resumo">
            <h2 class="fm-painel__titulo" style="margin-bottom: 16px;"><x-icone nome="list" /> Resumo</h2>
            <dl>
                <div><dt>Pagamento</dt><dd>{{ $ehConvenio ? 'Convênio' : 'Particular' }}</dd></div>
                <div>
                    <dt>Valor</dt>
                    <dd class="ag-valor">
                        @if ($ehConvenio)
                            Pelo convênio
                        @elseif ($valor !== null)
                            R$ {{ number_format($valor, 2, ',', '.') }}
                        @else
                            —
                        @endif
                    </dd>
                </div>
            </dl>
            @if (! $ehConvenio && $valor === null)
                <p class="fm-meta">Este médico não aceita particular aqui ou não tem preço para essa especialidade.</p>
            @endif

            <button type="submit" class="fm-botao" @disabled(($ehConvenio && $planosAceitos->isEmpty()) || (! $ehConvenio && $valor === null))>
                <x-icone nome="check-circle" /> Confirmar agendamento
            </button>
            <a href="{{ route('agendamento.horario', array_filter(['vinculo' => $vinculo->id, 'remarcar' => request('remarcar_consulta_id')])) }}" class="fm-botao fm-botao--suave" style="margin-top: 8px; width: 100%; justify-content: center;">
                Voltar e trocar
            </a>
            <p class="fm-meta" style="margin-top: 12px;">
                Dá para cancelar pelo menu "Minhas consultas" até o horário da consulta.
                Com menos de 24h de antecedência, o cancelamento fica registrado como tardio.
            </p>
        </aside>
    </form>

@endsection
