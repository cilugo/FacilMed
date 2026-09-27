<?php

namespace Tests\Feature;

use App\Models\Consulta;
use App\Models\Local;
use App\Models\Medico;
use App\Models\Preco;
use App\Models\User;
use App\Models\Vinculo;
use App\Services\CalculadoraDeHorarios;
use Tests\TestCase;

/**
 * Back-end da clínica. Vida Plena = unidade (local) 2, onde a Dra. Helena
 * atende (vínculo 1). SpSaúde = local 1, Dr. Rafael (vínculo 3).
 */
class ClinicaTest extends TestCase
{
    private function novoMedico(array $extra = [])
    {
        return $this->comoClinica()->post('/clinica/medicos', array_merge([
            'crm' => '445566', 'uf' => 'SP', 'local_id' => 2,
            'name' => 'Dr. Novo Contratado', 'email' => 'novo@teste.test', 'cpf' => '529.982.247-25',
            'especialidades' => [1], 'aceita_convenio' => 1,
        ], $extra));
    }

    public function test_cadastra_medico_novo_com_senha_temporaria(): void
    {
        $this->novoMedico()->assertRedirect(route('clinica.precos'))->assertSessionHas('senha_temporaria');

        $medico = Medico::where('crm', '445566')->firstOrFail();
        $this->assertSame('verificado', $medico->status_verificacao);
        $this->assertTrue($medico->senha_temporaria);
        $this->assertTrue(Vinculo::where('medico_id', $medico->id)->where('local_id', 2)->value('aceita_convenio'));
    }

    public function test_medico_com_senha_temporaria_so_troca_senha(): void
    {
        $this->novoMedico();
        $senha = session('senha_temporaria');
        $user = User::where('email', 'novo@teste.test')->first();

        $this->actingAs($user)->get('/medico')->assertRedirect(route('medico.perfil'));
        $this->actingAs($user)->put('/password', ['current_password' => $senha, 'password' => 'MinhaSenha2026', 'password_confirmation' => 'MinhaSenha2026'])
            ->assertSessionHasNoErrors();

        $this->assertFalse($user->medico->fresh()->senha_temporaria);
        $this->actingAs($user->fresh())->get('/medico')->assertOk();
    }

    public function test_crm_recusado_pela_base_nao_cadastra(): void
    {
        $this->novoMedico(['crm' => '998877'])->assertSessionHasErrors('crm');
        $this->assertNull(User::where('email', 'novo@teste.test')->first());
    }

    public function test_medico_que_ja_existe_so_ganha_vinculo(): void
    {
        // Dr. Rafael (223344/SP) passa a atender também na Vida Plena.
        $antes = Medico::count();
        $this->novoMedico(['crm' => '223344', 'name' => '', 'email' => '', 'cpf' => ''])->assertSessionHasNoErrors();

        $this->assertSame($antes, Medico::count());
        $this->assertTrue(Vinculo::where('medico_id', 2)->where('local_id', 2)->where('ativo', true)->exists());

        // Vincular de novo na mesma unidade é recusado.
        $this->novoMedico(['crm' => '223344', 'name' => '', 'email' => '', 'cpf' => ''])->assertSessionHasErrors('local_id');
    }

    public function test_nao_vincula_em_unidade_de_outra_clinica(): void
    {
        $this->novoMedico(['local_id' => 1])->assertSessionHasErrors('local_id');
    }

