<?php

namespace Database\Seeders;

use App\Models\Avaliacao;
use App\Models\Consulta;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\PacientePlano;
use Illuminate\Database\Seeder;

class ConsultaSeeder extends Seeder
{
    /** Horarios do turno da manha (bloco 08:00-12:00, consultas de 30 min). */
    private const MANHA = ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30'];

    /**
     * Gera historico e agenda futura para as telas nao nascerem vazias.
     *
     * Cobre os quatro status de proposito, porque cada um exercita uma
     * regra diferente: so 'realizada' pode ser avaliada, 'cancelada'
     * devolve o horario (a coluna virtual vira NULL) e 'nao_compareceu'
     * NAO pode avaliar.
     *
     * 24/09: entrou um bloco de consultas POR CONVENIO (antes todas eram
     * particulares, e o grafico "forma de pagamento" do dashboard da
     * clinica mostrava 0% de convenio). E, no fim, o cache de avaliacoes
     * de cada medico e recalculado - antes a media ficava 0,00 mesmo
     * com avaliacoes 5 estrelas no banco.
     */
    public function run(): void
    {
        $pacientes = Paciente::all();
        $medicos   = Medico::where('status_verificacao', 'verificado')->with('vinculos.precos')->get();

        if ($pacientes->isEmpty() || $medicos->isEmpty()) {
            $this->command->warn('Sem pacientes ou medicos: rode os seeders anteriores primeiro.');
            return;
        }

        // 29/09: os outros seeders podem rodar de novo sem duplicar nada (firstOrCreate).
        // Aqui o horario depende do que ja esta marcado (horarioLivre), entao rodar de
        // novo criaria outra agenda inteira. Para recriar: composer run banco-do-zero.
        if (Consulta::exists()) {
            $this->command->info('Ja existem consultas: as de demonstracao nao foram criadas de novo.');
            return;
        }

        $criadas = 0;

        foreach ($medicos as $medico) {
            $vinculo = $medico->vinculos->first();
            if (! $vinculo) {
                continue;
            }

            $preco = $vinculo->precos->first();
            if (! $preco) {
                continue;
            }

            // --- Passado: realizadas, uma cancelada e uma falta ---
            foreach ([['realizada', 30], ['realizada', 21], ['cancelada', 14], ['nao_compareceu', 7]] as $i => [$status, $diasAtras]) {
                $data = now()->subDays($diasAtras);

                // Pula domingo e sabado: nao ha disponibilidade nesses dias.
                if ($data->isWeekend()) {
                    $data = $data->previous('friday');
                }

                $pacienteId = $pacientes->random()->id;

                // Antes: 9+$i -> a ultima caia as 12:00, FORA do bloco
                // 08:00-12:00 (o ultimo horario do bloco e 11:30).
                $horario = $this->horarioLivre($medico->id, $pacienteId, $data->toDateString(), ['09:00', '10:00', '10:30', '11:30'][$i]);
                if ($horario === null) {
                    continue;
                }

                $consulta = Consulta::create(
                    [
                        'medico_id'        => $medico->id,
                        'data_consulta'    => $data->toDateString(),
                        'horario'          => $horario,
                        'paciente_id'      => $pacienteId,
                        'vinculo_id'       => $vinculo->id,
                        'especialidade_id' => $preco->especialidade_id,
                        'forma_pagamento'  => 'particular',
                        'valor'            => $preco->valor,
                        'status'           => $status,
                        'origem'           => 'medico',
                        'cancelada_em'     => $status === 'cancelada' ? $data->copy()->subDays(2) : null,
                        'motivo_cancelamento' => $status === 'cancelada' ? 'Imprevisto do paciente' : null,
                    ]
                );
                $criadas++;

                // So consulta realizada gera avaliacao.
                if ($status === 'realizada' && ! $consulta->avaliacao) {
                    Avaliacao::create([
                        'consulta_id' => $consulta->id,
                        'paciente_id' => $consulta->paciente_id,
                        'medico_id'   => $medico->id,
                        'estrelas'    => rand(4, 5),
                        'comentario'  => 'Atendimento pontual e atencioso. (comentario visivel apenas para a gestao)',
                    ]);
                }
            }

            // --- Futuro: agendadas ---
            foreach ([3, 8] as $i => $diasFrente) {
                $data = now()->addDays($diasFrente);
                if ($data->isWeekend()) {
                    $data = $data->next('monday');
                }

                $pacienteId = $pacientes->random()->id;

                // Manha: o primeiro vinculo de cada medico e o turno da manha.
                $horario = $this->horarioLivre($medico->id, $pacienteId, $data->toDateString(), ['09:30', '10:30'][$i]);
                if ($horario === null) {
                    continue;
                }

                Consulta::create(
                    [
                        'medico_id'        => $medico->id,
                        'data_consulta'    => $data->toDateString(),
                        'horario'          => $horario,
                        'paciente_id'      => $pacienteId,
                        'vinculo_id'       => $vinculo->id,
                        'especialidade_id' => $preco->especialidade_id,
                        'forma_pagamento'  => 'particular',
                        'valor'            => $preco->valor,
                        'status'           => 'agendada',
                        'origem'           => $i === 0 ? 'medico' : 'clinica',
                    ]
                );
                $criadas++;
            }
        }

        $criadas += $this->consultasPorConvenio();

        // Sem isso, media_avaliacoes e total_avaliacoes ficam zerados.
        foreach ($medicos as $medico) {
            $medico->recalcularAvaliacoes();
        }

        $this->command->info("{$criadas} consultas de demonstracao criadas.");
    }

