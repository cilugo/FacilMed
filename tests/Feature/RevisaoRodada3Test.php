<?php

namespace Tests\Feature;

use App\Models\BaseCrm;
use App\Models\Consulta;
use App\Models\Especialidade;
use App\Models\Local;
use App\Models\Medico;
use App\Models\PacienteAcessibilidade;
use App\Models\Preco;
use App\Models\User;
use App\Models\Vinculo;
use App\Services\BaseSimulada;
use App\Support\Dinheiro;
use Tests\TestCase;

/**
 * 3ª rodada da revisão de 28/09/2026 (README §11, itens 30 em diante).
 * Mesma ideia do RevisaoTest: cada teste reproduz o furo como ele era e
 * confere que agora o sistema faz o certo.
 */
class RevisaoRodada3Test extends TestCase
{
    // -----------------------------------------------------------------
    // Preço digitado com ponto (item 30)
    // -----------------------------------------------------------------

    public function test_valor_digitado_entende_virgula_e_ponto(): void
    {
        $casos = [
            '250'         => '250',
            '250,00'      => '250.00',
            '250,5'       => '250.5',
            'R$ 250,00'   => '250.00',
            '1.250,00'    => '1250.00',
            '1.250'       => '1250',
            '12.500'      => '12500',
            '150.00'      => '150.00',   // antes: 15000
            '150.5'       => '150.5',    // antes: 1505
            ' 99,90 '     => '99.90',
        ];
        foreach ($casos as $digitado => $esperado) {
            $this->assertSame($esperado, Dinheiro::lerDigitado($digitado), "Digitado: \"$digitado\"");
        }

        // Ambíguo ou inválido: melhor recusar do que salvar um valor errado.
        foreach (['', 'abc', '1,250.00', '12,345', '1.25.0', '-10', '150.000,5x'] as $digitado) {
            $this->assertNull(Dinheiro::lerDigitado($digitado), "Digitado: \"$digitado\"");
        }
    }

    public function test_grade_da_clinica_nao_multiplica_preco_com_ponto(): void
    {
        $clinica = User::where('email', 'contato@vidaplena.test')->first()->clinica;
        $v = $clinica->vinculos()->with('medico.especialidades')->first();
        $esp = $v->medico->especialidades->first();
        $valor = fn () => Preco::where('vinculo_id', $v->id)->where('especialidade_id', $esp->id)->value('valor');

        // Antes: "150.00" era salvo como R$ 15.000,00, com a mensagem "salva".
        $this->comoClinica()->post('/clinica/precos', ['precos' => [$v->id => [$esp->id => '150.00']]])
            ->assertSessionHasNoErrors()->assertSessionHas('sucesso');
        $this->assertEquals(150.00, (float) $valor());

        $this->comoClinica()->post('/clinica/precos', ['precos' => [$v->id => [$esp->id => '1.250,00']]])
            ->assertSessionHasNoErrors();
        $this->assertEquals(1250.00, (float) $valor());

        // Ambíguo: recusa e não mexe no valor.
        $this->comoClinica()->post('/clinica/precos', ['precos' => [$v->id => [$esp->id => '1,250.00']]])
            ->assertSessionHasErrors("precos.{$v->id}.{$esp->id}");
        $this->assertEquals(1250.00, (float) $valor());
    }

    // -----------------------------------------------------------------
    // Acessibilidade só para o médico, só com consulta agendada (item 31)
    // -----------------------------------------------------------------

    /** Consulta agendada Ana + Helena, sozinha no dia, com acessibilidade preenchida. */
    private function consultaDaAnaComAcessibilidade(): Consulta
    {
        $ana = User::where('email', 'ana@facilmed.test')->first()->paciente;
        $helena = User::where('email', 'helena@facilmed.test')->first()->medico;

        PacienteAcessibilidade::updateOrCreate(['paciente_id' => $ana->id], [
            'possui_deficiencia' => true, 'descricao' => 'Uso cadeira de rodas',
            'consentimento_em' => now(), 'consentimento_versao' => '1.0',
        ]);

        $c = Consulta::where('paciente_id', $ana->id)->where('medico_id', $helena->id)
            ->where('status', 'agendada')->orderBy('data_consulta')->firstOrFail();

        // Outras consultas dos dois no mesmo dia sairiam na mesma tela: tira do caminho.
        Consulta::where('paciente_id', $ana->id)->where('medico_id', $helena->id)
            ->whereDate('data_consulta', $c->data_consulta)->whereKeyNot($c->id)->delete();

        return $c;
    }

