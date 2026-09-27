<?php

namespace Tests\Feature;

use App\Models\Bloqueio;
use App\Models\Consulta;
use App\Models\Disponibilidade;
use App\Models\Local;
use App\Models\Medico;
use App\Models\Preco;
use App\Models\Vinculo;
use App\Services\CalculadoraDeHorarios;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Back-end do médico. Helena (medico 1): vínculo 1 = Vida Plena (seg-sex 08-12),
 * vínculo 2 = Santa Clara (seg-sex 14-18). Vida Plena abre sábado 08-12.
 */
class MedicoTest extends TestCase
{
    private function bloco(array $extra = [])
    {
        return $this->comoMedico()->post('/medico/horarios', array_merge([
            'vinculo_id' => 1, 'dia_semana' => 'sabado', 'hora_inicio' => '08:00', 'hora_fim' => '11:00',
            'duracao_consulta_minutos' => 30,
        ], $extra));
    }

    private function proximaVaga(int $vinculo = 1): array
    {
        $dias = app(CalculadoraDeHorarios::class)->proximosDias(Vinculo::find($vinculo), 1);
        $d = array_key_first($dias);

        return [$d, $dias[$d][0]];
    }

    // ---------------- Horários
    public function test_adiciona_bloco_no_sabado_e_ele_gera_horarios(): void
    {
        $this->bloco()->assertSessionHasNoErrors();

        $sabado = Carbon::parse('next saturday');
        $this->assertContains('08:00', app(CalculadoraDeHorarios::class)->paraData(Vinculo::find(1), $sabado->addWeek()));
    }

    public function test_bloco_fora_do_funcionamento_do_local_e_recusado(): void
    {
        $this->bloco(['hora_fim' => '13:00'])->assertSessionHasErrors('hora_inicio');   // sábado fecha 12h
        $this->bloco(['dia_semana' => 'domingo'])->assertSessionHasErrors('dia_semana'); // não abre
    }

    public function test_bloco_que_choca_com_outro_lugar_e_recusado(): void
    {
        // Segunda 13-15 na Vida Plena choca com Santa Clara 14-18.
        $this->bloco(['dia_semana' => 'segunda', 'hora_inicio' => '13:00', 'hora_fim' => '15:00'])
            ->assertSessionHasErrors('hora_inicio');
    }

    public function test_nao_mexe_em_vinculo_nem_bloco_de_outro_medico(): void
    {
        $this->bloco(['vinculo_id' => 3])->assertSessionHasErrors('vinculo_id');
        $doRafael = Disponibilidade::where('vinculo_id', 3)->first();
        $this->comoMedico()->delete("/medico/horarios/{$doRafael->id}")->assertForbidden();
    }

    public function test_remover_bloco_avisa_consultas_que_continuam(): void
    {
        [$data, $hora] = $this->proximaVaga();
        $this->comoPaciente()->post('/agendar', ['vinculo_id' => 1, 'especialidade_id' => 1, 'data_consulta' => $data, 'horario' => $hora, 'forma_pagamento' => 'particular']);
        $dia = Disponibilidade::DIAS[Carbon::parse($data)->dayOfWeek];
        $bloco = Disponibilidade::where('vinculo_id', 1)->where('dia_semana', $dia)->first();

        $this->comoMedico()->delete("/medico/horarios/{$bloco->id}")->assertSessionHas('sucesso');
        $this->assertStringContainsString('continua', session('sucesso'));
        $this->assertSame('agendada', Consulta::latest('id')->first()->status);
    }

    // ---------------- Ausências
    public function test_ausencia_tira_horarios_e_nao_cancela_consulta_sem_pedir(): void
    {
        [$data, $hora] = $this->proximaVaga();
        $this->comoPaciente()->post('/agendar', ['vinculo_id' => 1, 'especialidade_id' => 1, 'data_consulta' => $data, 'horario' => $hora, 'forma_pagamento' => 'particular']);
        $consulta = Consulta::latest('id')->first();

        $this->comoMedico()->post('/medico/ausencias', ['inicio' => "$data 00:00", 'fim' => "$data 23:59", 'motivo' => 'Congresso'])
            ->assertSessionHas('erro');

        $this->assertSame('agendada', $consulta->fresh()->status);
        $this->assertSame([], app(CalculadoraDeHorarios::class)->paraData(Vinculo::find(1), Carbon::parse($data)));
    }

    public function test_ausencia_com_cancelar_consultas_cancela_com_motivo(): void
    {
        [$data, $hora] = $this->proximaVaga();
        $this->comoPaciente()->post('/agendar', ['vinculo_id' => 1, 'especialidade_id' => 1, 'data_consulta' => $data, 'horario' => $hora, 'forma_pagamento' => 'particular']);
        $consulta = Consulta::latest('id')->first();

        $this->comoMedico()->post('/medico/ausencias', ['inicio' => "$data 00:00", 'fim' => "$data 23:59", 'motivo' => 'Congresso', 'cancelar_consultas' => 1])
            ->assertSessionHasNoErrors();

        $this->assertSame('cancelada', $consulta->fresh()->status);
        $this->assertSame('Ausência do médico: Congresso', $consulta->fresh()->motivo_cancelamento);
    }

