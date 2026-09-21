<?php

namespace App\Services;

use App\Models\Clinica;
use App\Models\Especialidade;
use App\Models\Vinculo;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Escolhe o medico quando o paciente marca PELA CLINICA.
 *
 * O caso de uso: a pessoa nao quer um medico especifico, quer
 * "dermatologista na Vida Plena, quinta de manha". Alguem precisa
 * decidir qual dos tres dermatologistas vai atender.
 *
 * REGRA DE DESEMPATE: quem tem mais horario livre no dia.
 *
 * Por que essa e nao outra: distribui a carga sozinha. O medico mais
 * vazio recebe primeiro, e conforme a agenda dele enche, a preferencia
 * migra naturalmente para o proximo. Nao precisa de contador, de
 * rodizio nem de campo novo no banco - a propria agenda e o criterio.
 *
 * ⚠ O NOME DO MEDICO ALOCADO APARECE ANTES DA CONFIRMACAO.
 * Decisao do grupo em 18/09/2026. O paciente nao escolheu a pessoa,
 * mas tem direito de saber quem vai atende-lo antes de confirmar -
 * e pode desistir e escolher outro.
 */
class AlocadorDeMedico
{
    public function __construct(
        private readonly CalculadoraDeHorarios $calculadora,
    ) {
    }

    /**
     * Candidatos possiveis: vinculos desta clinica em que o medico
     * atende esta especialidade, esta verificado e tem preco definido.
     *
     * As tres condicoes juntas sao o que impede oferecer um medico
     * que existe no banco mas nao pode receber paciente:
     *   - Medico::visivel() barra quem nao passou pelo admin;
     *   - o preco define o que aparece na tela de confirmacao;
     *   - sem a especialidade, o medico nao atende esse caso.
     *
     * @return \Illuminate\Support\Collection<int, Vinculo>
     */
    public function candidatos(Clinica $clinica, Especialidade $especialidade)
    {
        return Vinculo::query()
            ->where('ativo', true)
            ->whereHas('local', fn ($l) => $l->where('clinica_id', $clinica->id)
                                             ->where('ativo', true))
            ->whereHas('medico', fn ($m) => $m->visivel())
            ->whereHas('medico.especialidades', fn ($e) => $e->where('especialidades.id', $especialidade->id))
            ->whereHas('precos', fn ($p) => $p->where('especialidade_id', $especialidade->id)
                                              ->where('ativo', true))
            ->with(['medico.user', 'local', 'precos'])
            ->get();
    }

    /**
     * Escolhe um medico para uma data especifica.
     *
     * Devolve null quando nenhum candidato tem horario nesse dia -
     * e a tela precisa tratar isso oferecendo outra data, nao dando
     * erro. "Nao ha vaga nesta quinta" e resposta legitima.
     *
     * @return array{vinculo: Vinculo, horarios: array<int, string>}|null
     */
    public function paraData(Clinica $clinica, Especialidade $especialidade, CarbonInterface $data): ?array
    {
        $dia = Carbon::parse($data)->startOfDay();
        $melhor = null;

        foreach ($this->candidatos($clinica, $especialidade) as $vinculo) {
            $horarios = $this->calculadora->paraData($vinculo, $dia);

            if ($horarios === []) {
                continue;
            }

            // Mais horario livre vence. Em empate exato, fica o
            // primeiro - a ordem vem do banco e nao tem vies; o que
            // nao pode e sempre o mesmo medico ganhar, e isso a
            // propria agenda resolve conforme ela enche.
            if ($melhor === null || count($horarios) > count($melhor['horarios'])) {
                $melhor = ['vinculo' => $vinculo, 'horarios' => $horarios];
            }
        }

        return $melhor;
    }

    /**
     * Primeira data com vaga, a partir de hoje.
     *
     * Para a tela "quando tem vaga?" quando o paciente nao escolheu
     * dia. Percorre dia a dia ate achar ou ate o fim da janela -
     * de novo, e por isso que a janela precisa ter fim.
     *
     * @return array{data: string, vinculo: Vinculo, horarios: array<int, string>}|null
     */
    public function primeiraVaga(Clinica $clinica, Especialidade $especialidade): ?array
    {
        $cursor = now()->startOfDay();
        $fim = now()->startOfDay()->addDays((int) config('agendamento.janela_maxima_dias'));

        // Os candidatos nao mudam de um dia para o outro: busca uma
        // vez so, fora do laco. Sem isso sao 180 consultas ao banco
        // para descobrir que o medico esta de ferias.
        $candidatos = $this->candidatos($clinica, $especialidade);

        if ($candidatos->isEmpty()) {
            return null;
        }

        while ($cursor->lessThanOrEqualTo($fim)) {
            $melhor = null;

            foreach ($candidatos as $vinculo) {
                $horarios = $this->calculadora->paraData($vinculo, $cursor);

                if ($horarios !== [] && ($melhor === null || count($horarios) > count($melhor['horarios']))) {
                    $melhor = ['vinculo' => $vinculo, 'horarios' => $horarios];
                }
            }

            if ($melhor !== null) {
                return [
                    'data'     => $cursor->toDateString(),
                    'vinculo'  => $melhor['vinculo'],
                    'horarios' => $melhor['horarios'],
                ];
            }

            $cursor->addDay();
        }

        return null;
    }
}