    public function test_agenda_do_medico_so_mostra_acessibilidade_de_consulta_agendada(): void
    {
        $c = $this->consultaDaAnaComAcessibilidade();
        $url = '/medico/agenda?data=' . $c->data_consulta->toDateString();

        $this->comoMedico()->get($url)->assertOk()->assertSee('Uso cadeira de rodas');

        // Antes: continuava aparecendo depois que a consulta saía de "agendada".
        foreach (['cancelada', 'realizada', 'nao_compareceu'] as $status) {
            $c->forceFill(['status' => $status])->save();
            $this->comoMedico()->get($url)->assertOk()
                ->assertSee($c->paciente->user->name)
                ->assertDontSee('Uso cadeira de rodas');
        }
    }

    public function test_clinica_nao_ve_acessibilidade_nem_com_consulta_agendada(): void
    {
        $c = $this->consultaDaAnaComAcessibilidade();
        $clinica = User::where('email', 'contato@vidaplena.test')->first();
        $this->assertSame($clinica->clinica->id, $c->vinculo->local->clinica_id, 'A consulta deveria ser na Vida Plena.');

        // Antes: a agenda da clínica mostrava o texto, contra a Policy e o AGENTS.md §3.
        $this->comoClinica()->get('/clinica/agenda?data=' . $c->data_consulta->toDateString())
            ->assertOk()->assertSee($c->paciente->user->name)->assertDontSee('Uso cadeira de rodas');

        $this->assertFalse($clinica->can('verAcessibilidade', $c));
        $this->assertFalse($clinica->can('view', $c->paciente->acessibilidade));
        $this->assertTrue(User::where('email', 'helena@facilmed.test')->first()->can('verAcessibilidade', $c));
        $this->assertFalse(User::where('email', 'rafael@facilmed.test')->first()->can('verAcessibilidade', $c));
    }

    // -----------------------------------------------------------------
    // CRM de outra pessoa (item 32) e médico rejeitado (item 33)
    // -----------------------------------------------------------------

    private function cadastroDeMedico(string $nome): array
    {
        // 445566/SP está livre na base simulada e é do "Paulo Yamada".
        return [
            'name' => $nome, 'email' => 'novo.medico@facilmed.test',
            'password' => 'senha-segura-1', 'password_confirmation' => 'senha-segura-1',
            'cpf' => '529.982.247-25', 'crm' => '445566', 'uf' => 'SP',
            'especialidades' => [Especialidade::where('slug', 'cardiologia')->value('id')],
        ];
    }

