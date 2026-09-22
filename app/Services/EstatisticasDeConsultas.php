<?php

namespace App\Services;

use App\Models\Avaliacao;
use App\Models\Clinica;
use App\Models\Consulta;
use App\Models\Especialidade;
use App\Models\Local;
use App\Models\Medico;
use App\Models\PacientePlano;
use App\Models\Vinculo;
use App\Support\Formatador;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Números dos painéis do médico e da clínica.
 *
 * A ideia central: o serviço nasce JÁ PRESO a um dono.
 *
 *     EstatisticasDeConsultas::doMedico($medico)     // só consultas dele
 *     EstatisticasDeConsultas::daClinica($clinica)   // só consultas das unidades dela
 *
 * Toda pergunta feita depois parte dessa query-base. Não existe método
 * aqui que enxergue consulta de outro médico ou de outra clínica — o
 * "é dono disto?" fica garantido pela construção, não por um `if` que
 * alguém pode esquecer de repetir em cada tela.
 *
 * Duas escolhas técnicas que vale saber defender na banca:
 *
 * 1. Sem JOIN. Cada agrupamento é feito só na tabela `consultas`
 *    (GROUP BY id) e os NOMES vêm de uma segunda consulta pequena. Um
 *    JOIN com `users` traria uma coluna `status` ambígua (consultas.status
 *    x users.status) e é o tipo de erro que só aparece em produção.
 *
 * 2. As séries de gráfico vêm de UMA query agrupada por dia e são
 *    somadas em PHP nos "baldes" (dia, mês, ano). Funciona igual em
 *    MySQL, MariaDB e SQLite, e não depende de funções de data do banco.
 */
class EstatisticasDeConsultas
{
    /** @param Builder<Consulta> $base */
    private function __construct(private Builder $base)
    {
    }

    public static function doMedico(Medico $medico): self
    {
        return new self(Consulta::query()->where('consultas.medico_id', $medico->id));
    }

    public static function daClinica(Clinica $clinica): self
    {
        $locais    = Local::query()->where('clinica_id', $clinica->id)->select('id');
        $vinculos  = Vinculo::query()->whereIn('local_id', $locais)->select('id');

        return new self(Consulta::query()->whereIn('consultas.vinculo_id', $vinculos));
    }

    /** Consultas cuja DATA cai no intervalo fechado [$de, $ate]. */
    public function periodo(CarbonInterface $de, CarbonInterface $ate): Builder
    {
        return (clone $this->base)
            ->where('consultas.data_consulta', '>=', $de->toDateString())
            ->where('consultas.data_consulta', '<=', $ate->toDateString());
    }

    /** A query-base sem filtro de data (para os painéis montarem casos específicos). */
    public function todas(): Builder
    {
        return clone $this->base;
    }

    /**
     * Restringe a consultas que ainda vão acontecer (hoje depois da hora
     * atual, ou de amanhã em diante). Uma consulta "agendada" de hoje às
     * 08:00 já passou às 15:00 e não é "próxima".
     */
    public static function aPartirDeAgora(Builder $q): Builder
    {
        return $q->where(function ($w) {
            $w->where('consultas.data_consulta', '>', today()->toDateString())
              ->orWhere(function ($hoje) {
                  $hoje->where('consultas.data_consulta', today()->toDateString())
                       ->where('consultas.horario', '>=', now()->format('H:i:s'));
              });
        });
    }

    /**
     * Consultas que já deviam ter acontecido mas continuam "agendada":
     * ninguém marcou como realizada nem como falta. É a pendência real
     * do médico — sem isso a consulta nunca pode ser avaliada.
     */
    public static function semDesfecho(Builder $q): Builder
    {
        return $q->where('consultas.status', 'agendada')
            ->where(function ($w) {
                $w->where('consultas.data_consulta', '<', today()->toDateString())
                  ->orWhere(function ($hoje) {
                      $hoje->where('consultas.data_consulta', today()->toDateString())
                           ->where('consultas.horario', '<', now()->format('H:i:s'));
                  });
            });
    }

    // ------------------------------------------------------------------
    // Contagens
    // ------------------------------------------------------------------

    /** @return array{agendada:int, realizada:int, cancelada:int, nao_compareceu:int} */
    public function contagemPorStatus(CarbonInterface $de, CarbonInterface $ate): array
    {
        $linhas = $this->periodo($de, $ate)
            ->selectRaw('consultas.status as chave, COUNT(*) as total')
            ->groupBy('consultas.status')
            ->toBase()
            ->get()
            ->pluck('total', 'chave');

        return [
            'agendada'       => (int) ($linhas['agendada'] ?? 0),
            'realizada'      => (int) ($linhas['realizada'] ?? 0),
            'cancelada'      => (int) ($linhas['cancelada'] ?? 0),
            'nao_compareceu' => (int) ($linhas['nao_compareceu'] ?? 0),
        ];
    }

