<?php

namespace Tests\Feature;

use App\Models\BaseCrm;
use App\Models\Especialidade;
use App\Models\Local;
use App\Models\Medico;
use App\Models\UsuarioPlano;
use App\Models\Plano;
use App\Models\User;
use App\Models\Vinculo;
use App\Services\BaseSimulada;
use Tests\TestCase;

/**
 * 3ª rodada da revisão de 28/09/2026 (README §11, itens 30 em diante).
 * Mesma ideia do RevisaoTest: cada teste reproduz o furo como ele era e
 * confere que agora o sistema faz o certo.
 */
class RevisaoRodada3Test extends TestCase
{
    // -----------------------------------------------------------------
    // CRM de outra pessoa (item 32) e médico rejeitado (item 33)
    // -----------------------------------------------------------------

    /**
     * 445566/SP está livre na base simulada e é do "Paulo Yamada".
     * Desde 29/09 o médico só entra pela clínica (Meus médicos → Cadastrar).
     */
    private function clinicaCadastraMedico(string $nome)
    {
        $unidade = User::where('email', 'contato@vidaplena.test')->first()->clinica->locais()->first();

        return $this->comoClinica()->post('/clinica/medicos', [
            'name' => $nome, 'email' => 'novo.medico@facilmed.test', 'cpf' => '529.982.247-25',
            'crm' => '445566', 'uf' => 'SP', 'local_id' => $unidade->id, 'aceita_particular' => '1',
            'especialidades' => [Especialidade::where('slug', 'cardiologia')->value('id')],
        ]);
    }

    public function test_cadastro_de_medico_confere_o_nome_do_crm(): void
    {
        // Antes: "Fulano" entrava verificado com o CRM do Paulo Yamada.
        $this->clinicaCadastraMedico('Fulano Qualquer')
            ->assertSessionHasErrors(['crm' => 'Esse CRM está registrado em nome de outra pessoa na base simulada do PointMed. Confira se o nome completo está igual ao do CRM.']);
        $this->assertFalse(Medico::where('crm', '445566')->exists());

        // Título, acento e maiúscula não importam.
        $this->clinicaCadastraMedico('dr. PAULO YAMADA')->assertSessionHasNoErrors();
        $this->assertSame('verificado', Medico::where('crm', '445566')->first()->status_verificacao);
    }

    public function test_nome_comparavel_ignora_titulo_acento_e_pontuacao(): void
    {
        $this->assertSame('helena navarro', BaseSimulada::nomeComparavel('Dra. Helena  Navarro'));
        $this->assertSame('joao d avila', BaseSimulada::nomeComparavel("Dr. João D'Ávila"));
        $this->assertSame('paulo yamada', BaseSimulada::nomeComparavel('Doutor Paulo Yamada'));
        $this->assertNotSame(BaseSimulada::nomeComparavel('Helena Navarro'), BaseSimulada::nomeComparavel('Helena Souza'));
    }

    public function test_clinica_nao_cadastra_medico_com_crm_de_outra_pessoa(): void
    {
        $unidade = User::where('email', 'contato@vidaplena.test')->first()->clinica->locais()->first();
        $dados = ['crm' => '445566', 'uf' => 'SP', 'local_id' => $unidade->id, 'aceita_particular' => '1',
            'email' => 'contratado@facilmed.test', 'cpf' => '529.982.247-25', 'especialidades' => [1]];

        $this->comoClinica()->post('/clinica/medicos', $dados + ['name' => 'Dr. Outro Nome'])->assertSessionHasErrors('crm');
        $this->assertFalse(Medico::where('crm', '445566')->exists());

        $this->comoClinica()->post('/clinica/medicos', $dados + ['name' => 'Dr. Paulo Yamada'])->assertSessionHasNoErrors();
        $this->assertTrue(Medico::where('crm', '445566')->exists());
    }

    // -----------------------------------------------------------------
    // Perfil público e busca só mostram onde dá para agendar (itens 34 e 35)
    // -----------------------------------------------------------------

    public function test_perfil_publico_nao_oferece_clinica_bloqueada(): void
    {
        $helena = Medico::where('crm', '112233')->firstOrFail();
        $vidaPlena = \App\Models\Local::where('nome', 'Vida Plena - Centro')->firstOrFail();

        $this->get('/medico/' . $helena->id)->assertOk()->assertSee('Vida Plena - Centro');

        User::where('email', 'contato@vidaplena.test')->update(['status' => 'bloqueado']);

        // Clínica bloqueada some do perfil do médico e a página do local não abre.
        $this->get('/medico/' . $helena->id)->assertOk()
            ->assertDontSee('Vida Plena - Centro')
            ->assertSee('Santa Clara');                 // o outro lugar dela continua
        $this->get('/local/' . $vidaPlena->id)->assertNotFound();

        // Na busca, o card também não lista mais a Vida Plena.
        auth()->logout();
        $this->get('/buscar?especialidade=cardiologia')->assertOk()
            ->assertSee('Helena Navarro')->assertDontSee('Vida Plena - Centro');
    }

    public function test_perfil_publico_nao_mostra_especialidade_desativada(): void
    {
        $rafael = Medico::where('crm', '223344')->firstOrFail();
        $this->get('/medico/' . $rafael->id)->assertOk()->assertSee('Dermatologia');

        Especialidade::where('slug', 'dermatologia')->update(['ativo' => false]);

        // Antes: o preço de Dermatologia continuava na tabela do perfil.
        $this->get('/medico/' . $rafael->id)->assertOk()
            ->assertDontSee('Dermatologia')
            ->assertSee('Clínica Geral');
    }

    public function test_busca_mostra_medico_so_com_especialidade_ativa(): void
    {
        // 05/10/2026: sem a Tabela de preços, o médico aparece na busca assim
        // que a clínica o cadastra com uma especialidade ativa (Vinculo::scopeOferece).
        $this->clinicaCadastraMedico('Paulo Yamada')->assertSessionHasNoErrors();
        auth()->logout();
        $this->flushSession(); // a mensagem de sucesso da clínica cita o nome dele

        $this->get('/buscar?especialidade=cardiologia')->assertOk()->assertSee('Paulo Yamada');

        // Especialidade desativada pelo admin: ele some (não tem mais o que oferecer).
        Especialidade::where('slug', 'cardiologia')->update(['ativo' => false]);
        $this->get('/buscar')->assertOk()->assertDontSee('Paulo Yamada')->assertSee('Rafael');
    }


    // -----------------------------------------------------------------
    // Ausência registrada de novo não duplica (item 36)
    // -----------------------------------------------------------------

    // -----------------------------------------------------------------
    // "Aprovar" carteirinha no admin confere a base simulada (item 37)
    // -----------------------------------------------------------------

}
