<?php

namespace Tests\Feature;

use App\Models\Consulta;
use App\Models\Especialidade;
use App\Models\Medico;
use App\Models\User;
use App\Models\Vinculo;
use App\Services\CalculadoraDeHorarios;
use Tests\TestCase;

class AdminTest extends TestCase
{
    private function consultaDaAna(int $vinculo = 1, int $esp = 1): Consulta
    {
        $dias = app(CalculadoraDeHorarios::class)->proximosDias(Vinculo::find($vinculo), 1);
        $d = array_key_first($dias);
        $this->comoPaciente()->post('/agendar', ['vinculo_id' => $vinculo, 'especialidade_id' => $esp, 'data_consulta' => $d, 'horario' => $dias[$d][0], 'forma_pagamento' => 'particular'])
            ->assertSessionHasNoErrors();

        return Consulta::latest('id')->first();
    }

    public function test_bloquear_medico_com_consulta_futura_exige_confirmacao_e_cancela(): void
    {
        $consulta = $this->consultaDaAna();
        $helena = User::where('email', 'helena@facilmed.test')->first();

        $this->comoAdmin()->post("/admin/usuarios/{$helena->id}/bloquear", ['motivo' => 'curto'])->assertSessionHasErrors('motivo');
        $this->comoAdmin()->post("/admin/usuarios/{$helena->id}/bloquear", ['motivo' => 'Denúncia em análise pela equipe'])->assertSessionHas('erro');
        $this->assertSame('ativo', $helena->fresh()->status);

        $this->comoAdmin()->post("/admin/usuarios/{$helena->id}/bloquear", ['motivo' => 'Denúncia em análise pela equipe', 'cancelar_consultas' => 1])
            ->assertSessionHas('sucesso');
        $this->assertSame('bloqueado', $helena->fresh()->status);
        $this->assertSame('cancelada', $consulta->fresh()->status);
        $this->assertStringNotContainsString('Denúncia', $consulta->fresh()->motivo_cancelamento, 'motivo do bloqueio não vaza para o paciente');

        $this->get('/buscar')->assertDontSee('Helena Navarro');
    }

    public function test_clinica_bloqueada_nao_oferece_horarios(): void
    {
        $vidaPlena = User::where('email', 'contato@vidaplena.test')->first();
        $this->comoAdmin()->post("/admin/usuarios/{$vidaPlena->id}/bloquear", ['motivo' => 'Documentação vencida da clínica'])->assertSessionHas('erro');
        $this->comoAdmin()->post("/admin/usuarios/{$vidaPlena->id}/bloquear", ['motivo' => 'Documentação vencida da clínica', 'cancelar_consultas' => 1])->assertSessionHas('sucesso');
        $this->assertSame(0, $vidaPlena->consultasFuturasAfetadas()->count());

        $this->assertSame([], app(CalculadoraDeHorarios::class)->proximosDias(Vinculo::find(1), 3));
        $this->assertNotSame([], app(CalculadoraDeHorarios::class)->proximosDias(Vinculo::find(2), 3), 'Helena segue atendendo no hospital');
    }

    public function test_nao_bloqueia_admin_e_desbloqueia(): void
    {
        $admin = User::where('email', 'admin@facilmed.test')->first();
        $this->comoAdmin()->post("/admin/usuarios/{$admin->id}/bloquear", ['motivo' => 'Teste de bloqueio do admin'])->assertForbidden();

        $marcos = User::where('email', 'marcos@facilmed.test')->first();
        $this->comoAdmin()->post("/admin/usuarios/{$marcos->id}/desbloquear")->assertStatus(422);
        $this->comoAdmin()->post("/admin/usuarios/{$marcos->id}/bloquear", ['motivo' => 'Muitas faltas seguidas', 'cancelar_consultas' => 1])->assertSessionHas('sucesso');
        $this->comoAdmin()->post("/admin/usuarios/{$marcos->id}/desbloquear")->assertSessionHas('sucesso');
        $this->assertSame('ativo', $marcos->fresh()->status);
    }

    public function test_especialidade_editar_e_desativar(): void
    {
        $orto = Especialidade::where('slug', 'ortopedia')->first();
        $this->comoAdmin()->put("/admin/especialidades/{$orto->slug}", ['nome' => 'Ortopedia e Traumatologia', 'destaque' => 0, 'ativo' => 0])
            ->assertSessionHasNoErrors();
        $orto->refresh();
        $this->assertSame('ortopedia', $orto->slug, 'slug não muda');
        $this->assertFalse($orto->ativo);

        $this->comoAdmin()->put("/admin/especialidades/{$orto->slug}", ['nome' => 'Cardiologia'])->assertSessionHasErrors('nome');
    }

    public function test_nao_desativa_especialidade_com_consulta_futura(): void
    {
        $this->consultaDaAna(1, 1);
        $this->comoAdmin()->put('/admin/especialidades/clinica-geral', ['nome' => 'Clínica Geral', 'ativo' => 0])->assertSessionHas('erro');
        $this->assertTrue(Especialidade::find(1)->ativo);
    }

    public function test_rejeitar_crm_tira_da_busca_e_cancela_consultas(): void
    {
        $consulta = $this->consultaDaAna();
        $this->comoAdmin()->post('/admin/verificacoes/1/rejeitar', ['motivo' => 'CRM não confere com o nome'])->assertSessionHas('sucesso');

        $this->assertSame('rejeitado', Medico::find(1)->status_verificacao);
        $this->assertSame('cancelada', $consulta->fresh()->status);
        $this->get('/medico/1')->assertNotFound();
    }
}