    public function total(string $status, CarbonInterface $de, CarbonInterface $ate): int
    {
        return $this->periodo($de, $ate)->where('consultas.status', $status)->count();
    }

    /** Pacientes diferentes já atendidos (consultas realizadas), no total. */
    public function pacientesAtendidos(): int
    {
        return (clone $this->base)
            ->where('consultas.status', 'realizada')
            ->distinct()
            ->count('consultas.paciente_id');
    }

    /** @return array{particular:int, convenio:int} consultas realizadas no período */
    public function porFormaDePagamento(CarbonInterface $de, CarbonInterface $ate): array
    {
        $linhas = $this->periodo($de, $ate)
            ->where('consultas.status', 'realizada')
            ->selectRaw('consultas.forma_pagamento as chave, COUNT(*) as total')
            ->groupBy('consultas.forma_pagamento')
            ->toBase()
            ->get()
            ->pluck('total', 'chave');

        return [
            'particular' => (int) ($linhas['particular'] ?? 0),
            'convenio'   => (int) ($linhas['convenio'] ?? 0),
        ];
    }

    // ------------------------------------------------------------------
    // Séries para gráfico
    // ------------------------------------------------------------------

    /**
     * Quantidade de consultas por dia, uma query só.
     *
     * @return Collection<string, int> ['2026-08-11' => 8, ...]
     */
    private function contagemPorDia(CarbonInterface $de, CarbonInterface $ate, string $status): Collection
    {
        return $this->periodo($de, $ate)
            ->where('consultas.status', $status)
            ->selectRaw('consultas.data_consulta as dia, COUNT(*) as total')
            ->groupBy('consultas.data_consulta')
            ->toBase()
            ->get()
            ->mapWithKeys(fn ($l) => [substr((string) $l->dia, 0, 10) => (int) $l->total]);
    }

    /**
     * Série pronta para o gráfico de linha.
     *
     * @param string $tipo     'dia' (lotes de $tamanho dias), 'mes' ou 'ano'
     * @param int    $tamanho  só para 'dia': 1 = diário, 3 = de 3 em 3 dias, 7 = semanal
     *
     * @return array{rotulos: string[], valores: int[]}
     */
    public function serie(
        CarbonInterface $de,
        CarbonInterface $ate,
        string $tipo = 'dia',
        int $tamanho = 1,
        string $status = 'realizada'
    ): array {
        $porDia = $this->contagemPorDia($de, $ate, $status);

        $rotulos = [];
        $valores = [];

        $cursor = $de->copy()->startOfDay();
        $fim    = $ate->copy()->startOfDay();

        while ($cursor->lte($fim)) {
            if ($tipo === 'mes') {
                $ini     = $cursor->copy()->startOfMonth();
                $term    = $cursor->copy()->endOfMonth();
                $rotulo  = Formatador::MESES_CURTOS[$cursor->month - 1];
                $cursor  = $ini->copy()->addMonth();
            } elseif ($tipo === 'ano') {
                $ini     = $cursor->copy()->startOfYear();
                $term    = $cursor->copy()->endOfYear();
                $rotulo  = (string) $cursor->year;
                $cursor  = $ini->copy()->addYear();
            } else {
                $ini     = $cursor->copy();
                $term    = $cursor->copy()->addDays($tamanho - 1);
                $rotulo  = $cursor->format('d/m');
                $cursor  = $cursor->copy()->addDays($tamanho);
            }

            $iniStr  = $ini->toDateString();
            $termStr = $term->toDateString();

            $rotulos[] = $rotulo;
            $valores[] = (int) $porDia
                ->filter(fn ($qtd, $dia) => $dia >= $iniStr && $dia <= $termStr)
                ->sum();
        }

        return ['rotulos' => $rotulos, 'valores' => $valores];
    }

    /**
     * Consultas realizadas por dia da semana. Índice 0 = domingo.
     *
     * @return int[]
     */
    public function porDiaDaSemana(CarbonInterface $de, CarbonInterface $ate): array
    {
        $contagem = array_fill(0, 7, 0);

        foreach ($this->contagemPorDia($de, $ate, 'realizada') as $dia => $qtd) {
            $contagem[\Carbon\Carbon::parse($dia)->dayOfWeek] += $qtd;
        }

        return $contagem;
    }