    public function test_desvincular_com_consulta_futura_exige_confirmacao(): void
    {
        $dias = app(CalculadoraDeHorarios::class)->proximosDias(Vinculo::find(1), 1);
        $d = array_key_first($dias);
        $this->comoPaciente()->post('/agendar', ['vinculo_id' => 1, 'especialidade_id' => 1, 'data_consulta' => $d, 'horario' => $dias[$d][0], 'forma_pagamento' => 'particular']);
        $consulta = Consulta::latest('id')->first();

        $this->comoClinica()->delete('/clinica/medicos/1')->assertSessionHas('erro');
        $this->assertTrue(Vinculo::find(1)->ativo);

        $this->comoClinica()->delete('/clinica/medicos/1', ['cancelar_consultas' => 1])->assertSessionHas('sucesso');
        $this->assertFalse(Vinculo::find(1)->ativo);
        $this->assertSame('cancelada', $consulta->fresh()->status);

        // Vínculo de outra clínica: proibido.
        $this->comoClinica()->delete('/clinica/medicos/3')->assertForbidden();
    }

    public function test_nova_unidade_com_horarios(): void
    {
        $this->comoClinica()->post('/clinica/unidades', [
            'nome' => 'Vida Plena - Sul', 'tipo' => 'clinica', 'cep' => '12230-000', 'endereco' => 'Rua B', 'numero' => '5',
            'bairro' => 'Sul', 'cidade' => 'São José dos Campos', 'uf' => 'SP',
            'horarios' => ['segunda' => ['abre' => '07:00', 'fecha' => '19:00'], 'sabado' => ['abre' => '08:00', 'fecha' => '12:00']],
        ])->assertSessionHasNoErrors();

        $local = Local::where('nome', 'Vida Plena - Sul')->firstOrFail();
        $this->assertSame(User::where('email', 'contato@vidaplena.test')->first()->clinica->id, $local->clinica_id);
        $this->assertSame(2, $local->horarios()->count());
    }

    public function test_horarios_da_unidade_avisam_blocos_fora(): void
    {
        $this->comoClinica()->put('/clinica/unidades/2/horarios', [
            'horarios' => ['segunda' => ['abre' => '09:00', 'fecha' => '18:00'], 'terca' => ['abre' => '08:00', 'fecha' => '18:00']],
        ])->assertSessionHas('sucesso');
        $this->assertStringContainsString('fora do novo horário', session('sucesso'));

        $this->comoClinica()->put('/clinica/unidades/2/horarios', ['horarios' => []])->assertSessionHas('erro');
        $this->comoClinica()->put('/clinica/unidades/1/horarios', ['horarios' => ['segunda' => ['abre' => '08:00', 'fecha' => '12:00']]])->assertForbidden();
    }

    public function test_grade_de_precos(): void
    {
        $this->comoClinica()->post('/clinica/precos', ['precos' => [1 => [1 => '199,90', 2 => '']]])->assertSessionHasNoErrors();

        $this->assertSame('199.90', Preco::where('vinculo_id', 1)->where('especialidade_id', 1)->value('valor'));
        $this->assertFalse((bool) Preco::where('vinculo_id', 1)->where('especialidade_id', 2)->value('ativo'));

        $this->comoClinica()->post('/clinica/precos', ['precos' => [1 => [3 => '100']]])->assertSessionHasErrors();     // Helena não é pediatra
        $this->comoClinica()->post('/clinica/precos', ['precos' => [1 => [1 => 'abc']]])->assertSessionHasErrors();
        $this->comoClinica()->post('/clinica/precos', ['precos' => [3 => [1 => '100']]])->assertForbidden();         // vínculo do SpSaúde
    }

    public function test_perfil_nao_muda_cnpj(): void
    {
        $clinica = User::where('email', 'contato@vidaplena.test')->first()->clinica;
        $cnpj = $clinica->cnpj;

        $this->comoClinica()->put('/clinica/perfil', ['name' => 'Nova Responsável', 'nome_fantasia' => 'Vida Plena Saúde', 'cnpj' => '00000000000000', 'telefone' => '(12) 3333-4444'])
            ->assertSessionHasNoErrors();

        $clinica->refresh();
        $this->assertSame('Vida Plena Saúde', $clinica->nome_fantasia);
        $this->assertSame($cnpj, $clinica->cnpj);
        $this->assertSame('1233334444', $clinica->telefone);
    }
}
