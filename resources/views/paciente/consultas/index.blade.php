{{--
    Paciente → Minhas consultas. Dados: Paciente\ConsultaController@index.
    Filtro por status via ?status= (agendada, realizada, cancelada, nao_compareceu).
--}}
@extends('layouts.painel')

@section('titulo', 'Minhas consultas')

@php
    use App\Support\Formatador;
    $filtroAtual = request('status');
    $abas = ['' => 'Todas', 'agendada' => 'Agendadas', 'realizada' => 'Realizadas', 'cancelada' => 'Canceladas'];
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Minhas consultas</h1>
            <p class="fm-subtitulo">Todas as suas consultas, das próximas às mais antigas.</p>
        </div>
        <a href="{{ route('busca.index') }}" class="fm-botao"><x-icone nome="plus" /> Agendar consulta</a>
    </div>

    <section class="fm-painel" style="margin-top: 18px;">
        <header class="fm-painel__topo">
            <nav class="fm-abas" aria-label="Filtrar por situação">
                @foreach ($abas as $valor => $rotulo)
                    <a href="{{ route('paciente.consultas', array_filter(['status' => $valor])) }}"
                       class="fm-aba {{ (string) $filtroAtual === (string) $valor ? 'is-ativa' : '' }}">{{ $rotulo }}</a>
                @endforeach
            </nav>
        </header>

        @if ($consultas->isEmpty())
            <div class="fm-vazio fm-vazio--acao">
                <p>Nenhuma consulta {{ $filtroAtual ? 'com essa situação' : 'ainda' }}.</p>
                <a href="{{ route('busca.index') }}" class="fm-botao">Encontrar um médico</a>
            </div>
        @else
            <ul class="fm-lista">
                @foreach ($consultas as $c)
                    @php $st = Formatador::status($c->status); @endphp
                    <li>
                        <a href="{{ route('paciente.consultas.show', $c) }}" class="fm-historico">
                            <span class="fm-historico__data">
                                {{ Formatador::dataCurta($c->data_consulta) }}<br>
                                <strong style="color: var(--fm-titulo);">{{ Formatador::hora($c->horario) }}</strong>
                            </span>
                            <span class="fm-historico__medico">{{ $c->medico->user->name }}</span>
                            <span class="fm-historico__esp">{{ $c->especialidade->nome }} · {{ $c->vinculo->local->nome }}</span>
                            <span class="fm-etiqueta fm-etiqueta--{{ $st['tom'] }}">{{ $st['rotulo'] }}</span>
                            <x-icone nome="chevron-right" class="fm-historico__seta" />
                        </a>
                    </li>
                @endforeach
            </ul>

            @if ($consultas->hasPages())
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; gap: 10px;">
                    <span class="fm-meta">Página {{ $consultas->currentPage() }} de {{ $consultas->lastPage() }}</span>
                    <span style="display: flex; gap: 8px;">
                        @if (! $consultas->onFirstPage())
                            <a href="{{ $consultas->previousPageUrl() }}" class="fm-pilula fm-pilula--pequena"><x-icone nome="chevron-left" /> Anteriores</a>
                        @endif
                        @if ($consultas->hasMorePages())
                            <a href="{{ $consultas->nextPageUrl() }}" class="fm-pilula fm-pilula--pequena">Próximas <x-icone nome="chevron-right" /></a>
                        @endif
                    </span>
                </div>
            @endif
        @endif
    </section>

@endsection