    /** Hora cheia com mais consultas realizadas. Null se não houver nenhuma. */
    public function horarioDePico(CarbonInterface $de, CarbonInterface $ate): ?array
    {
        $linha = $this->periodo($de, $ate)
            ->where('consultas.status', 'realizada')
            ->selectRaw('HOUR(consultas.horario) as hora, COUNT(*) as total')
            ->groupByRaw('HOUR(consultas.horario)')
            ->orderByDesc('total')
            ->toBase()
            ->first();

        if (! $linha) {
            return null;
        }

        $hora = (int) $linha->hora;

        return [
            'faixa' => sprintf('%02d:00 - %02d:00', $hora, ($hora + 1) % 24),
            'total' => (int) $linha->total,
        ];
    }

    // ------------------------------------------------------------------
    // Rankings (consultas realizadas)
    // ------------------------------------------------------------------

    public function porEspecialidade(CarbonInterface $de, CarbonInterface $ate, int $limite = 5): array
    {
        $linhas = $this->agrupar('especialidade_id', $de, $ate);

        $nomes = Especialidade::whereIn('id', $linhas->pluck('chave'))->pluck('nome', 'id');

        $itens = $linhas
            ->map(fn ($l) => ['nome' => $nomes[$l->chave] ?? 'Não informada', 'total' => (int) $l->total])
            ->all();

        return $this->enriquecer($this->agruparResto($itens, $limite));
    }

    public function porMedico(CarbonInterface $de, CarbonInterface $ate, int $limite = 5): array
    {
        $linhas = $this->agrupar('medico_id', $de, $ate);

        $nomes = Medico::with('user:id,name')
            ->whereIn('id', $linhas->pluck('chave'))
            ->get()
            ->mapWithKeys(fn ($m) => [$m->id => $m->user->name ?? 'Médico']);

        $itens = $linhas
            ->take($limite)
            ->map(fn ($l) => ['nome' => $nomes[$l->chave] ?? 'Médico', 'total' => (int) $l->total])
            ->all();

        return $this->enriquecer($itens);
    }

    /** Ranking por local de atendimento (para o médico ver onde mais atende). */
    public function porLocal(CarbonInterface $de, CarbonInterface $ate, int $limite = 5): array
    {
        $linhas = $this->agrupar('vinculo_id', $de, $ate);

        $nomes = Vinculo::with('local:id,nome')
            ->whereIn('id', $linhas->pluck('chave'))
            ->get()
            ->mapWithKeys(fn ($v) => [$v->id => $v->local->nome ?? 'Local']);

        // Dois vínculos podem apontar para o mesmo nome; soma por nome.
        $somado = [];
        foreach ($linhas as $l) {
            $nome = $nomes[$l->chave] ?? 'Local';
            $somado[$nome] = ($somado[$nome] ?? 0) + (int) $l->total;
        }
        arsort($somado);

        $itens = [];
        foreach (array_slice($somado, 0, $limite, true) as $nome => $total) {
            $itens[] = ['nome' => $nome, 'total' => $total];
        }

        return $this->enriquecer($itens);
    }

    /**
     * Consultas realizadas por convênio, mais a fatia "Particular".
     * O nome do convênio vem do plano que o paciente usou naquela consulta.
     */
    public function porConvenio(CarbonInterface $de, CarbonInterface $ate, int $limite = 5): array
    {
        $linhas = $this->periodo($de, $ate)
            ->where('consultas.status', 'realizada')
            ->selectRaw('consultas.forma_pagamento as forma, consultas.paciente_plano_id as plano, COUNT(*) as total')
            ->groupBy('consultas.forma_pagamento', 'consultas.paciente_plano_id')
            ->toBase()
            ->get();

        $carteirinhas = PacientePlano::with('plano.convenio:id,nome')
            ->whereIn('id', $linhas->pluck('plano')->filter())
            ->get()
            ->keyBy('id');

        $somado = [];
        foreach ($linhas as $l) {
            if ($l->forma === 'particular') {
                $nome = 'Particular';
            } else {
                $nome = $carteirinhas[$l->plano]->plano->convenio->nome ?? 'Convênio';
            }
            $somado[$nome] = ($somado[$nome] ?? 0) + (int) $l->total;
        }
        arsort($somado);

        $itens = [];
        foreach (array_slice($somado, 0, $limite, true) as $nome => $total) {
            $itens[] = ['nome' => $nome, 'total' => $total];
        }

        return $this->enriquecer($itens);
    }