    public function test_cadastro_de_medico_confere_o_nome_do_crm(): void
    {
        // Antes: "Fulano" entrava verificado com o CRM do Paulo Yamada.
        $this->post('/cadastro/medico', $this->cadastroDeMedico('Fulano Qualquer'))
            ->assertSessionHasErrors(['crm' => 'Esse CRM está registrado em nome de outra pessoa na base simulada do FacilMed. Confira se o nome completo está igual ao do CRM.']);
        $this->assertFalse(Medico::where('crm', '445566')->exists());
        $this->assertGuest();

        // Título, acento e maiúscula não importam.
        $this->post('/cadastro/medico', $this->cadastroDeMedico('dr. PAULO YAMADA'))->assertSessionHasNoErrors();
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

    public function test_medico_nao_troca_o_nome_para_outro_que_nao_o_do_crm(): void
    {
        $base = ['crm' => '112233', 'uf' => 'SP'];

        $this->comoMedico()->put('/medico/perfil', $base + ['name' => 'Paulo Yamada'])->assertSessionHasErrors('crm');
        $this->assertSame('Dra. Helena Navarro', User::where('email', 'helena@facilmed.test')->value('name'));

        // Tirar o "Dra." não é trocar de nome.
        $this->comoMedico()->put('/medico/perfil', $base + ['name' => 'Helena Navarro'])->assertSessionHasNoErrors();
        $this->assertSame('Helena Navarro', User::where('email', 'helena@facilmed.test')->value('name'));
    }

    public function test_medico_rejeitado_continua_rejeitado_ao_trocar_o_crm(): void
    {
        $helena = User::where('email', 'helena@facilmed.test')->first();
        $this->comoAdmin()->post('/admin/verificacoes/' . $helena->medico->id . '/rejeitar', ['motivo' => 'CRM com pendência na revisão'])
            ->assertSessionHas('sucesso');

        // Um segundo CRM dela, válido na base (outro estado). Só o teste escreve na base.
        BaseCrm::create(['crm' => '778899', 'uf' => 'RJ', 'nome' => 'Helena Navarro', 'situacao' => 'ativo']);

        // Antes: trocar o CRM voltava para "verificado" e ela reaparecia na busca.
        $this->actingAs($helena->fresh())->put('/medico/perfil', [
            'name' => 'Dra. Helena Navarro', 'crm' => '778899', 'uf' => 'RJ',
        ])->assertSessionHasNoErrors()
          ->assertSessionHas('sucesso', 'Dados salvos. O novo CRM foi conferido na base simulada, mas o seu cadastro continua recusado pela administração do FacilMed.');

        $medico = $helena->medico->fresh();
        $this->assertSame('778899', $medico->crm);
        $this->assertSame('rejeitado', $medico->status_verificacao);

        // Ela vê o porquê no menu.
        $this->actingAs($helena->fresh())->get('/medico')->assertOk()
            ->assertSee('Seu cadastro foi recusado pela administração do FacilMed.');

        // E continua fora da busca (visitante, para o nome dela não vir do menu).
        auth()->logout();
        $this->get('/buscar')->assertOk()->assertDontSee('Helena Navarro');

        // Só o admin desfaz — e o "Desfazer rejeição" confere a base de novo, com o nome.
        $this->comoAdmin()->post('/admin/verificacoes/' . $medico->id . '/aprovar')->assertSessionHas('sucesso');
        $this->assertSame('verificado', $medico->fresh()->status_verificacao);
    }

    public function test_admin_nao_aprova_crm_em_nome_de_outra_pessoa(): void
    {
        $medico = User::where('email', 'helena@facilmed.test')->first()->medico;
        $medico->forceFill(['status_verificacao' => 'rejeitado'])->save();
        $medico->user->forceFill(['name' => 'Outra Pessoa'])->save();

        $this->comoAdmin()->post('/admin/verificacoes/' . $medico->id . '/aprovar')
            ->assertSessionHas('erro', 'Não dá para aprovar Outra Pessoa. Esse CRM está registrado em nome de outra pessoa na base simulada do FacilMed. Confira se o nome completo está igual ao do CRM.');
        $this->assertSame('rejeitado', $medico->fresh()->status_verificacao);
    }

    public function test_preco_do_consultorio_nao_multiplica_valor_com_ponto(): void
    {
        $this->comoMedico()->post('/medico/locais', [
            'nome' => 'Consultório Dra. Helena', 'cep' => '12245-000', 'endereco' => 'Av. Teste', 'numero' => '100',
            'bairro' => 'Centro', 'cidade' => 'São José dos Campos', 'uf' => 'SP',
        ])->assertSessionHasNoErrors();
        $vinculo = Vinculo::where('local_id', Local::where('nome', 'Consultório Dra. Helena')->value('id'))->firstOrFail();

        $this->comoMedico()->post('/medico/precos', ['vinculo_id' => $vinculo->id, 'especialidade_id' => 2, 'valor' => '150.00'])
            ->assertSessionHasNoErrors();
        $this->assertSame('150.00', Preco::where('vinculo_id', $vinculo->id)->where('especialidade_id', 2)->value('valor'));

        $this->comoMedico()->post('/medico/precos', ['vinculo_id' => $vinculo->id, 'especialidade_id' => 2, 'valor' => '1,250.00'])
            ->assertSessionHasErrors('valor');
        $this->assertSame('150.00', Preco::where('vinculo_id', $vinculo->id)->where('especialidade_id', 2)->value('valor'));
    }
}
