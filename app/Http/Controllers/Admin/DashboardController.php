<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinica;
use App\Models\Consulta;
use App\Models\Local;
use App\Models\Medico;
use App\Models\User;
use App\Models\Vinculo;
use App\Services\EstatisticasDeConsultas;
use App\Support\Formatador;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Painel do admin (24/09/2026), seguindo o mockup do grupo.
     *
     * O QUE FOI TROCADO EM RELAÇÃO AO MOCKUP (e por quê):
     *  - Busca no topo e sino de notificações -> sem tela por trás (item
     *    sem tela é link morto). Ficam de fora até existirem.
     *  - Menus "Relatórios" e "Configurações" -> não existem; o "Acesso
     *    rápido" usa os itens reais do menu (config/navegacao.php).
     *  - "Consultas por status: Confirmadas / Em espera" -> o sistema tem
     *    agendada / realizada / cancelada / não compareceu.
     *  - "Atividades recentes" -> não existe tabela de log. Vira a lista
     *    das consultas mexidas por último (agendada, cancelada, realizada),
     *    que é o que realmente acontece na plataforma.
     *
     * Tudo parte de EstatisticasDeConsultas::daPlataforma().
     */
    public function index(Request $request)
    {
        $usuario = $request->user();
        $stats   = EstatisticasDeConsultas::daPlataforma();
        $hoje    = today();

        // ---- Gráfico principal (?periodo=semanal|mensal|anual) ----
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
                'url'    => route('admin.dashboard', ['periodo' => $chave]),
                'ativo'  => $chave === $modo,
            ];
        }

        // ---- Realizadas no mês x mesmo trecho do mês anterior ----
        $inicioMes     = $hoje->copy()->startOfMonth();
        $mesPassadoIni = $inicioMes->copy()->subMonth();
        $mesPassadoFim = $mesPassadoIni->copy()->addDays($hoje->day - 1)
                            ->min($mesPassadoIni->copy()->endOfMonth());

        $realizadasMes   = $stats->total('realizada', $inicioMes, $hoje);
        $realizadasAntes = $stats->total('realizada', $mesPassadoIni, $mesPassadoFim);

        // Cadastros novos no mês x mês anterior (para a variação dos cartões).
        // (Conta excluída pelo paciente, 30/09, não entra: ela não é mais um cadastro.)
        $novos = fn (string $tipo, $de, $ate) => User::where('tipo', $tipo)->whereNull('excluida_em')
            ->whereBetween('created_at', [$de->copy()->startOfDay(), $ate->copy()->endOfDay()])->count();

        $clinicasAtivas  = Clinica::whereHas('user', fn ($q) => $q->where('status', 'ativo'))->count();
        $medicosAtivos   = Medico::visivel()->count();
        // 30/09: conta excluída pelo paciente não é mais um paciente cadastrado.
        $pacientes       = User::where('tipo', User::TIPO_PACIENTE)->whereNull('excluida_em')->count();
        $agendadasFuturas = EstatisticasDeConsultas::aPartirDeAgora(
            $stats->todas()->where('consultas.status', 'agendada')
        )->count();

        // ---- Últimos 30 dias ----
        $de30 = $hoje->copy()->subDays(29);

        $porUnidade = $stats->enriquecer($stats->agruparResto($stats->porLocal($de30, $hoje, 50), 5));

        // 30 dias para tras E para frente: so para tras, 'agendada' dava sempre 0%.
        $contagem = $stats->contagemPorStatus($de30, $hoje->copy()->addDays(30));
        $tons = ['agendada' => '#1f6feb', 'realizada' => '#19b58a', 'cancelada' => '#e0466a', 'nao_compareceu' => '#f6a23c'];
        $statusItens = [];
        foreach ($contagem as $status => $total) {
            $statusItens[] = [
                'nome'  => Formatador::status($status)['rotulo'],
                'total' => $total,
                'cor'   => $tons[$status],
            ];
        }
        $somaStatus = array_sum(array_column($statusItens, 'total'));
        foreach ($statusItens as $i => $item) {
            $statusItens[$i]['pct'] = $somaStatus > 0 ? (int) round($item['total'] / $somaStatus * 100) : 0;
        }

        // ---- Clínicas e hospitais ----
        $clinicas = Clinica::with('user:id,status', 'locais:id,clinica_id,cidade,uf,tipo,ativo')->get()
            ->map(function (Clinica $c) use ($de30, $hoje) {
                $localIds = $c->locais->pluck('id');
                $vinculos = Vinculo::whereIn('local_id', $localIds)->where('ativo', true);
                $consultas = EstatisticasDeConsultas::daClinica($c)->total('realizada', $de30, $hoje);
                $local = $c->locais->first();
                $ativa = $c->user?->status === 'ativo';

                return [
                    'nome'      => $c->nome_fantasia ?: $c->razao_social,
                    'url'       => route('publico.clinica', $c),
                    'hospital'  => $c->locais->contains('tipo', 'hospital'),
                    'cidade'    => $local ? "{$local->cidade} - {$local->uf}" : '—',
                    'medicos'   => (clone $vinculos)->distinct()->count('medico_id'),
                    'consultas' => $consultas,
                    'status'    => $ativa ? 'Ativa' : 'Inativa',
                    'tom'       => $ativa ? 'verde' : 'cinza',
                ];
            })
            ->sortByDesc('consultas')->take(4)->values()->all();

        // ---- Médicos cadastrados por último ----
        $medicos = Medico::with('user:id,name,status', 'especialidades:id,nome', 'vinculos.local:id,nome')
            ->latest()->limit(5)->get()
            ->map(function (Medico $m) {
                $principal = $m->especialidades->firstWhere('pivot.principal', true) ?? $m->especialidades->first();
                $verificado = $m->status_verificacao === 'verificado';

                return [
                    'nome'          => $m->user->name ?? 'Médico',
                    'iniciais'      => Formatador::iniciais($m->user->name ?? '?'),
                    'especialidade' => $principal->nome ?? '—',
                    'local'         => $m->vinculos->firstWhere('ativo', true)?->local?->nome ?? 'Sem local de atendimento',
                    'status'        => $verificado ? 'Ativo' : ucfirst((string) $m->status_verificacao),
                    'tom'           => $verificado ? 'verde' : 'ambar',
                    'url'           => $verificado ? route('publico.medico', $m) : null,
                ];
            })->all();

        // ---- Últimos cadastros (todos os tipos) ----
        $icones = ['paciente' => 'user', 'medico' => 'doctors', 'clinica' => 'building', 'admin' => 'badge'];
        $ultimosCadastros = User::whereNull('excluida_em')->latest()->limit(5)->get(['id', 'name', 'tipo', 'created_at'])
            ->map(fn (User $u) => [
                'nome'  => $u->name,
                'tipo'  => Formatador::PAPEIS[$u->tipo] ?? $u->tipo,
                'icone' => $icones[$u->tipo] ?? 'user',
                'quando'=> $u->created_at?->isToday() ? $u->created_at->format('H:i') : $u->created_at?->format('d/m'),
            ])->all();

        // ---- "Atividade recente" = consultas mexidas por último ----
        $atividades = Consulta::with('paciente.user:id,name', 'medico.user:id,name')
            ->latest('updated_at')->limit(5)->get()
            ->map(function (Consulta $c) {
                [$titulo, $icone, $tom] = match ($c->status) {
                    'cancelada'      => ['Consulta cancelada', 'x-circle', 'rosa'],
                    'realizada'      => ['Consulta realizada', 'check-circle', 'verde'],
                    'nao_compareceu' => ['Paciente não compareceu', 'alert', 'ambar'],
                    default          => ['Nova consulta agendada', 'calendar-check', 'azul'],
                };

                return [
                    'titulo'  => $titulo,
                    'detalhe' => ($c->medico->user->name ?? 'Médico') . ' · ' . ($c->paciente->user->name ?? 'Paciente'),
                    'icone'   => $icone,
                    'tom'     => $tom,
                    'quando'  => $c->updated_at?->isToday() ? $c->updated_at->format('H:i') : $c->updated_at?->format('d/m'),
                ];
            })->all();

        $inicioMesAnt = $inicioMes->copy()->subMonth();

        return view('admin.dashboard', [
            'saudacao' => Formatador::saudacao($usuario->name),
            'dataHoje' => Formatador::dataExtensa($hoje),

            'cartoes' => [
                [
                    'icone' => 'building', 'tom' => 'azul', 'rotulo' => 'Clínicas ativas',
                    'valor' => Formatador::numero($clinicasAtivas),
                    'variacao' => null,
                    'nota' => Local::where('ativo', true)->count() . ' unidades de atendimento',
                    'url' => route('admin.clinicas'),
                ],
                [
                    'icone' => 'doctors', 'tom' => 'verde', 'rotulo' => 'Médicos ativos',
                    'valor' => Formatador::numero($medicosAtivos),
                    'variacao' => Formatador::variacao($novos('medico', $inicioMes, $hoje), $novos('medico', $inicioMesAnt, $mesPassadoFim)),
                    'nota' => 'cadastros no mês x mês anterior',
                    'url' => route('admin.usuarios'),
                ],
                [
                    'icone' => 'users', 'tom' => 'roxo', 'rotulo' => 'Pacientes cadastrados',
                    'valor' => Formatador::numero($pacientes),
                    'variacao' => Formatador::variacao($novos('paciente', $inicioMes, $hoje), $novos('paciente', $inicioMesAnt, $mesPassadoFim)),
                    'nota' => 'cadastros no mês x mês anterior',
                    'url' => route('admin.usuarios'),
                ],
                [
                    'icone' => 'calendar-check', 'tom' => 'ciano', 'rotulo' => 'Consultas realizadas',
                    'valor' => Formatador::numero($realizadasMes),
                    'variacao' => Formatador::variacao($realizadasMes, $realizadasAntes),
                    'nota' => 'no mês, em relação ao anterior',
                    'url' => route('admin.consultas'),
                ],
                [
                    'icone' => 'calendar', 'tom' => 'ambar', 'rotulo' => 'Agendamentos',
                    'valor' => Formatador::numero($agendadasFuturas),
                    'variacao' => null,
                    'nota' => 'consultas marcadas daqui para frente',
                    'url' => route('admin.consultas'),
                ],
            ],

            'grafico' => [
                'abas'  => $abas,
                'serie' => $serie,
                'total' => Formatador::numero(array_sum($serie['valores'])),
                'rotulo' => match ($modo) {
                    'semanal' => 'realizadas nos últimos 7 dias',
                    'anual'   => 'realizadas nos últimos 5 anos',
                    default   => 'realizadas nos últimos 12 meses',
                },
            ],

            'porUnidade' => [
                'itens' => $porUnidade,
                'donut' => [
                    'centro'  => Formatador::numero(array_sum(array_column($porUnidade, 'total'))),
                    'legenda' => 'consultas',
                    'itens'   => array_map(fn ($i) => ['nome' => $i['nome'], 'total' => $i['total'], 'cor' => $i['cor']], $porUnidade),
                ],
            ],

            'porStatus' => [
                'itens' => $statusItens,
                'donut' => [
                    'centro'  => Formatador::numero($somaStatus),
                    'legenda' => 'consultas',
                    'itens'   => array_map(fn ($i) => ['nome' => $i['nome'], 'total' => $i['total'], 'cor' => $i['cor']], $statusItens),
                ],
            ],

            'clinicas'         => $clinicas,
            'medicos'          => $medicos,
            'ultimosCadastros' => $ultimosCadastros,
            'atividades'       => $atividades,
        ]);
    }
}