    public function test_ausencia_invalida_e_remover_de_outro_medico(): void
    {
        $this->comoMedico()->post('/medico/ausencias', ['inicio' => now()->addDays(3)->toDateString(), 'fim' => now()->addDay()->toDateString()])
            ->assertSessionHasErrors('fim');

        $b = Bloqueio::create(['medico_id' => 2, 'inicio' => now()->addDay(), 'fim' => now()->addDays(2)]);
        $this->comoMedico()->delete("/medico/ausencias/{$b->id}")->assertForbidden();
    }

    // ---------------- Consultório próprio e preços
    public function test_cria_consultorio_proprio_com_vinculo_e_horarios(): void
    {
        $this->comoMedico()->post('/medico/locais', [
            'nome' => 'Consultório Dra. Helena', 'cep' => '12245-000', 'endereco' => 'Av. Teste', 'numero' => '100',
            'bairro' => 'Centro', 'cidade' => 'São José dos Campos', 'uf' => 'sp',
        ])->assertRedirect(route('medico.precos'));

        $local = Local::where('nome', 'Consultório Dra. Helena')->firstOrFail();
        $this->assertSame(1, $local->medico_id);
        $this->assertNull($local->clinica_id);
        $this->assertSame(5, $local->horarios()->count());
        $this->assertTrue(Vinculo::where('local_id', $local->id)->where('medico_id', 1)->exists());
    }

    public function test_preco_so_no_consultorio_proprio(): void
    {
        $this->test_cria_consultorio_proprio_com_vinculo_e_horarios();
        $vinculo = Vinculo::whereHas('local', fn ($q) => $q->where('nome', 'Consultório Dra. Helena'))->first();

        $this->comoMedico()->post('/medico/precos', ['vinculo_id' => $vinculo->id, 'especialidade_id' => 2, 'valor' => '350,00'])
            ->assertSessionHasNoErrors();
        $this->assertSame('350.00', Preco::where('vinculo_id', $vinculo->id)->where('especialidade_id', 2)->value('valor'));

        // Na Vida Plena quem define é a clínica.
        $this->comoMedico()->post('/medico/precos', ['vinculo_id' => 1, 'especialidade_id' => 2, 'valor' => '1'])->assertForbidden();
        // Especialidade que ela não tem.
        $this->comoMedico()->post('/medico/precos', ['vinculo_id' => $vinculo->id, 'especialidade_id' => 3, 'valor' => '100'])
            ->assertSessionHasErrors('especialidade_id');
    }

    // ---------------- Perfil
    public function test_perfil_troca_crm_so_se_a_base_aceitar(): void
    {
        $base = ['name' => 'Dra. Helena Navarro', 'uf' => 'SP', 'bio' => 'Nova bio'];

        $this->comoMedico()->put('/medico/perfil', $base + ['crm' => '998877'])->assertSessionHasErrors('crm'); // cassado
        $this->comoMedico()->put('/medico/perfil', $base + ['crm' => '223344'])->assertSessionHasErrors();      // do Rafael
        $this->comoMedico()->put('/medico/perfil', $base + ['crm' => '112233'])->assertSessionHasNoErrors();    // o dela
        $this->assertSame('Nova bio', Medico::find(1)->bio);

        $this->comoMedico()->put('/medico/perfil', $base + ['crm' => '445566'])->assertSessionHasNoErrors();    // livre na base
        $this->assertSame('445566', Medico::find(1)->crm);
    }

    public function test_especialidades_com_principal_e_precos_desativados(): void
    {
        // Helena: Clínica Geral (1) + Cardiologia (2). Fica só com Cardiologia.
        $this->comoMedico()->put('/medico/perfil/especialidades', ['especialidades' => [2, 5], 'principal' => 5])->assertSessionHasNoErrors();

        $esp = Medico::find(1)->especialidades()->get();
        $this->assertEqualsCanonicalizing([2, 5], $esp->pluck('id')->all());
        $this->assertSame(5, $esp->firstWhere('pivot.principal', true)->id);
        $this->assertFalse((bool) Preco::where('vinculo_id', 1)->where('especialidade_id', 1)->value('ativo'));
    }

    public function test_nao_tira_especialidade_com_consulta_futura(): void
    {
        [$data, $hora] = $this->proximaVaga();
        $this->comoPaciente()->post('/agendar', ['vinculo_id' => 1, 'especialidade_id' => 1, 'data_consulta' => $data, 'horario' => $hora, 'forma_pagamento' => 'particular']);

        $this->comoMedico()->put('/medico/perfil/especialidades', ['especialidades' => [2]])->assertSessionHas('erro');
        $this->assertTrue(Medico::find(1)->especialidades()->where('especialidades.id', 1)->exists());
    }

    // ---------------- Agenda
    public function test_medico_cancela_consulta_futura_com_motivo(): void
    {
        [$data, $hora] = $this->proximaVaga();
        $this->comoPaciente()->post('/agendar', ['vinculo_id' => 1, 'especialidade_id' => 1, 'data_consulta' => $data, 'horario' => $hora, 'forma_pagamento' => 'particular']);
        $c = Consulta::latest('id')->first();

        $this->comoMedico()->post("/medico/agenda/{$c->id}/cancelar")->assertSessionHasErrors('motivo');
        $this->comoMedico('rafael@facilmed.test')->post("/medico/agenda/{$c->id}/cancelar", ['motivo' => 'x'])->assertForbidden();
        $this->comoMedico()->post("/medico/agenda/{$c->id}/cancelar", ['motivo' => 'Imprevisto'])->assertSessionHasNoErrors();

        $this->assertSame('cancelada', $c->fresh()->status);
    }
}
