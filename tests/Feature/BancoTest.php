<?php

namespace Tests\Feature;

use App\Models\Bloqueio;
use App\Models\Consulta;
use App\Models\Disponibilidade;
use App\Models\Preco;
use Illuminate\Database\QueryException;
use Tests\TestCase;

/**
 * Travas do PRÓPRIO BANCO, independentes do PHP. Se alguém gravar direto
 * (tinker, seeder, bug), o banco recusa.
 */
class BancoTest extends TestCase
{
    private function espera23000(callable $fn, string $msg): void
    {
        try {
            $fn();
            $this->fail("O banco aceitou: {$msg}");
        } catch (QueryException $e) {
            $this->assertSame('23000', $e->getCode(), $msg);
        }
    }

    public function test_dois_pacientes_no_mesmo_horario_barrado_pelo_indice_unico(): void
    {
        $dados = ['medico_id' => 1, 'vinculo_id' => 1, 'especialidade_id' => 1, 'data_consulta' => now()->addDays(10)->toDateString(),
            'horario' => '09:00', 'duracao_minutos' => 30, 'forma_pagamento' => 'particular', 'valor' => 220, 'status' => 'agendada', 'origem' => 'medico'];

        Consulta::create($dados + ['paciente_id' => 1]);
        $this->espera23000(fn () => Consulta::create($dados + ['paciente_id' => 2]), 'duas consultas no mesmo horário');
    }

    public function test_horario_de_consulta_cancelada_pode_ser_reusado(): void
    {
        $dados = ['medico_id' => 1, 'vinculo_id' => 1, 'especialidade_id' => 1, 'data_consulta' => now()->addDays(10)->toDateString(),
            'horario' => '09:00', 'duracao_minutos' => 30, 'forma_pagamento' => 'particular', 'valor' => 220, 'status' => 'agendada', 'origem' => 'medico'];

        Consulta::create($dados + ['paciente_id' => 1])->update(['status' => 'cancelada']);
        $this->assertNotNull(Consulta::create($dados + ['paciente_id' => 2])->id);
    }

    public function test_checks_do_banco(): void
    {
        $this->espera23000(fn () => Preco::create(['vinculo_id' => 1, 'especialidade_id' => 5, 'valor' => -1]), 'preço negativo');
        $this->espera23000(fn () => Disponibilidade::create(['vinculo_id' => 1, 'dia_semana' => 'sabado', 'hora_inicio' => '10:00', 'hora_fim' => '09:00']), 'bloco ao contrário');
        $this->espera23000(fn () => Bloqueio::create(['medico_id' => 1, 'inicio' => now()->addDay(), 'fim' => now()]), 'ausência ao contrário');
        $this->espera23000(fn () => \App\Models\Avaliacao::create(['consulta_id' => 1, 'paciente_id' => 1, 'medico_id' => 1, 'estrelas' => 6]), 'nota 6');
    }
}