    /**
     * Consultas por convenio para quem tem carteirinha ATIVA.
     *
     * Segue as mesmas regras do AgendamentoController::validarCarteirinha:
     * o vinculo aceita convenio E o medico aceita o convenio do plano.
     * Valor 0,00 porque a plataforma nao cobra consulta por convenio.
     * Horarios 11:00 e 08:00 (turno da manha); se o paciente ja tiver consulta nesse
     * horario (com outro medico), horarioLivre() escolhe o proximo livre.
     */
    private function consultasPorConvenio(): int
    {
        $criadas = 0;

        $carteirinhas = PacientePlano::with('plano.convenio')
            ->where('status', 'ativa')
            ->get();

        foreach ($carteirinhas as $carteirinha) {
            $convenioId = $carteirinha->plano?->convenio_id;
            if (! $convenioId || ! $carteirinha->plano->convenio?->ativo) {
                continue;
            }

            $medicos = Medico::where('status_verificacao', 'verificado')
                ->whereHas('convenios', fn ($q) => $q->where('convenios.id', $convenioId))
                ->with(['vinculos' => fn ($q) => $q->where('aceita_convenio', true)->with('precos')])
                ->get();

            foreach ($medicos as $medico) {
                $vinculo = $medico->vinculos->first();
                $preco   = $vinculo?->precos->first();
                if (! $vinculo || ! $preco) {
                    continue;
                }

                foreach ([['realizada', -10, '11:00'], ['agendada', 5, '08:00']] as [$status, $dias, $hora]) {
                    $data = now()->addDays($dias);
                    if ($data->isWeekend()) {
                        $data = $dias < 0 ? $data->previous('friday') : $data->next('monday');
                    }

                    $horario = $this->horarioLivre($medico->id, $carteirinha->paciente_id, $data->toDateString(), $hora);
                    if ($horario === null) {
                        continue;
                    }

                    Consulta::create(
                        [
                            'medico_id'         => $medico->id,
                            'data_consulta'     => $data->toDateString(),
                            'horario'           => $horario,
                            'paciente_id'       => $carteirinha->paciente_id,
                            'vinculo_id'        => $vinculo->id,
                            'especialidade_id'  => $preco->especialidade_id,
                            'forma_pagamento'   => 'convenio',
                            'paciente_plano_id' => $carteirinha->id,
                            'valor'             => 0,
                            'status'            => $status,
                            'origem'            => 'clinica',
                        ]
                    );
                    $criadas++;
                }
            }
        }

        return $criadas;
    }

    /**
     * 29/09/2026: o horario preferido ou, se ele nao servir, o primeiro da manha em
     * que NEM o medico NEM o paciente ja tem consulta naquele dia.
     *
     * Antes o paciente era sorteado e o horario era fixo: com 3 medicos no mesmo
     * dia e hora e so 2 pacientes, a Ana sempre aparecia com duas consultas no
     * mesmo horario (em "Minhas consultas"). O agendamento agora recusa isso
     * (Paciente::consultaNoHorario), entao os dados de demonstracao tambem nao
     * podem ter. Todas as consultas daqui duram 30 min: comparar o horario de
     * inicio basta.
     */
    private function horarioLivre(int $medicoId, int $pacienteId, string $data, string $preferido): ?string
    {
        $ocupados = Consulta::whereDate('data_consulta', $data)
            ->where(fn ($q) => $q->where('medico_id', $medicoId)->orWhere('paciente_id', $pacienteId))
            ->pluck('horario')
            ->map(fn ($h) => substr($h, 0, 5))
            ->all();

        foreach (array_unique([$preferido, ...self::MANHA]) as $horario) {
            if (! in_array($horario, $ocupados, true)) {
                return $horario;
            }
        }

        return null;
    }
}
