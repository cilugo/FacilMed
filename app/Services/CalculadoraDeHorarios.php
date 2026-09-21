<?php

namespace App\Services;

use App\Models\Bloqueio;
use App\Models\Consulta;
use App\Models\Disponibilidade;
use App\Models\Feriado;
use App\Models\Vinculo;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * A FONTE UNICA DA VERDADE SOBRE HORARIO LIVRE.
 *
 * Leia isto antes de mexer em qualquer coisa de agendamento.
 *
 * REGRA NUMERO UM: a tela e a gravacao chamam ESTA classe. As duas.
 * No prototipo antigo, a tela que mostrava os horarios e o arquivo
 * que gravava a consulta tinham cada um a sua regra, e elas
 * divergiam - a tela oferecia horario que a gravacao recusava, e
 * pior, aceitava horario que a tela nunca mostrou (dava para marcar
 * 08:07 numa grade de 30 minutos, editando o formulario). Duas
 * regras para a mesma pergunta sempre acabam assim.
 *
 * REGRA NUMERO DOIS: isto e CALCULADO, nunca armazenado. Nao crie
 * tabela de "slots" ou "horarios_disponiveis". Ela desincroniza na
 * primeira vez que alguem mudar a agenda, e a partir dai mente.
 *
 * REGRA NUMERO TRES: isto NAO e a protecao contra duplo agendamento.
 * Entre esta classe dizer "10:00 esta livre" e o INSERT acontecer,
 * outra pessoa pode gravar 10:00. As duas passam por aqui e as duas
 * recebem "livre". A protecao real e o indice unico
 * uq_consulta_horario_ativo, no banco. Esta classe existe para nao
 * oferecer horario ocupado - nao para garantir que ele continue
 * livre.
 *
 * A CONTA, NA ORDEM
 * -----------------
 *   blocos de `disponibilidades` daquele dia da semana
 *     fatiados pela duracao_consulta_minutos do bloco
 *     DENTRO do horario de funcionamento do local
 *     MENOS consultas nao canceladas DO MEDICO naquele dia
 *     MENOS qualquer intervalo em `bloqueios`
 *     MENOS o dia inteiro, se for feriado que fecha este local
 *     E respeitando a antecedencia minima e a janela maxima
 */
class CalculadoraDeHorarios
{
    /**
     * Horarios livres de um vinculo numa data.
     *
     * Devolve strings no formato "HH:MM", em ordem. Array vazio
     * significa "nao ha horario", e isso e resposta valida - dia
     * sem atendimento, feriado, agenda cheia ou data fora da janela.
     *
     * @return array<int, string>
     */
    public function paraData(Vinculo $vinculo, CarbonInterface $data): array
    {
        $dia = Carbon::parse($data)->startOfDay();

        if (! $this->dataEstaNaJanela($dia)) {
            return [];
        }

        if (! $vinculo->ativo) {
            return [];
        }

        $local = $vinculo->local;

        if ($local === null || ! $local->ativo) {
            return [];
        }

        // Feriado fecha o dia inteiro: nao adianta calcular o resto.
        if (Feriado::fecha($local, $dia)) {
            return [];
        }

        $nomeDoDia = Disponibilidade::DIAS[$dia->dayOfWeek];

        $blocos = $vinculo->disponibilidades()
            ->where('dia_semana', $nomeDoDia)
            ->where('ativo', true)
            ->orderBy('hora_inicio')
            ->get();

        if ($blocos->isEmpty()) {
            return [];
        }

        // O local pode abrir mais tarde do que o medico se propos a
        // atender. Quem manda e o horario do LUGAR.
        $funcionamento = $local->horarios()->where('dia_semana', $nomeDoDia)->first();

        if ($funcionamento === null) {
            return [];   // local fechado nesse dia da semana
        }

        $ocupados  = $this->consultasDoDia($vinculo, $dia);
        $bloqueios = $this->bloqueiosDoDia($vinculo, $dia);
        $limite    = now()->addHours((int) config('agendamento.antecedencia_minima_horas'));

        $livres = [];

        foreach ($blocos as $bloco) {
            $duracao = (int) ($bloco->duracao_consulta_minutos
                ?: config('agendamento.duracao_padrao_minutos'));

            if ($duracao <= 0) {
                continue;   // bloco mal cadastrado: ignora em vez de dividir por zero
            }

            $inicio = $this->maiorEntre(
                $this->juntar($dia, $bloco->hora_inicio),
                $this->juntar($dia, $funcionamento->abre)
            );

            $fim = $this->menorEntre(
                $this->juntar($dia, $bloco->hora_fim),
                $this->juntar($dia, $funcionamento->fecha)
            );

            $slot = $inicio->copy();

            while ($slot->copy()->addMinutes($duracao)->lessThanOrEqualTo($fim)) {
                $slotFim = $slot->copy()->addMinutes($duracao);

                if ($slot->greaterThanOrEqualTo($limite)
                    && ! $this->colideComConsulta($slot, $slotFim, $ocupados)
                    && ! $this->colideComBloqueio($slot, $slotFim, $bloqueios)) {
                    $livres[] = $slot->format('H:i');
                }

                $slot->addMinutes($duracao);
            }
        }

        // O almoco nao tem campo proprio: sao dois blocos no mesmo
        // dia (08:00-12:00 e 14:00-18:00) e o buraco entre eles e o
        // almoco. Por isso os blocos sao percorridos em sequencia e
        // a lista final e ordenada - nunca concatenada as cegas.
        sort($livres);

        return array_values(array_unique($livres));
    }

