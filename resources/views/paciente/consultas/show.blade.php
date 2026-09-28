{{--
    Paciente → detalhe de uma consulta. Dados: Paciente\ConsultaController@show
    (a Policy já garantiu que a consulta é deste paciente).

    Ações conforme a situação:
    - agendada e no futuro → remarcar ou cancelar
    - realizada e sem avaliação → avaliar
    - qualquer uma → agendar de novo com o mesmo médico
--}}
@extends('layouts.painel')

@section('titulo', 'Consulta')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    use App\Support\Formatador;
    $consulta->loadMissing('medico.user', 'especialidade', 'vinculo.local', 'pacientePlano.plano.convenio', 'avaliacao');
    $st = Formatador::status($consulta->status);
    $podeCancelar = $consulta->podeSerCancelada();
    $local = $consulta->vinculo->local;
@endphp

@section('conteudo')

    <a href="{{ route('paciente.consultas') }}" class="fm-voltar" style="display: inline-flex; align-items: center; gap: 6px; margin-bottom: 10px; font-weight: 600; color: var(--fm-azul);">
        <x-icone nome="chevron-left" /> Minhas consultas
    </a>

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">{{ $consulta->especialidade->nome }}</h1>
            <p class="fm-subtitulo">{{ Formatador::dataExtensa($consulta->data_consulta) }}, às {{ Formatador::hora($consulta->horario) }}</p>
        </div>
        <span class="fm-etiqueta fm-etiqueta--{{ $st['tom'] }}" style="font-size: 14px; padding: 6px 14px;">{{ $st['rotulo'] }}</span>
    </div>

    <div class="fm-duas" style="margin-top: 18px;">
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="list" /> Detalhes</h2>
            </header>

            <ul class="fm-info fm-info--lista" style="list-style: none; padding: 0; margin: 0;">
                <li class="fm-info__item">
                    <span class="fm-info__icone fm-tom-azul"><x-icone nome="doctors" /></span>
                    <div>
                        <span class="fm-info__rotulo">Médico</span>
                        <a href="{{ route('publico.medico', $consulta->medico) }}" class="fm-info__valor" style="text-decoration: underline;">{{ $consulta->medico->user->name }}</a>
                        <span class="fm-info__nota">CRM {{ $consulta->medico->crm }}/{{ $consulta->medico->uf }}</span>
                    </div>
                </li>
                <li class="fm-info__item">
                    <span class="fm-info__icone fm-tom-verde"><x-icone :nome="$local->tipo === 'hospital' ? 'hospital' : 'pin'" /></span>
                    <div>
                        <span class="fm-info__rotulo">Onde</span>
                        <strong class="fm-info__valor">{{ $local->nome }}</strong>
                        <span class="fm-info__nota">{{ $local->endereco_completo }}</span>
                        @if ($local->telefone)<span class="fm-info__nota">Telefone: {{ \App\Support\Formatador::telefone($local->telefone) }}</span>@endif
                    </div>
                </li>
                <li class="fm-info__item">
                    <span class="fm-info__icone fm-tom-roxo"><x-icone :nome="$consulta->forma_pagamento === 'convenio' ? 'shield' : 'money'" /></span>
                    <div>
                        <span class="fm-info__rotulo">Pagamento</span>
                        @if ($consulta->forma_pagamento === 'convenio')
                            <strong class="fm-info__valor">Convênio</strong>
                            @if ($consulta->pacientePlano)
                                <span class="fm-info__nota">{{ $consulta->pacientePlano->plano->convenio->nome }} — {{ $consulta->pacientePlano->plano->nome }}</span>
                            @endif
                            {{-- Aviso OBRIGATÓRIO (AGENTS.md §3): o convênio é aceito pelo médico,
                                 não pelo endereço. Esta é a tela que abre logo depois de agendar. --}}
                            @if ($consulta->status === 'agendada')
                                <span class="fm-info__nota">Confirme na recepção se o seu plano é aceito neste endereço.</span>
                            @endif
                        @else
                            <strong class="fm-info__valor">Particular · R$ {{ number_format((float) $consulta->valor, 2, ',', '.') }}</strong>
                        @endif
                    </div>
                </li>
                @if ($consulta->observacoes)
                    <li class="fm-info__item">
                        <span class="fm-info__icone fm-tom-ciano"><x-icone nome="pencil" /></span>
                        <div><span class="fm-info__rotulo">Suas observações</span><span class="fm-info__valor" style="font-weight: 400;">{{ $consulta->observacoes }}</span></div>
                    </li>
                @endif
                @if ($consulta->status === 'cancelada')
                    <li class="fm-info__item">
                        <span class="fm-info__icone fm-tom-rosa"><x-icone nome="x-circle" /></span>
                        <div>
                            <span class="fm-info__rotulo">Cancelada em</span>
                            <strong class="fm-info__valor">{{ $consulta->cancelada_em?->format('d/m/Y H:i') }}</strong>
                            @if ($consulta->motivo_cancelamento)<span class="fm-info__nota">{{ $consulta->motivo_cancelamento }}</span>@endif
                        </div>
                    </li>
                @endif
            </ul>
        </section>

        <section class="fm-painel" x-data="{ cancelando: {{ $errors->has('motivo') ? 'true' : 'false' }} }">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo"><x-icone nome="bolt" /> O que fazer</h2>
            </header>

            <div style="display: grid; gap: 10px;">
                @if ($podeCancelar)
                    <a href="{{ route('paciente.consultas.remarcar', $consulta) }}" class="fm-botao" style="justify-content: center;">
                        <x-icone nome="calendar" /> Remarcar
                    </a>
                    <button type="button" class="fm-botao fm-botao--suave" style="justify-content: center;" @click="cancelando = !cancelando" x-show="!cancelando">
                        <x-icone nome="x-circle" /> Cancelar consulta
                    </button>

                    <form method="POST" action="{{ route('paciente.consultas.cancelar', $consulta) }}" x-show="cancelando" x-cloak
                          style="display: grid; gap: 10px; padding: 14px; border: 1px solid var(--fm-borda); border-radius: 12px;">
                        @csrf
                        <strong style="color: var(--fm-titulo);">Cancelar esta consulta?</strong>
                        @if ($consulta->ehCancelamentoTardio())
                            <p class="fm-meta">Faltam menos de 24h: o cancelamento fica registrado como tardio.</p>
                        @endif
                        <label class="fm-meta" for="motivo">Motivo (opcional)</label>
                        <input id="motivo" name="motivo" maxlength="255" value="{{ old('motivo') }}"
                               style="height: 40px; padding: 0 10px; border: 1px solid var(--fm-borda-forte); border-radius: 10px;">
                        @error('motivo') <span class="fm-campo__erro">{{ $message }}</span> @enderror
                        <div style="display: flex; gap: 8px;">
                            <button type="submit" class="fm-botao fm-botao--perigo">Sim, cancelar</button>
                            <button type="button" class="fm-botao fm-botao--suave" @click="cancelando = false">Voltar</button>
                        </div>
                    </form>
                @endif

                @if ($consulta->podeSerAvaliada())
                    <a href="{{ route('paciente.consultas.avaliar', $consulta) }}" class="fm-botao" style="justify-content: center;">
                        <x-icone nome="star" /> Avaliar atendimento
                    </a>
                @elseif ($consulta->avaliacao)
                    <p class="fm-meta">Você avaliou com {{ $consulta->avaliacao->estrelas }} {{ $consulta->avaliacao->estrelas === 1 ? 'estrela' : 'estrelas' }}. Obrigado!</p>
                @endif

                <a href="{{ route('agendamento.horario', $consulta->vinculo_id) }}" class="fm-botao fm-botao--suave" style="justify-content: center;">
                    <x-icone nome="plus" /> Agendar de novo com este médico
                </a>
            </div>
        </section>
    </div>

@endsection
