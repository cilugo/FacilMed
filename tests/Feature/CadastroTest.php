<?php

namespace Tests\Feature;

use App\Models\Medico;
use App\Models\User;
use Tests\TestCase;

/**
 * Cadastros com as bases simuladas (tabelas do README §3).
 */
class CadastroTest extends TestCase
{
    private const SENHA = ['password' => 'SenhaForte2026', 'password_confirmation' => 'SenhaForte2026'];

    private function medico(array $extra = []): array
    {
        return array_merge([
            'name' => 'Dr. Paulo Yamada', 'email' => 'novo.medico@teste.test', 'cpf' => '529.982.247-25',
            'crm' => '445566', 'uf' => 'SP', 'especialidades' => [1],
        ], self::SENHA, $extra);
    }

    private function clinica(array $extra = []): array
    {
        return array_merge([
            'name' => 'Responsável Teste', 'email' => 'nova.clinica@teste.test', 'cnpj' => '43.300.001/0001-64',
            'razao_social' => 'Clínica Teste LTDA', 'nome_fantasia' => 'Clínica Teste',
            'unidade_nome' => 'Centro', 'unidade_tipo' => 'clinica', 'unidade_cep' => '12245-000',
            'unidade_endereco' => 'Rua A', 'unidade_numero' => '10', 'unidade_bairro' => 'Centro',
            'unidade_cidade' => 'São José dos Campos', 'unidade_uf' => 'sp',
        ], self::SENHA, $extra);
    }

    public function test_medico_com_crm_ativo_na_base_entra_verificado(): void
    {
        $this->post('/cadastro/medico', $this->medico())->assertRedirect(route('medico.dashboard'));

        $medico = Medico::where('crm', '445566')->firstOrFail();
        $this->assertSame('verificado', $medico->status_verificacao);
        $this->assertSame(0, (int) $medico->anos_atuacao, 'anos_atuacao em branco vira 0 (coluna NOT NULL)');
        $this->assertAuthenticatedAs($medico->user);
    }

    public function test_medico_com_crm_recusado_pela_base_nao_entra(): void
    {
        foreach ([['998877', 'SP', 'cassado'], ['556677', 'RJ', 'suspenso'], ['123456', 'SP', 'não foi encontrado'], ['112233', 'SP', 'já está cadastrado']] as [$crm, $uf, $motivo]) {
            $this->post('/cadastro/medico', $this->medico(['crm' => $crm, 'uf' => $uf]))
                ->assertSessionHasErrors();
            $this->assertStringContainsString($motivo, collect(session('errors')->all())->join(' '));
            $this->assertNull(User::where('email', 'novo.medico@teste.test')->first());
        }
    }

    public function test_clinica_aceita_cep_com_hifen_e_cria_unidade_com_horarios(): void
    {
        $this->post('/cadastro/clinica', $this->clinica())->assertRedirect(route('clinica.dashboard'));

        $user = User::where('email', 'nova.clinica@teste.test')->firstOrFail();
        $local = $user->clinica->locais()->firstOrFail();
        $this->assertSame('12245000', $local->cep);
        $this->assertSame('SP', $local->uf);
        $this->assertSame(6, $local->horarios()->count());
    }

    public function test_clinica_com_cnpj_recusado_nao_entra(): void
    {
        foreach (['43.300.002/0001-09' => 'baixada', '41.100.001/0001-95' => 'Já existe', '11.111.111/1111-11' => 'não é válido'] as $cnpj => $motivo) {
            $this->post('/cadastro/clinica', $this->clinica(['cnpj' => $cnpj]))->assertSessionHasErrors('cnpj');
            $this->assertStringContainsString($motivo, session('errors')->first('cnpj'));
        }
    }

    public function test_paciente_se_cadastra_sem_marcar_acessibilidade(): void
    {
        $this->post('/cadastro/paciente', ['name' => 'Paciente Teste', 'email' => 'p@teste.test', 'cpf' => '529.982.247-25'] + self::SENHA)
            ->assertRedirect(route('paciente.dashboard'));

        $this->assertSame('52998224725', User::where('email', 'p@teste.test')->first()->paciente->cpf);
    }

    public function test_acessibilidade_exige_consentimento(): void
    {
        $dados = ['name' => 'Paciente Teste', 'email' => 'p@teste.test', 'cpf' => '52998224725',
            'possui_deficiencia' => '1', 'descricao_deficiencia' => 'Uso cadeira de rodas'] + self::SENHA;

        $this->post('/cadastro/paciente', $dados)->assertSessionHasErrors('consentimento_acessibilidade');

        $this->post('/cadastro/paciente', $dados + ['consentimento_acessibilidade' => '1'])
            ->assertRedirect(route('paciente.dashboard'));
        $this->assertNotNull(User::where('email', 'p@teste.test')->first()->paciente->acessibilidade);
    }

    public function test_cpf_duplicado_com_ou_sem_mascara_e_recusado(): void
    {
        $this->post('/cadastro/paciente', ['name' => 'Outra Ana', 'email' => 'x@teste.test', 'cpf' => '80230140130'] + self::SENHA)
            ->assertSessionHasErrors('cpf');
    }

    public function test_register_do_breeze_manda_para_o_cadastro_do_facilmed(): void
    {
        $this->get('/register')->assertRedirect('/cadastro');
    }
}
