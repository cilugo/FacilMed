<?php

namespace Database\Seeders;

use App\Models\Avaliacao;
use App\Models\Consulta;
use App\Models\Medico;
use App\Models\Paciente;
use Illuminate\Database\Seeder;

class ConsultaSeeder extends Seeder
{
    /**
     * Gera historico e agenda futura para as telas nao nascerem vazias.
     *
     * Cobre os quatro status de proposito, porque cada um exercita uma
     * regra diferente: so 'realizada' pode ser avaliada, 'cancelada'
     * devolve o horario (a coluna virtual vira NULL) e 'nao_compareceu'
     * NAO pode avaliar.
     */
    public function run(): void
    {
        $pacientes = Paciente::all();
        $medicos   = Medico::where('status_verificacao', 'verificado')->with('vinculos.precos')->get();

        if ($pacientes->isEmpty() || $medicos->isEmpty()) {
            $this->command->warn('Sem pacientes ou medicos: rode os seeders anteriores primeiro.');
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

                $consulta = Consulta::firstOrCreate(
                    [
                        'medico_id'     => $medico->id,
                        'data_consulta' => $data->toDateString(),
                        'horario'       => sprintf('%02d:00', 9 + $i),
                    ],
                    [
                        'paciente_id'      => $pacientes->random()->id,
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

                Consulta::firstOrCreate(
                    [
                        'medico_id'     => $medico->id,
                        'data_consulta' => $data->toDateString(),
                        'horario'       => sprintf('%02d:30', 14 + $i),
                    ],
                    [
                        'paciente_id'      => $pacientes->random()->id,
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

        $this->command->info("{$criadas} consultas de demonstracao criadas.");
    }
}
