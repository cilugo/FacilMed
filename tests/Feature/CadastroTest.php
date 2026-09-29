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

    /**
     * 29/09/2026: o médico não se cadastra mais sozinho. Ele entra pela
     * clínica (Meus médicos → Cadastrar médico), que confere o CRM na
     * mesma base simulada. O endereço antigo volta para a escolha.
     */
    public function test_autocadastro_de_medico_nao_existe_mais(): void
    {
        $this->get('/cadastro/medico')->assertRedirect('/cadastro');
        $this->post('/cadastro/medico', ['name' => 'Paulo Yamada', 'crm' => '445566', 'uf' => 'SP'] + self::SENHA)
            ->assertRedirect('/cadastro');

        $this->assertFalse(Medico::where('crm', '445566')->exists());
        $this->assertGuest();
    }

    public function test_escolha_de_cadastro_tem_so_paciente_e_clinica(): void
    {
        $this->get('/cadastro')->assertOk()
            ->assertSee(route('cadastro.paciente'))
            ->assertSee(route('cadastro.clinica'))
            ->assertDontSee('/cadastro/medico')
            ->assertDontSee('Sou médico');

        $this->get('/')->assertOk()->assertDontSee('/cadastro/medico');
    }

    /** Visual da Mariana (29/09): as telas abrem e o erro do servidor volta embaixo do campo. */
    public function test_telas_de_cadastro_no_visual_novo_mostram_o_erro_do_servidor(): void
    {
        $this->get('/cadastro/paciente')->assertOk()
            ->assertSee('css/cadastro.css')
            ->assertSee('Crie sua conta')
            ->assertSee('name="consentimento_acessibilidade"', false);

        // AGENTS §3: a tela diz que o CNPJ é conferido na BASE SIMULADA, nunca "na Receita".
        $this->get('/cadastro/clinica')->assertOk()
            ->assertSee('Conferido na base simulada do FacilMed.')
            ->assertDontSee('Receita Federal')
            ->assertSee('name="unidade_tipo" value="clinica" checked', false);

        $this->from('/cadastro/clinica')->followingRedirects()
            ->post('/cadastro/clinica', $this->clinica(['cnpj' => '11.111.111/1111-11', 'unidade_tipo' => 'hospital']))
            ->assertSee('Confira os campos marcados abaixo.')
            ->assertSee('campo--invalido')
            ->assertSee('name="unidade_tipo" value="hospital" checked', false);
    }

    public function test_crm_recusado_pela_base_quando_a_clinica_cadastra_o_medico(): void
    {
        $unidade = User::where('email', 'contato@vidaplena.test')->first()->clinica->locais()->first();

        foreach ([['998877', 'SP', 'cassado'], ['556677', 'RJ', 'suspenso'], ['123456', 'SP', 'não foi encontrado']] as [$crm, $uf, $motivo]) {
            $this->comoClinica()->post('/clinica/medicos', [
                'crm' => $crm, 'uf' => $uf, 'local_id' => $unidade->id, 'aceita_particular' => '1',
                'name' => 'Dr. Paulo Yamada', 'email' => 'novo.medico@teste.test', 'cpf' => '529.982.247-25', 'especialidades' => [1],
            ])->assertSessionHasErrors('crm');

            $this->assertStringContainsString($motivo, session('errors')->first('crm'));
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
