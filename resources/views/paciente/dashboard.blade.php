{{--
    Dashboard do paciente. Dados prontos de
    App\Http\Controllers\Paciente\DashboardController.

    NÃO existe aqui "Resumo da sua saúde", exames, medicamentos nem
    documentos: o sistema não tem esse dado e AGENTS.md §6 proíbe qualquer
    leitura clínica. Não acrescente.
--}}
@extends('layouts.painel')

@section('titulo', 'Início')

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Olá, {{ $saudacao }}!</h1>
            <p class="fm-subtitulo">Como podemos te ajudar hoje?</p>
        </div>

        <div class="fm-data">
            <x-icone nome="calendar" />
            <span>{{ $dataHoje }}</span>
        </div>
    </div>

    <div class="fm-grade fm-grade--cartoes">
        @foreach ($cartoes as $c)
            @include('painel.parciais.cartao', ['c' => $c])
        @endforeach
    </div>

    {{-- Próximos atendimentos --}}
    <section class="fm-painel">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo">
                <x-icone nome="calendar" />
                Próximos atendimentos
            </h2>
            <a href="{{ $proximasUrl }}" class="fm-pilula fm-pilula--pequena">
                Ver todos
                <x-icone nome="chevron-right" />
            </a>
        </header>

        @if (count($proximas) > 0)
            <ul class="fm-lista">
                @foreach ($proximas as $p)
                    <li class="fm-proximo">
                        <span class="fm-proximo__icone fm-tom-azul"><x-icone nome="calendar-check" /></span>

                        <div class="fm-proximo__quando">
                            <span>{{ $p['data'] }}</span>
                            <strong>{{ $p['hora'] }}</strong>
                        </div>

                        <div class="fm-proximo__quem">
                            <strong>{{ $p['medico'] }}</strong>
                            <span>{{ $p['especialidade'] }} · {{ $p['local'] }}</span>
                        </div>

                        <a href="{{ $p['url'] }}" class="fm-pilula fm-pilula--pequena">
                            Detalhes
                            <x-icone nome="chevron-right" />
                        </a>
                    </li>
                @endforeach
            </ul>

            @if ($avisoConvenio)
                {{-- Aviso obrigatório (AGENTS.md §6): o convênio é aceito pelo
                     médico, não pelo endereço. --}}
                <p class="fm-dica">
                    <x-icone nome="lightbulb" />
                    <span>Consulta por convênio? Confirme na recepção se o seu plano é aceito neste endereço.</span>
                </p>
            @endif
        @else
            <div class="fm-vazio fm-vazio--acao">
                <p>Você não tem consultas marcadas.</p>
                <a href="{{ $buscarUrl }}" class="fm-botao">Agendar consulta</a>
            </div>
        @endif
    </section>

    {{-- Últimas consultas --}}
    <section class="fm-painel">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo">
                <x-icone nome="clock" />
                Últimas consultas
            </h2>
            <a href="{{ $historicoUrl }}" class="fm-pilula fm-pilula--pequena">
                Ver histórico
                <x-icone nome="chevron-right" />
            </a>
        </header>

        @if (count($historico) > 0)
            <ul class="fm-lista">
                @foreach ($historico as $h)
                    <li>
                        <a href="{{ $h['url'] }}" class="fm-historico">
                            <span class="fm-historico__data">{{ $h['data'] }}</span>
                            <span class="fm-historico__medico">{{ $h['medico'] }}</span>
                            <span class="fm-historico__esp">{{ $h['especialidade'] }}</span>
                            <span class="fm-etiqueta fm-etiqueta--{{ $h['tom'] }}">{{ $h['status'] }}</span>
                            <x-icone nome="chevron-right" class="fm-historico__seta" />
                        </a>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="fm-vazio">Suas consultas passadas vão aparecer aqui.</p>
        @endif
    </section>

    <div class="fm-duas">

        {{-- Só aparece consulta REALIZADA e ainda sem avaliação (AGENTS.md §6) --}}
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo">
                    <x-icone nome="star" />
                    Consultas para avaliar
                </h2>
            </header>

            @if (count($paraAvaliar) > 0)
                <ul class="fm-lista">
                    @foreach ($paraAvaliar as $a)
                        <li class="fm-linha">
                            <span class="fm-linha__data">{{ $a['data'] }}</span>
                            <div class="fm-linha__info">
                                <strong>{{ $a['medico'] }}</strong>
                                <span>{{ $a['especialidade'] }}</span>
                            </div>
                            <a href="{{ $a['url'] }}" class="fm-pilula fm-pilula--pequena">
                                Avaliar
                                <x-icone nome="chevron-right" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="fm-vazio">Nenhuma consulta esperando avaliação.</p>
            @endif
        </section>

        {{-- Carteirinhas: o status vem da conferência humana da equipe --}}
        <section class="fm-painel">
            <header class="fm-painel__topo">
                <h2 class="fm-painel__titulo">
                    <x-icone nome="card" />
                    Meu plano de saúde
                </h2>
                <a href="{{ $planosUrl }}" class="fm-pilula fm-pilula--pequena">
                    Ver planos
                    <x-icone nome="chevron-right" />
                </a>
            </header>

            @if (count($carteirinhas) > 0)
                <ul class="fm-lista">
                    @foreach ($carteirinhas as $cp)
                        <li class="fm-linha">
                            <span class="fm-linha__icone fm-tom-rosa"><x-icone nome="shield" /></span>
                            <div class="fm-linha__info">
                                <strong>{{ $cp['nome'] }}</strong>
                            </div>
                            <span class="fm-etiqueta fm-etiqueta--{{ $cp['tom'] }}">{{ $cp['status'] }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="fm-dica">
                    <x-icone nome="lightbulb" />
                    <span>A carteirinha é conferida pela equipe FacilMed. Até lá, ela aparece como em conferência.</span>
                </p>
            @else
                <div class="fm-vazio fm-vazio--acao">
                    <p>Você ainda não cadastrou um plano. Sem plano, a consulta é particular.</p>
                    <a href="{{ $planosUrl }}" class="fm-botao fm-botao--suave">Cadastrar plano</a>
                </div>
            @endif
        </section>
    </div>

    {{-- Atalhos: os mesmos itens do menu --}}
    <section class="fm-painel">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo">
                <x-icone nome="bolt" />
                Acesso rápido
            </h2>
        </header>

        <ul class="fm-atalhos fm-atalhos--linha">
            @foreach ($atalhos as $atalho)
                <li>
                    <a href="{{ $atalho['url'] }}" class="fm-atalho">
                        <span class="fm-atalho__icone"><x-icone :nome="$atalho['icone']" /></span>
                        <span class="fm-atalho__rotulo">{{ $atalho['rotulo'] }}</span>
                        <x-icone nome="chevron-right" class="fm-atalho__seta" />
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

@endsection