    /** Nota média (1–5) das consultas do período. Null se ninguém avaliou. */
    public function mediaDeAvaliacao(CarbonInterface $de, CarbonInterface $ate): ?float
    {
        $media = Avaliacao::whereIn(
            'consulta_id',
            $this->periodo($de, $ate)->select('consultas.id')
        )->avg('estrelas');

        return $media === null ? null : (float) $media;
    }

    // ------------------------------------------------------------------
    // Relatório completo da tela "Consultas realizadas"
    // ------------------------------------------------------------------

    /**
     * Tudo que a tela "Consultas realizadas" mostra, para uma janela de
     * $dias dias terminando hoje.
     *
     * @param string $quebrarPor 'medico' (clínica) ou 'local' (médico)
     */
    public function relatorio(int $dias, string $quebrarPor): array
    {
        $ate = today();
        $de  = $ate->copy()->subDays($dias - 1);

        // Período anterior, do mesmo tamanho, colado antes deste.
        $anteriorAte = $de->copy()->subDay();
        $anteriorDe  = $anteriorAte->copy()->subDays($dias - 1);

        $atual    = $this->contagemPorStatus($de, $ate);
        $anterior = $this->contagemPorStatus($anteriorDe, $anteriorAte);

        $total    = array_sum($atual);
        $totalAnt = array_sum($anterior);

        $semDesfecho = $atual['agendada'];

        // 7 dias = um ponto por dia; 30 = de 3 em 3 dias; 90 = semanal.
        [$tamanhoLote, $rotuloSerie] = match (true) {
            $dias <= 7  => [1, 'por dia'],
            $dias <= 30 => [3, 'a cada 3 dias'],
            default     => [7, 'por semana'],
        };

        $serie = $this->serie($de, $ate, 'dia', $tamanhoLote);

        // Comparecimento = de quem tinha consulta, quantos foram.
        $base = $atual['realizada'] + $atual['nao_compareceu'];

        $porDia = $this->porDiaDaSemana($de, $ate);
        $maxDia = max($porDia);
        $diaTop = $maxDia > 0 ? array_search($maxDia, $porDia, true) : null;

        $media = $this->mediaDeAvaliacao($de, $ate);
        $pico  = $this->horarioDePico($de, $ate);

        $especialidades = $this->porEspecialidade($de, $ate);

        return [
            'dias'  => $dias,
            'de'    => Formatador::dataCurta($de),
            'ate'   => Formatador::dataCurta($ate),

            'cartoes' => [
                [
                    'icone'   => 'calendar',
                    'tom'     => 'azul',
                    'rotulo'  => 'Total de consultas',
                    'valor'   => Formatador::numero($total),
                    'variacao'=> Formatador::variacao($total, $totalAnt),
                    'nota'    => $semDesfecho > 0
                        ? Formatador::numero($semDesfecho) . ' sem desfecho registrado'
                        : 'em relação ao período anterior',
                ],
                [
                    'icone'   => 'check-circle',
                    'tom'     => 'verde',
                    'rotulo'  => 'Realizadas',
                    'valor'   => Formatador::numero($atual['realizada']),
                    'variacao'=> Formatador::variacao($atual['realizada'], $anterior['realizada']),
                    'nota'    => 'em relação ao período anterior',
                ],
                [
                    'icone'   => 'x-circle',
                    'tom'     => 'rosa',
                    'rotulo'  => 'Canceladas',
                    'valor'   => Formatador::numero($atual['cancelada']),
                    'variacao'=> Formatador::variacao($atual['cancelada'], $anterior['cancelada'], false),
                    'nota'    => 'em relação ao período anterior',
                ],
                [
                    'icone'   => 'user-x',
                    'tom'     => 'ambar',
                    'rotulo'  => 'Não compareceram',
                    'valor'   => Formatador::numero($atual['nao_compareceu']),
                    'variacao'=> Formatador::variacao($atual['nao_compareceu'], $anterior['nao_compareceu'], false),
                    'nota'    => 'em relação ao período anterior',
                ],
            ],

            'serie'        => $serie,
            'serieRotulo'  => $rotuloSerie,
            'especialidades' => $especialidades,
            'donutEspecialidades' => [
                'centro'  => Formatador::numero($atual['realizada']),
                'legenda' => 'consultas',
                'itens'   => array_map(
                    fn ($i) => ['nome' => $i['nome'], 'total' => $i['total'], 'cor' => $i['cor']],
                    $especialidades
                ),
            ],
            'ranking'      => $quebrarPor === 'local'
                ? $this->porLocal($de, $ate)
                : $this->porMedico($de, $ate),
            'rankingTitulo' => $quebrarPor === 'local' ? 'Consultas por local' : 'Consultas por médico',

            'ultimas' => $this->ultimas($quebrarPor, 6),
            'colunaExtra' => $quebrarPor === 'local' ? 'Local' : 'Médico',

            'informacoes' => [
                [
                    'icone'  => 'calendar',
                    'rotulo' => 'Dia com mais consultas',
                    'valor'  => $diaTop !== null ? Formatador::DIAS[$diaTop] : '—',
                    'nota'   => $diaTop !== null ? '(' . Formatador::numero($maxDia) . ' consultas)' : 'sem consultas no período',
                ],
                [
                    'icone'  => 'clock',
                    'rotulo' => 'Horário de pico',
                    'valor'  => $pico ? $pico['faixa'] : '—',
                    'nota'   => $pico ? '(' . Formatador::numero($pico['total']) . ' consultas)' : 'sem consultas no período',
                ],
                [
                    'icone'  => 'user-check',
                    'rotulo' => 'Comparecimento',
                    'valor'  => $base > 0 ? round($atual['realizada'] / $base * 100) . '%' : '—',
                    'nota'   => 'de quem tinha consulta marcada',
                ],
                [
                    'icone'  => 'star',
                    'rotulo' => 'Avaliação média',
                    'valor'  => $media !== null ? Formatador::numero($media, 1) : '—',
                    'nota'   => $media !== null ? '(de 5 estrelas)' : 'ainda sem avaliações',
                ],
            ],
        ];
    }