    /**
     * Este horario especifico esta livre?
     *
     * E o que o AgendamentoController::salvar() chama ANTES do
     * insert. Usa a mesma lista de paraData() de proposito: se um
     * horario nao esta na lista que a tela mostrou, ele tambem nao
     * pode ser gravado. E isso que impede alguem editar o formulario
     * e marcar 08:07 numa grade de 30 em 30.
     */
    public function estaLivre(Vinculo $vinculo, CarbonInterface $data, string $horario): bool
    {
        $horario = substr($horario, 0, 5);   // aceita "10:00" ou "10:00:00"

        return in_array($horario, $this->paraData($vinculo, $data), true);
    }

    /**
     * Os proximos dias que tem pelo menos um horario livre.
     *
     * Serve para a tela "proxima data disponivel" e para o
     * AlocadorDeMedico comparar candidatos. Para de procurar no fim
     * da janela - e por isso que a janela precisa ter fim.
     *
     * @return array<string, array<int, string>>  data => horarios
     */
    public function proximosDias(Vinculo $vinculo, int $quantosDias = 7): array
    {
        $encontrados = [];
        $cursor = now()->startOfDay();
        $fimDaJanela = now()->startOfDay()->addDays((int) config('agendamento.janela_maxima_dias'));

        while (count($encontrados) < $quantosDias && $cursor->lessThanOrEqualTo($fimDaJanela)) {
            $horarios = $this->paraData($vinculo, $cursor);

            if ($horarios !== []) {
                $encontrados[$cursor->toDateString()] = $horarios;
            }

            $cursor->addDay();
        }

        return $encontrados;
    }

    // -----------------------------------------------------------------
    // Interno
    // -----------------------------------------------------------------

    private function dataEstaNaJanela(Carbon $dia): bool
    {
        if ($dia->isBefore(now()->startOfDay())) {
            return false;
        }

        $fim = now()->startOfDay()->addDays((int) config('agendamento.janela_maxima_dias'));

        return $dia->lessThanOrEqualTo($fim);
    }

