<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Models\Consulta;
use App\Services\EstatisticasDeConsultas;
use App\Support\Formatador;

class DashboardController extends Controller
{
    /**
     * Dashboard do paciente.
     *
     * O QUE NÃO ENTRA AQUI, por mais que apareça no mockup: "Resumo da sua
     * saúde", pressão arterial, medicamentos em uso, exames, resultados,
     * receitas, atestados e documentos. Não há dado para isso e não pode
     * haver leitura clínica (AGENTS.md §6 e §2).
     *
     * Onde o mockup tinha esses cards entraram coisas que o sistema sabe
     * de verdade: avaliações pendentes e o estado da carteirinha do plano.
     *
     * NUNCA leia `acessibilidade` aqui: é dado sensível (LGPD art. 11) e só
     * pode ser lido no contexto de uma consulta agendada (AGENTS.md §6).
     */
    public function index()
    {
        $usuario  = auth()->user();
        $paciente = $usuario->paciente;

        // Consultas DESTE paciente. Nasce preso ao dono, como no painel do médico.
        $minhas = fn () => Consulta::query()->where('consultas.paciente_id', $paciente->id);

        // ---- Próximos atendimentos (agendadas que ainda não aconteceram) ----
        $proximasQuery = fn () => EstatisticasDeConsultas::aPartirDeAgora(
            $minhas()->where('consultas.status', 'agendada')
        );

        $proximas = $proximasQuery()
            ->with('medico.user:id,name', 'especialidade:id,nome', 'vinculo.local:id,nome')
            ->orderBy('consultas.data_consulta')->orderBy('consultas.horario')
            ->limit(3)->get();

        $totalProximas = $proximasQuery()->count();

        // ---- Realizadas e avaliações pendentes ----
        $totalRealizadas = $minhas()->where('consultas.status', 'realizada')->count();

        $paraAvaliar = $minhas()
            ->where('consultas.status', 'realizada')
            ->whereDoesntHave('avaliacao')
            ->with('medico.user:id,name', 'especialidade:id,nome')
            ->orderByDesc('consultas.data_consulta')
            ->get();

        // ---- Histórico (o que já passou, qualquer desfecho) ----
        $historico = $minhas()
            ->whereIn('consultas.status', ['realizada', 'nao_compareceu', 'cancelada'])
            ->with('medico.user:id,name', 'especialidade:id,nome')
            ->orderByDesc('consultas.data_consulta')->orderByDesc('consultas.horario')
            ->limit(5)->get();

        // ---- Carteirinhas ----
        $carteirinhas = $paciente->planos()->with('plano.convenio:id,nome')->latest()->get();

        $ativas     = $carteirinhas->where('status', 'ativa')->count();
        $pendentes  = $carteirinhas->where('status', 'pendente')->count();

        $notaPlano = match (true) {
            $carteirinhas->isEmpty() => 'Nenhum plano cadastrado',
            $pendentes > 0           => $pendentes . ' em conferência',
            default                  => $ativas . ' ativo' . ($ativas === 1 ? '' : 's'),
        };

        $urlConsultas = route('paciente.consultas');

        return view('paciente.dashboard', [
            'saudacao' => Formatador::saudacao($usuario->name),
            'dataHoje' => Formatador::dataExtensa(today()),

            'cartoes' => [
                [
                    'icone'  => 'calendar',
                    'tom'    => 'azul',
                    'rotulo' => 'Próximos atendimentos',
                    'valor'  => Formatador::numero($totalProximas),
                    'link'   => 'ver detalhes',
                    'url'    => route('paciente.consultas', ['status' => 'agendada']),
                ],
                [
                    'icone'  => 'check-circle',
                    'tom'    => 'verde',
                    'rotulo' => 'Consultas realizadas',
                    'valor'  => Formatador::numero($totalRealizadas),
                    'link'   => 'ver histórico',
                    'url'    => route('paciente.consultas', ['status' => 'realizada']),
                ],
                [
                    'icone'  => 'star',
                    'tom'    => 'roxo',
                    'rotulo' => 'Para avaliar',
                    'valor'  => Formatador::numero($paraAvaliar->count()),
                    'link'   => 'avaliar consultas',
                    'url'    => route('paciente.consultas', ['status' => 'realizada']),
                ],
                [
                    'icone'  => 'card',
                    'tom'    => 'rosa',
                    'rotulo' => 'Planos de saúde',
                    'valor'  => Formatador::numero($carteirinhas->count()),
                    'link'   => $notaPlano,
                    'url'    => route('paciente.planos'),
                ],
            ],

            'proximas' => $proximas->map(fn (Consulta $c) => [
                'url'           => route('paciente.consultas.show', ['consulta' => $c->id]),
                'data'          => Formatador::dataExtensa($c->data_consulta),
                'hora'          => Formatador::hora($c->horario),
                'medico'        => $c->medico->user->name ?? 'Médico',
                'especialidade' => $c->especialidade->nome ?? '—',
                'local'         => $c->vinculo->local->nome ?? '—',
            ])->all(),

            'proximasUrl' => route('paciente.consultas', ['status' => 'agendada']),
            'buscarUrl'   => route('busca.index'),

            // Aviso obrigatório do AGENTS.md §6: o convênio é aceito pelo
            // médico, não pelo endereço. Aparece só se há consulta por convênio.
            'avisoConvenio' => $proximas->contains('forma_pagamento', 'convenio'),

            'historico' => $historico->map(function (Consulta $c) {
                $status = Formatador::status($c->status);

                return [
                    'url'           => route('paciente.consultas.show', ['consulta' => $c->id]),
                    'data'          => Formatador::dataCurta($c->data_consulta),
                    'medico'        => $c->medico->user->name ?? 'Médico',
                    'especialidade' => $c->especialidade->nome ?? '—',
                    'status'        => $status['rotulo'],
                    'tom'           => $status['tom'],
                ];
            })->all(),
            'historicoUrl' => $urlConsultas,

            // Só quem pode avaliar (consulta realizada, sem avaliação, do próprio paciente).
            'paraAvaliar' => $paraAvaliar->take(3)->map(fn (Consulta $c) => [
                'url'           => route('paciente.consultas.avaliar', ['consulta' => $c->id]),
                'medico'        => $c->medico->user->name ?? 'Médico',
                'especialidade' => $c->especialidade->nome ?? '—',
                'data'          => Formatador::dataCurta($c->data_consulta),
            ])->all(),

            'carteirinhas' => $carteirinhas->take(3)->map(function ($cp) {
                $status = Formatador::statusCarteirinha($cp->status);

                return [
                    'nome'   => ($cp->plano->convenio->nome ?? 'Convênio') . ' · ' . ($cp->plano->nome ?? 'Plano'),
                    'status' => $status['rotulo'],
                    'tom'    => $status['tom'],
                ];
            })->all(),
            'planosUrl' => route('paciente.planos'),

            'atalhos' => Formatador::atalhos('paciente'),
        ]);
    }
}
