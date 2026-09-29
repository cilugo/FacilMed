<?php

namespace Tests\Feature;

use App\Models\Consulta;
use App\Models\Especialidade;
use App\Models\PacientePlano;
use App\Models\Vinculo;
use App\Services\CalculadoraDeHorarios;
use Tests\TestCase;

/**
 * Agendamento, cancelamento e remarcação — o coração do sistema.
 * Vínculo 1 = Dra. Helena na Vida Plena (Clínica Geral e Cardiologia, aceita SpSaúde).
 */
class AgendamentoTest extends TestCase
{
    /** Primeiro dia/horário livre do vínculo. @return array{0:string,1:string} */
    private function vaga(int $vinculoId = 1, int $pular = 0): array
    {
        $dias = app(CalculadoraDeHorarios::class)->proximosDias(Vinculo::findOrFail($vinculoId), 3);
        $data = array_keys($dias)[0];

        return [$data, $dias[$data][$pular]];
    }

    private function agendar(array $extra = [], string $email = 'ana@facilmed.test')
    {
        [$data, $hora] = $this->vaga();

        return $this->comoPaciente($email)->post('/agendar', array_merge([
            'vinculo_id' => 1, 'especialidade_id' => 1, 'data_consulta' => $data,
            'horario' => $hora, 'forma_pagamento' => 'particular',
        ], $extra));
    }

    public function test_agenda_particular_com_valor_calculado_no_servidor(): void
    {
        $this->agendar(['valor' => '0.01'])->assertSessionHasNoErrors();

        $c = Consulta::latest('id')->first();
        $this->assertSame('agendada', $c->status);
        $this->assertSame('220.00', $c->valor, 'valor vem de precos, nunca do formulario');
    }

    public function test_mesmo_horario_nao_pode_ser_marcado_duas_vezes(): void
    {
        [$data, $hora] = $this->vaga();
        $mesmo = ['data_consulta' => $data, 'horario' => $hora];

        $this->agendar($mesmo)->assertSessionHasNoErrors();
        $this->agendar($mesmo, 'marcos@facilmed.test')->assertSessionHasErrors('horario');
    }

    public function test_horario_fora_da_grade_e_recusado(): void
    {
        $this->agendar(['horario' => '08:07'])->assertSessionHasErrors('horario');
    }

    public function test_especialidade_que_o_medico_nao_atende_e_recusada_mesmo_por_convenio(): void
    {
        $pediatria = Especialidade::where('slug', 'pediatria')->value('id');
        $carteirinha = PacientePlano::whereHas('paciente.user', fn ($q) => $q->where('email', 'ana@facilmed.test'))->value('id');

        $this->agendar(['especialidade_id' => $pediatria, 'forma_pagamento' => 'convenio', 'paciente_plano_id' => $carteirinha])
            ->assertSessionHasErrors('especialidade_id');
    }

    public function test_convenio_aceito_grava_valor_zero(): void
    {
        $carteirinha = PacientePlano::whereHas('paciente.user', fn ($q) => $q->where('email', 'ana@facilmed.test'))->value('id');

        $this->agendar(['forma_pagamento' => 'convenio', 'paciente_plano_id' => $carteirinha])->assertSessionHasNoErrors();
        $this->assertSame('0.00', Consulta::latest('id')->first()->valor);
    }

    public function test_cancelar_libera_o_horario_e_nao_e_tardio_com_antecedencia(): void
    {
        [$data, $hora] = $this->vaga();
        $this->agendar()->assertSessionHasNoErrors();
        $c = Consulta::latest('id')->first();

        $this->comoPaciente()->post("/paciente/consultas/{$c->id}/cancelar", ['motivo' => 'teste'])->assertSessionHasNoErrors();
        $c->refresh();

        $this->assertSame('cancelada', $c->status);
        $this->assertSame($c->inicio->diffInHours(now(), true) < 24, (bool) $c->cancelamento_tardio);
        $this->assertContains($hora, app(CalculadoraDeHorarios::class)->paraData(Vinculo::find(1), \Carbon\Carbon::parse($data)));
    }