    /**
     * Consultas que ocupam o medico nesse dia.
     *
     * ATENCAO: a busca e por MEDICO, nao por vinculo. O medico e uma
     * pessoa so - se ele tem consulta as 10h na clinica A, as 10h na
     * clinica B tambem estao ocupadas. Filtrar por vinculo deixaria
     * o mesmo medico ser marcado em dois enderecos ao mesmo tempo, e
     * o indice unico do banco (que e por medico_id) recusaria o
     * insert depois - o paciente veria um erro no fim do processo.
     *
     * Consulta cancelada nao ocupa: o horario volta a ficar livre.
     *
     * @return array<int, array{inicio: Carbon, fim: Carbon}>
     */
    private function consultasDoDia(Vinculo $vinculo, Carbon $dia): array
    {
        return Consulta::query()
            ->where('medico_id', $vinculo->medico_id)
            ->whereDate('data_consulta', $dia->toDateString())
            ->where('status', '!=', 'cancelada')
            ->get(['horario', 'duracao_minutos'])
            ->map(fn ($c) => [
                'inicio' => $this->juntar($dia, $c->horario),
                'fim'    => $this->juntar($dia, $c->horario)
                                 ->addMinutes((int) ($c->duracao_minutos ?: 30)),
            ])
            ->all();
    }

    /**
     * Bloqueios que pegam esse dia.
     *
     * Bloqueio com vinculo_id nulo vale para o medico inteiro (ferias).
     * Com vinculo_id preenchido, so naquele endereco - o medico pode
     * faltar numa clinica e atender na outra no mesmo dia.
     *
     * @return array<int, array{inicio: Carbon, fim: Carbon}>
     */
    private function bloqueiosDoDia(Vinculo $vinculo, Carbon $dia): array
    {
        return Bloqueio::query()
            ->where('medico_id', $vinculo->medico_id)
            ->where(fn ($q) => $q->whereNull('vinculo_id')
                                 ->orWhere('vinculo_id', $vinculo->id))
            ->where('inicio', '<', $dia->copy()->endOfDay())
            ->where('fim', '>', $dia->copy()->startOfDay())
            ->get(['inicio', 'fim'])
            ->map(fn ($b) => [
                'inicio' => Carbon::parse($b->inicio),
                'fim'    => Carbon::parse($b->fim),
            ])
            ->all();
    }

    /**
     * Dois intervalos se sobrepoem quando um comeca antes do outro
     * acabar E acaba depois do outro comecar.
     *
     * Encostar nao e sobrepor: uma consulta que termina 10:30 nao
     * conflita com uma que comeca 10:30. Por isso e "<" e ">", nunca
     * "<=" e ">=" - com os sinais errados, metade da agenda some.
     */
    private function sobrepoe(Carbon $aInicio, Carbon $aFim, Carbon $bInicio, Carbon $bFim): bool
    {
        return $aInicio->lessThan($bFim) && $aFim->greaterThan($bInicio);
    }

    /** @param array<int, array{inicio: Carbon, fim: Carbon}> $consultas */
    private function colideComConsulta(Carbon $inicio, Carbon $fim, array $consultas): bool
    {
        foreach ($consultas as $c) {
            if ($this->sobrepoe($inicio, $fim, $c['inicio'], $c['fim'])) {
                return true;
            }
        }

        return false;
    }

    /** @param array<int, array{inicio: Carbon, fim: Carbon}> $bloqueios */
    private function colideComBloqueio(Carbon $inicio, Carbon $fim, array $bloqueios): bool
    {
        foreach ($bloqueios as $b) {
            if ($this->sobrepoe($inicio, $fim, $b['inicio'], $b['fim'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Junta a data com um horario vindo do banco.
     *
     * Coluna TIME do MySQL chega como "08:00:00" em string, mas
     * dependendo do driver pode vir como "08:00". Normaliza os dois.
     */
    private function juntar(Carbon $dia, string $horario): Carbon
    {
        return Carbon::parse($dia->toDateString() . ' ' . substr($horario, 0, 8));
    }

    private function maiorEntre(Carbon $a, Carbon $b): Carbon
    {
        return $a->greaterThan($b) ? $a->copy() : $b->copy();
    }

    private function menorEntre(Carbon $a, Carbon $b): Carbon
    {
        return $a->lessThan($b) ? $a->copy() : $b->copy();
    }
}
