<?php

namespace Tests\Feature;

use App\Models\Consulta;
use App\Models\PacienteAcessibilidade;
use App\Models\User;
use App\Models\Vinculo;
use Tests\TestCase;

/**
 * As 7 telas internas da clínica (28/09/2026), com as views DE VERDADE.
 * A clínica padrão dos testes é a Vida Plena (contato@vidaplena.test).
 */
class TelasClinicaTest extends TestCase
{
    public function test_todas_as_telas_da_clinica_abrem(): void
    {
        foreach (['/clinica/agenda', '/clinica/medicos', '/clinica/medicos/novo', '/clinica/unidades',
                  '/clinica/precos', '/clinica/avaliacoes', '/clinica/perfil'] as $url) {
            $this->comoClinica()->get($url)->assertOk();
        }
    }

    public function test_agenda_mostra_consulta_com_medico_e_acessibilidade(): void
    {
        $clinica = User::where('email', 'contato@vidaplena.test')->first()->clinica;
        $consulta = Consulta::whereIn('vinculo_id', $clinica->vinculos()->select('vinculos.id'))
            ->where('status', 'agendada')->first();
        $this->assertNotNull($consulta, 'O seeder deveria ter consulta agendada na Vida Plena.');

        PacienteAcessibilidade::updateOrCreate(
            ['paciente_id' => $consulta->paciente_id],
            ['descricao' => 'Uso cadeira de rodas', 'consentimento_em' => now()],
        );

        $this->comoClinica()
            ->get('/clinica/agenda?data=' . $consulta->data_consulta->toDateString())
            ->assertOk()
            ->assertSee($consulta->paciente->user->name)
            ->assertSee($consulta->medico->user->name)
            ->assertSee('Uso cadeira de rodas')
            // A agenda da clínica é só leitura.
            ->assertDontSee('/agenda/' . $consulta->id . '/cancelar', false);
    }

    public function test_medicos_lista_e_mostra_confirmacao_de_desvinculo(): void
    {
        $this->comoClinica()->get('/clinica/medicos')
            ->assertOk()
            ->assertSee('Helena')
            ->assertSee('cancelar_consultas', false);
    }

    public function test_cadastrar_medico_novo_mostra_senha_provisoria_uma_vez_nos_precos(): void
    {
        $clinica = User::where('email', 'contato@vidaplena.test')->first()->clinica;
        $unidade = $clinica->locais()->first();

        $resposta = $this->comoClinica()->post('/clinica/medicos', [
            'crm' => '445566', 'uf' => 'SP', 'local_id' => $unidade->id,
            'aceita_particular' => '1', 'aceita_convenio' => '0',
            'name' => 'Dr. Teste Novo', 'email' => 'teste.novo@facilmed.test', 'cpf' => '529.982.247-25',
            'especialidades' => [1],
        ]);
        $resposta->assertRedirect(route('clinica.precos'))->assertSessionHas('senha_temporaria');

        $senha = session('senha_temporaria');
        $this->comoClinica()->get('/clinica/precos')->assertOk()->assertSee($senha)->assertSee('Dr. Teste Novo');

        // Na próxima visita, a senha não aparece mais.
        $this->comoClinica()->get('/clinica/precos')->assertOk()->assertDontSee($senha);
    }

    public function test_grade_de_precos_mostra_com_virgula_e_salva_igual(): void
    {
        $clinica = User::where('email', 'contato@vidaplena.test')->first()->clinica;
        $vinculo = $clinica->vinculos()->where('vinculos.ativo', true)->with('medico.especialidades')->first();
        $esp = $vinculo->medico->especialidades->first();

        $this->comoClinica()->post('/clinica/precos', [
            'precos' => [$vinculo->id => [$esp->id => '1.250,00']],
        ])->assertSessionHasNoErrors();

        $this->assertEquals(1250.00, (float) $vinculo->precos()->where('especialidade_id', $esp->id)->value('valor'));
        $this->comoClinica()->get('/clinica/precos')->assertSee('value="1.250,00"', false);
    }

    public function test_unidades_mostra_horarios_e_formulario(): void
    {
        $this->comoClinica()->get('/clinica/unidades')
            ->assertOk()
            ->assertSee('Vida Plena')
            ->assertSee('horarios[segunda][abre]', false);
    }

    public function test_erro_no_horario_de_uma_unidade_so_aparece_nela(): void
    {
        $clinica = User::where('email', 'contato@vidaplena.test')->first()->clinica;
        $local = $clinica->locais()->first();

        $this->comoClinica()
            ->from('/clinica/unidades')
            ->put("/clinica/unidades/{$local->id}/horarios", [
                '_form' => 'horarios-' . $local->id,
                'horarios' => ['segunda' => ['abre' => '18:00', 'fecha' => '08:00']],
            ])->assertSessionHasErrors('horarios.segunda.fecha');
    }

    public function test_avaliacoes_da_clinica_mostram_comentario(): void
    {
        $this->comoClinica()->get('/clinica/avaliacoes')->assertOk()->assertSee('Comentários');
    }

    public function test_perfil_mostra_cnpj_so_leitura_e_nunca_diz_validado(): void
    {
        $this->comoClinica()->get('/clinica/perfil')
            ->assertOk()
            ->assertSee('base simulada do FacilMed')
            ->assertDontSee('name="cnpj"', false)
            ->assertDontSee('validado', false);
    }
}