    /**
     * Últimas consultas que já passaram (qualquer status), da mais nova
     * para a mais antiga. Sem observações clínicas e sem acessibilidade:
     * só o que o agendamento sabe (AGENTS.md §6).
     */
    public function ultimas(string $quebrarPor = 'medico', int $limite = 6): array
    {
        $consultas = (clone $this->base)
            ->where('consultas.data_consulta', '<=', today()->toDateString())
            ->with('paciente.user:id,name', 'medico.user:id,name', 'especialidade:id,nome', 'vinculo.local:id,nome')
            ->orderByDesc('consultas.data_consulta')
            ->orderByDesc('consultas.horario')
            ->limit($limite)
            ->get();

        return $consultas->map(function (Consulta $c) use ($quebrarPor) {
            $status = Formatador::status($c->status);

            return [
                'quando'       => Formatador::dataCurta($c->data_consulta) . ' - ' . Formatador::hora($c->horario),
                'paciente'     => $c->paciente->user->name ?? 'Paciente',
                'iniciais'     => Formatador::iniciais($c->paciente->user->name ?? '?'),
                'extra'        => $quebrarPor === 'local'
                    ? ($c->vinculo->local->nome ?? '—')
                    : ($c->medico->user->name ?? '—'),
                'especialidade'=> $c->especialidade->nome ?? '—',
                'status'       => $status['rotulo'],
                'tom'          => $status['tom'],
            ];
        })->all();
    }

    // ------------------------------------------------------------------
    // Auxiliares
    // ------------------------------------------------------------------

    /** Agrupa consultas realizadas do período por uma coluna, do maior para o menor. */
    private function agrupar(string $coluna, CarbonInterface $de, CarbonInterface $ate): Collection
    {
        return $this->periodo($de, $ate)
            ->where('consultas.status', 'realizada')
            ->selectRaw("consultas.{$coluna} as chave, COUNT(*) as total")
            ->groupBy("consultas.{$coluna}")
            ->orderByDesc('total')
            ->toBase()
            ->get();
    }

    /** Mantém os $limite maiores e soma o resto numa fatia "Outras". */
    public function agruparResto(array $itens, int $limite): array
    {
        if (count($itens) <= $limite) {
            return $itens;
        }

        $primeiros = array_slice($itens, 0, $limite - 1);
        $resto     = array_slice($itens, $limite - 1);

        $primeiros[] = [
            'nome'  => 'Outras',
            'total' => array_sum(array_column($resto, 'total')),
        ];

        return $primeiros;
    }

    /** Acrescenta porcentagem, largura da barra, cor e número formatado. */
    public function enriquecer(array $itens): array
    {
        $soma  = array_sum(array_column($itens, 'total'));
        $maior = $itens === [] ? 0 : max(array_column($itens, 'total'));

        foreach ($itens as $i => $item) {
            $itens[$i]['pct']       = $soma > 0 ? (int) round($item['total'] / $soma * 100) : 0;
            $itens[$i]['largura']   = $maior > 0 ? (int) round($item['total'] / $maior * 100) : 0;
            $itens[$i]['cor']       = Formatador::cor($i);
            $itens[$i]['total_fmt'] = Formatador::numero($item['total']);
        }

        return $itens;
    }
}