    public function test_remarcar_cancela_a_antiga_na_mesma_operacao(): void
    {
        $this->agendar()->assertSessionHasNoErrors();
        $antiga = Consulta::latest('id')->first();
        [$data, $hora] = $this->vaga(1, 1);

        $this->comoPaciente()->get("/paciente/consultas/{$antiga->id}/remarcar")
            ->assertRedirect(route('agendamento.horario', ['vinculo' => 1, 'remarcar' => $antiga->id]));

        $this->post('/agendar', [
            'vinculo_id' => 1, 'especialidade_id' => 1, 'data_consulta' => $data, 'horario' => $hora,
            'forma_pagamento' => 'particular', 'remarcar_consulta_id' => $antiga->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame('cancelada', $antiga->fresh()->status);
        $this->assertStringStartsWith('Remarcada para', $antiga->fresh()->motivo_cancelamento);
    }

    public function test_nao_remarca_consulta_de_outro_paciente(): void
    {
        $this->agendar()->assertSessionHasNoErrors();
        $daAna = Consulta::latest('id')->first();
        [$data, $hora] = $this->vaga(1, 1);

        $this->comoPaciente('marcos@facilmed.test')->post('/agendar', [
            'vinculo_id' => 1, 'especialidade_id' => 1, 'data_consulta' => $data, 'horario' => $hora,
            'forma_pagamento' => 'particular', 'remarcar_consulta_id' => $daAna->id,
        ])->assertSessionHasErrors('horario');

        $this->assertSame('agendada', $daAna->fresh()->status);
    }

    public function test_medico_so_marca_realizada_depois_do_horario(): void
    {
        $this->agendar()->assertSessionHasNoErrors();
        $futura = Consulta::latest('id')->first();

        $this->comoMedico()->post("/medico/agenda/{$futura->id}/realizada")->assertForbidden();
        $this->comoMedico('rafael@facilmed.test')->post("/medico/agenda/{$futura->id}/realizada")->assertForbidden();
    }

    // -----------------------------------------------------------------
    // 29/09: o mesmo PACIENTE não fica em dois lugares ao mesmo tempo.
    // Vínculo 1 = Dra. Helena na Vida Plena; vínculo 3 = Dr. Rafael na
    // SpSaúde. Os dois atendem Clínica Geral de manhã, de 30 em 30 min.
    // -----------------------------------------------------------------

    /** Um dia com os dois horários ($hora e $hora + 30 min) livres nos vínculos 1 e 3. @return array{0:string,1:string,2:string} */
    private function vagaEmComum(): array
    {
        $calc = app(CalculadoraDeHorarios::class);
        $rafael = Vinculo::findOrFail(3);

        foreach ($calc->proximosDias(Vinculo::findOrFail(1), 5) as $data => $horas) {
            $doRafael = $calc->paraData($rafael, \Carbon\Carbon::parse($data));

            foreach ($horas as $hora) {
                $seguinte = \Carbon\Carbon::parse($hora)->addMinutes(30)->format('H:i');

                if (in_array($hora, $doRafael, true) && in_array($seguinte, $doRafael, true)) {
                    return [$data, $hora, $seguinte];
                }
            }
        }

        $this->fail('Os dados de teste deveriam ter um horário livre em comum nos vínculos 1 e 3.');
    }

    private function clinicaGeral(): int
    {
        return Especialidade::where('slug', 'clinica-geral')->value('id');
    }

    /** Agenda Clínica Geral, particular. */
    private function agendarEm(int $vinculoId, string $data, string $hora, string $email = 'ana@facilmed.test', array $extra = [])
    {
        return $this->comoPaciente($email)->post('/agendar', array_merge([
            'vinculo_id' => $vinculoId, 'especialidade_id' => $this->clinicaGeral(), 'data_consulta' => $data,
            'horario' => $hora, 'forma_pagamento' => 'particular',
        ], $extra));
    }

    public function test_paciente_nao_marca_duas_consultas_no_mesmo_horario(): void
    {
        [$data, $hora] = $this->vagaEmComum();

        $this->agendarEm(1, $data, $hora)->assertSessionHasNoErrors();
        $this->agendarEm(3, $data, $hora)->assertSessionHasErrors('horario');

        $this->assertStringContainsString('Você já tem uma consulta nesse horário', session('errors')->first('horario'));
        $this->assertSame(0, Consulta::where('vinculo_id', 3)->whereDate('data_consulta', $data)->where('horario', $hora . ':00')->count());
    }

    public function test_horario_que_cai_no_meio_da_outra_consulta_tambem_e_recusado(): void
    {
        [$data, $hora, $seguinte] = $this->vagaEmComum();

        // Consulta de 60 min às $hora: ocupa também o horário de $hora + 30 min.
        $this->agendarEm(1, $data, $hora)->assertSessionHasNoErrors();
        Consulta::latest('id')->first()->update(['duracao_minutos' => 60]);

        $this->agendarEm(3, $data, $seguinte)->assertSessionHasErrors('horario');
    }

    public function test_consulta_logo_depois_da_outra_pode(): void
    {
        [$data, $hora, $seguinte] = $this->vagaEmComum();

        // Termina às $seguinte e a outra começa às $seguinte: encostar não é sobrepor.
        $this->agendarEm(1, $data, $hora)->assertSessionHasNoErrors();
        $this->agendarEm(3, $data, $seguinte)->assertSessionHasNoErrors();
    }

    public function test_outro_paciente_no_mesmo_horario_com_outro_medico_pode(): void
    {
        [$data, $hora] = $this->vagaEmComum();

        $this->agendarEm(1, $data, $hora)->assertSessionHasNoErrors();
        $this->agendarEm(3, $data, $hora, 'marcos@facilmed.test')->assertSessionHasNoErrors();
    }

    public function test_consulta_cancelada_libera_o_horario_do_paciente(): void
    {
        [$data, $hora] = $this->vagaEmComum();

        $this->agendarEm(1, $data, $hora)->assertSessionHasNoErrors();
        $c = Consulta::latest('id')->first();
        $this->comoPaciente()->post("/paciente/consultas/{$c->id}/cancelar", ['motivo' => 'teste'])->assertSessionHasNoErrors();

        $this->agendarEm(3, $data, $hora)->assertSessionHasNoErrors();
    }

    public function test_tela_de_confirmacao_ja_avisa_do_conflito(): void
    {
        [$data, $hora] = $this->vagaEmComum();
        $this->agendarEm(1, $data, $hora)->assertSessionHasNoErrors();

        $this->comoPaciente()->get('/agendar/3/confirmar?' . http_build_query([
            'especialidade_id' => $this->clinicaGeral(), 'data_consulta' => $data,
            'horario' => $hora, 'forma_pagamento' => 'particular',
        ]))->assertRedirect(route('agendamento.horario', ['vinculo' => 3]))
           ->assertSessionHasErrors('horario');
    }

    public function test_remarcar_para_outro_medico_no_mesmo_horario_pode(): void
    {
        [$data, $hora] = $this->vagaEmComum();

        // A consulta antiga é cancelada na mesma operação: não conta como conflito.
        $this->agendarEm(1, $data, $hora)->assertSessionHasNoErrors();
        $antiga = Consulta::latest('id')->first();

        $this->agendarEm(3, $data, $hora, extra: ['remarcar_consulta_id' => $antiga->id])->assertSessionHasNoErrors();

        $this->assertSame('cancelada', $antiga->fresh()->status);
        $this->assertSame('agendada', Consulta::latest('id')->first()->status);
    }

    public function test_dados_de_demonstracao_nao_tem_paciente_em_dois_lugares_ao_mesmo_tempo(): void
    {
        $agendadas = Consulta::where('status', 'agendada')->get()->groupBy('paciente_id');

        foreach ($agendadas as $consultas) {
            foreach ($consultas as $a) {
                foreach ($consultas as $b) {
                    if ($a->id >= $b->id) {
                        continue;
                    }

                    $sobrepoe = $a->inicio->lessThan($b->inicio->copy()->addMinutes($b->duracao_minutos))
                        && $b->inicio->lessThan($a->inicio->copy()->addMinutes($a->duracao_minutos));

                    $this->assertFalse($sobrepoe, "Consultas {$a->id} e {$b->id} do mesmo paciente se sobrepõem no seed.");
                }
            }
        }
    }
}
