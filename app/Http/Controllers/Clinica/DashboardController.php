<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Models\Consulta;
use App\Models\Disponibilidade;
use App\Models\Local;
use App\Models\Medico;
use App\Models\Vinculo;
use App\Services\EstatisticasDeConsultas;
use App\Support\Formatador;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Dashboard da clínica.
     *
     * O QUE FOI TROCADO EM RELAÇÃO AO MOCKUP (e por quê):
     *  - "Pacientes cadastrados 8.732"   -> "Pacientes atendidos" (consultas
     *    realizadas nas unidades DESTA clínica; o total do sistema não é dela).
     *  - "Ligações recebidas" e "Ligações por franquia" -> não existe telefonia
     *    no sistema. Viraram "Consultas agendadas" e "Consultas por convênio".
     *  - "Presenciais / Teleconsultas / Retornos" -> Particular / Convênio.
     *    Teleconsulta saiu do escopo em 18/09/2026 (AGENTS.md §2).
     *  - Menu "Financeiro" -> não existe: a plataforma não processa pagamento.
     *  - Campo de busca e sino de notificações -> sem tela por trás; ficam de
     *    fora até existirem (item de menu sem tela é link morto).
     *
     * Tudo parte de EstatisticasDeConsultas::daClinica(), que só enxerga
     * consultas das unidades da clínica logada.
     */
    public function index(Request $request)
    {
        $usuario = auth()->user();
        $clinica = $usuario->clinica;
        $stats   = EstatisticasDeConsultas::daClinica($clinica);

        $hoje = today();

        // ---- Janela do gráfico principal (vem da URL: ?periodo=) ----
        $modo = $request->query('periodo');
        $modo = in_array($modo, ['semanal', 'mensal', 'anual'], true) ? $modo : 'mensal';

        [$deGrafico, $tipoSerie] = match ($modo) {
            'semanal' => [$hoje->copy()->subDays(6), 'dia'],
            'anual'   => [$hoje->copy()->subYears(4)->startOfYear(), 'ano'],
            default   => [$hoje->copy()->subMonths(11)->startOfMonth(), 'mes'],
        };

        $serie = $stats->serie($deGrafico, $hoje, $tipoSerie);

        $abas = [];
        foreach (['semanal' => 'Semanal', 'mensal' => 'Mensal', 'anual' => 'Anual'] as $chave => $rotulo) {
            $abas[] = [
                'rotulo' => $rotulo,
                'url'    => route('clinica.dashboard', ['periodo' => $chave]),
                'ativo'  => $chave === $modo,
            ];
        }

        $totalPeriodo = array_sum($serie['valores']);
        $formas       = $stats->porFormaDePagamento($deGrafico, $hoje);
        $somaFormas   = max(1, $formas['particular'] + $formas['convenio']);

        // ---- Realizadas no mês x mês anterior (mesmo número de dias) ----
        $inicioMes     = $hoje->copy()->startOfMonth();
        $mesPassadoIni = $inicioMes->copy()->subMonth();
        $mesPassadoFim = $mesPassadoIni->copy()->addDays($hoje->day - 1)
                            ->min($mesPassadoIni->copy()->endOfMonth());

        $realizadasMes   = $stats->total('realizada', $inicioMes, $hoje);
        $realizadasAntes = $stats->total('realizada', $mesPassadoIni, $mesPassadoFim);

        // ---- Unidades, vínculos e médicos ativos ----
        $localIds  = Local::query()->where('clinica_id', $clinica->id)->pluck('id');
        $vinculos  = Vinculo::query()
            ->whereIn('local_id', $localIds)->where('ativo', true)
            ->get(['id', 'medico_id']);

        $medicoPorVinculo = $vinculos->pluck('medico_id', 'id');
        $medicosAtivos    = $vinculos->pluck('medico_id')->unique()->count();
        $unidades         = Local::query()->where('clinica_id', $clinica->id)->where('ativo', true)->count();

        $agendadasFuturas = EstatisticasDeConsultas::aPartirDeAgora(
            $stats->todas()->where('consultas.status', 'agendada')
        )->count();

        // ---- Médicos ativos por especialidade principal ----
        $medicos = Medico::query()
            ->whereIn('id', $vinculos->pluck('medico_id')->unique())
            ->with('user:id,name', 'especialidades:id,nome')
            ->get();

        $porEspecialidadeMedicos = [];
        foreach ($medicos as $m) {
            $principal = $m->especialidades->firstWhere('pivot.principal', true) ?? $m->especialidades->first();
            $nome = $principal->nome ?? 'Sem especialidade';
            $porEspecialidadeMedicos[$nome] = ($porEspecialidadeMedicos[$nome] ?? 0) + 1;
        }
        arsort($porEspecialidadeMedicos);

        $itensMedicos = [];
        foreach ($porEspecialidadeMedicos as $nome => $total) {
            $itensMedicos[] = ['nome' => $nome, 'total' => $total];
        }
        $itensMedicos = $stats->enriquecer($stats->agruparResto($itensMedicos, 6));

        // ---- Agenda dos médicos hoje ----
        $diaSemana = Disponibilidade::DIAS[$hoje->dayOfWeek];

        $blocosHoje = Disponibilidade::query()
            ->whereIn('vinculo_id', $vinculos->pluck('id'))
            ->where('dia_semana', $diaSemana)->where('ativo', true)
            ->get()
            ->groupBy(fn ($d) => $medicoPorVinculo[$d->vinculo_id] ?? 0);

        $consultasHojePorMedico = $stats->todas()
            ->where('consultas.data_consulta', $hoje->toDateString())
            ->where('consultas.status', '<>', 'cancelada')
            ->selectRaw('consultas.medico_id as chave, COUNT(*) as total')
            ->groupBy('consultas.medico_id')
            ->toBase()->get()->pluck('total', 'chave');

        $agendaMedicos = $medicos
            ->map(function (Medico $m) use ($blocosHoje, $consultasHojePorMedico) {
                $blocos = $blocosHoje[$m->id] ?? collect();
                $qtd    = (int) ($consultasHojePorMedico[$m->id] ?? 0);

                $janela = $blocos->isNotEmpty()
                    ? Formatador::hora($blocos->min('hora_inicio')) . ' - ' . Formatador::hora($blocos->max('hora_fim'))
                    : 'Não atende hoje';

                $principal = $m->especialidades->firstWhere('pivot.principal', true) ?? $m->especialidades->first();

                return [
                    'nome'          => $m->user->name ?? 'Médico',
                    'iniciais'      => Formatador::iniciais($m->user->name ?? '?'),
                    'especialidade' => $principal->nome ?? '—',
                    'janela'        => $janela,
                    'consultas'     => $qtd,
                    'consultas_fmt' => $qtd . ($qtd === 1 ? ' consulta' : ' consultas'),
                    'atende'        => $blocos->isNotEmpty() || $qtd > 0,
                ];
            })
            ->filter(fn ($m) => $m['atende'])
            ->sortByDesc('consultas')
            ->take(5)->values()->all();

        // ---- Rankings dos últimos 30 dias ----
        $de30 = $hoje->copy()->subDays(29);

        $porEspecialidade = $stats->porEspecialidade($de30, $hoje, 6);
        $porConvenio      = $stats->porConvenio($de30, $hoje, 5);

        foreach ($porConvenio as $i => $item) {
            $porConvenio[$i]['iniciais'] = Formatador::iniciais($item['nome']);
            $porConvenio[$i]['detalhe']  = $item['total_fmt'] . ($item['total'] === 1 ? ' consulta' : ' consultas');
        }

        // ---- Consultas por dia da semana (últimos 30 dias) ----
        $porDia  = $stats->porDiaDaSemana($de30, $hoje);
        $maiorDia = max(1, max($porDia));

        $diasDaSemana = [];
        // Segunda a sábado, como no mockup; domingo só aparece se houver consulta.
        foreach ([1, 2, 3, 4, 5, 6, 0] as $dia) {
            if ($dia === 0 && $porDia[0] === 0) {
                continue;
            }
            $diasDaSemana[] = [
                'rotulo' => Formatador::DIAS_CURTOS[$dia],
                'total'  => $porDia[$dia],
                'altura' => (int) round($porDia[$dia] / $maiorDia * 100),
            ];
        }

        // ---- Últimos agendamentos (os feitos mais recentemente) ----
        $ultimos = $stats->todas()
            ->with('paciente.user:id,name', 'especialidade:id,nome')
            ->orderByDesc('consultas.created_at')
            ->limit(4)->get()
            ->map(function (Consulta $c) {
                $status = Formatador::status($c->status);

                return [
                    'paciente'      => $c->paciente->user->name ?? 'Paciente',
                    'iniciais'      => Formatador::iniciais($c->paciente->user->name ?? '?'),
                    'especialidade' => $c->especialidade->nome ?? '—',
                    'quando'        => Formatador::dataCurta($c->data_consulta) . ' · ' . Formatador::hora($c->horario),
                    'status'        => $status['rotulo'],
                    'tom'           => $status['tom'],
                ];
            })->all();

        return view('clinica.dashboard', [
            'saudacao'    => Formatador::saudacao($usuario->name),
            'nomeClinica' => $clinica->nome_fantasia ?: $clinica->razao_social,
            'dataHoje'    => Formatador::dataExtensa($hoje),

            'cartoes' => [
                [
                    'icone'    => 'calendar-check',
                    'tom'      => 'azul',
                    'rotulo'   => 'Consultas realizadas',
                    'valor'    => Formatador::numero($realizadasMes),
                    'variacao' => Formatador::variacao($realizadasMes, $realizadasAntes),
                    'nota'     => 'em relação ao mês anterior',
                    'url'      => route('clinica.consultas'),
                ],
                [
                    'icone'    => 'users',
                    'tom'      => 'verde',
                    'rotulo'   => 'Pacientes atendidos',
                    'valor'    => Formatador::numero($stats->pacientesAtendidos()),
                    'variacao' => null,
                    'nota'     => 'nas suas unidades',
                    'url'      => null,
                ],
                [
                    'icone'    => 'doctors',
                    'tom'      => 'roxo',
                    'rotulo'   => 'Médicos ativos',
                    'valor'    => Formatador::numero($medicosAtivos),
                    'variacao' => null,
                    'nota'     => $unidades . ($unidades === 1 ? ' unidade ativa' : ' unidades ativas'),
                    'url'      => route('clinica.medicos'),
                ],
                [
                    'icone'    => 'calendar',
                    'tom'      => 'ciano',
                    'rotulo'   => 'Consultas agendadas',
                    'valor'    => Formatador::numero($agendadasFuturas),
                    'variacao' => null,
                    'nota'     => 'a partir de agora',
                    'url'      => route('clinica.agenda'),
                ],
            ],

            'grafico' => [
                'abas'     => $abas,
                'serie'    => $serie,
                'total'    => Formatador::numero($totalPeriodo),
                'rotulo'   => match ($modo) {
                    'semanal' => 'consultas realizadas nos últimos 7 dias',
                    'anual'   => 'consultas realizadas nos últimos 5 anos',
                    default   => 'consultas realizadas nos últimos 12 meses',
                },
                'formas'   => [
                    [
                        'nome'  => 'Particular',
                        'total' => Formatador::numero($formas['particular']),
                        'pct'   => (int) round($formas['particular'] / $somaFormas * 100),
                        'cor'   => Formatador::cor(0),
                    ],
                    [
                        'nome'  => 'Convênio',
                        'total' => Formatador::numero($formas['convenio']),
                        'pct'   => (int) round($formas['convenio'] / $somaFormas * 100),
                        'cor'   => Formatador::cor(2),
                    ],
                ],
            ],

            'medicosAtivos' => [
                'itens'  => $itensMedicos,
                'donut'  => [
                    'centro' => Formatador::numero($medicosAtivos),
                    'legenda'=> $medicosAtivos === 1 ? 'médico' : 'médicos',
                    'itens'  => array_map(fn ($i) => ['nome' => $i['nome'], 'total' => $i['total'], 'cor' => $i['cor']], $itensMedicos),
                ],
                'url'    => route('clinica.medicos'),
            ],

            'porEspecialidade' => $porEspecialidade,
            'agendaMedicos'    => $agendaMedicos,
            'agendaUrl'        => route('clinica.agenda'),
            'porConvenio'      => $porConvenio,
            'convenioUrl'      => route('clinica.convenios'),
            'diasDaSemana'     => $diasDaSemana,
            'ultimos'          => $ultimos,
        ]);
    }
}
