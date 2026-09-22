<?php

namespace App\Http\Controllers\Medico;

use App\Http\Controllers\Controller;
use App\Models\Consulta;
use App\Services\EstatisticasDeConsultas;
use App\Support\Formatador;

class DashboardController extends Controller
{
    /**
     * Dashboard do médico.
     *
     * O QUE FOI TROCADO EM RELAÇÃO AO MOCKUP (e por quê):
     *  - "Pendências: 2 retornos | 1 exame"  -> consultas passadas que ainda
     *    estão como "agendada" (ninguém marcou realizada nem falta). Retorno
     *    e exame não existem no modelo.
     *  - "Consulta online"                    -> não existe (AGENTS.md §2).
     *  - "Resultados de exames", "Solicitar exames", "Enviar prontuário",
     *    "Adicionar anotações"                -> fora do escopo (AGENTS.md §2).
     *  - Ações/Acesso rápido                  -> viram atalhos gerados do
     *    config/navegacao.php, sem link para tela que não existe.
     *
     * Toda consulta lida aqui passa por EstatisticasDeConsultas::doMedico(),
     * que já nasce presa ao médico logado.
     */
    public function index()
    {
        $usuario = auth()->user();
        $medico  = $usuario->medico;
        $stats   = EstatisticasDeConsultas::doMedico($medico);

        $hoje  = today();
        $ontem = today()->subDay();

        // ---- Consultas de hoje x ontem (não conta cancelada) ----
        $doDia = fn ($dia) => $stats->todas()
            ->where('consultas.data_consulta', $dia->toDateString())
            ->where('consultas.status', '<>', 'cancelada')
            ->count();

        $consultasHoje  = $doDia($hoje);
        $consultasOntem = $doDia($ontem);

        // ---- Pendências e próximos atendimentos ----
        $semDesfecho = EstatisticasDeConsultas::semDesfecho($stats->todas())->count();

        $horariosRestantes = EstatisticasDeConsultas::aPartirDeAgora(
            $stats->todas()
                ->where('consultas.status', 'agendada')
                ->where('consultas.data_consulta', $hoje->toDateString())
        )->orderBy('consultas.horario')->pluck('consultas.horario');

        // ---- Realizadas no mês x mês anterior (mesmo número de dias) ----
        $inicioMes     = $hoje->copy()->startOfMonth();
        $mesPassadoIni = $inicioMes->copy()->subMonth();
        $mesPassadoFim = $mesPassadoIni->copy()->addDays($hoje->day - 1)
                            ->min($mesPassadoIni->copy()->endOfMonth());

        $realizadasMes   = $stats->total('realizada', $inicioMes, $hoje);
        $realizadasAntes = $stats->total('realizada', $mesPassadoIni, $mesPassadoFim);

        // ---- Agenda de hoje ----
        $agenda = $stats->todas()
            ->where('consultas.data_consulta', $hoje->toDateString())
            ->where('consultas.status', '<>', 'cancelada')
            ->with('paciente.user:id,name', 'especialidade:id,nome', 'vinculo.local:id,nome')
            ->orderBy('consultas.horario')
            ->get()
            ->map(function (Consulta $c) {
                $status = Formatador::status($c->status);

                return [
                    'hora'          => Formatador::hora($c->horario),
                    'paciente'      => $c->paciente->user->name ?? 'Paciente',
                    'especialidade' => $c->especialidade->nome ?? '—',
                    'local'         => $c->vinculo->local->nome ?? '—',
                    'tom'           => $status['tom'],
                    // Só mostra etiqueta quando o status foge do "agendada" normal.
                    'etiqueta'      => $c->status === 'agendada' ? null : $status['rotulo'],
                ];
            })->all();

        // ---- Histórico recente (últimas realizadas) ----
        $historico = $stats->todas()
            ->where('consultas.status', 'realizada')
            ->with('paciente.user:id,name', 'especialidade:id,nome')
            ->orderByDesc('consultas.data_consulta')
            ->orderByDesc('consultas.horario')
            ->limit(4)
            ->get()
            ->map(fn (Consulta $c) => [
                'data'          => Formatador::dataCurta($c->data_consulta),
                'paciente'      => $c->paciente->user->name ?? 'Paciente',
                'especialidade' => $c->especialidade->nome ?? '—',
            ])->all();

        return view('medico.dashboard', [
            'saudacao' => Formatador::saudacao($usuario->name),
            'dataHoje' => Formatador::dataExtensa($hoje),

            'cartoes' => [
                [
                    'icone'    => 'users',
                    'tom'      => 'azul',
                    'rotulo'   => 'Consultas hoje',
                    'valor'    => Formatador::numero($consultasHoje),
                    'variacao' => Formatador::diferenca($consultasHoje, $consultasOntem),
                    'nota'     => 'em relação a ontem',
                    'url'      => null,
                ],
                [
                    'icone'    => 'user',
                    'tom'      => 'verde',
                    'rotulo'   => 'Pacientes atendidos',
                    'valor'    => Formatador::numero($stats->pacientesAtendidos()),
                    'variacao' => null,
                    'nota'     => 'Total no sistema',
                    'url'      => null,
                ],
                [
                    'icone'    => 'clock',
                    'tom'      => 'roxo',
                    'rotulo'   => 'Pendências',
                    'valor'    => Formatador::numero($semDesfecho),
                    'variacao' => $semDesfecho > 0
                        ? ['sentido' => 'alerta', 'texto' => 'sem desfecho registrado', 'tom' => 'ruim']
                        : null,
                    'nota'     => $semDesfecho > 0 ? null : 'Tudo em dia',
                    'url'      => $semDesfecho > 0 ? route('medico.agenda') : null,
                ],
                [
                    'icone'    => 'calendar',
                    'tom'      => 'rosa',
                    'rotulo'   => 'Próximos atendimentos',
                    'valor'    => Formatador::numero($horariosRestantes->count()),
                    'variacao' => null,
                    'nota'     => $horariosRestantes->isNotEmpty()
                        ? 'Hoje, a partir das ' . Formatador::hora($horariosRestantes->first())
                        : 'Nenhum a mais hoje',
                    'url'      => null,
                ],
            ],

            'realizadas' => [
                'icone'    => 'calendar-check',
                'tom'      => 'verde',
                'rotulo'   => 'Consultas realizadas neste mês',
                'valor'    => Formatador::numero($realizadasMes),
                'variacao' => Formatador::variacao($realizadasMes, $realizadasAntes),
                'nota'     => 'em relação ao mês anterior',
                'url'      => route('medico.consultas'),
            ],

            'agenda'    => $agenda,
            'agendaUrl' => route('medico.agenda'),
            'atalhos'   => Formatador::atalhos('medico'),
            'historico' => $historico,

            // Só a nota. Comentário de avaliação tem tela própria (medico.avaliacoes).
            'avaliacao' => [
                'total' => (int) $medico->total_avaliacoes,
                'total_texto' => (int) $medico->total_avaliacoes
                    . ((int) $medico->total_avaliacoes === 1 ? ' avaliação' : ' avaliações')
                    . ' de pacientes',
                'media' => $medico->total_avaliacoes > 0
                    ? Formatador::numero((float) $medico->media_avaliacoes, 1)
                    : null,
                'url'   => route('medico.avaliacoes'),
            ],
        ]);
    }
}
