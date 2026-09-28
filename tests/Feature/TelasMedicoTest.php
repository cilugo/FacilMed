<?php

namespace Tests\Feature;

use App\Models\Consulta;
use App\Models\User;
use Tests\TestCase;

/**
 * As 7 telas internas do médico (28/09/2026), com as views DE VERDADE.
 *
 * O TelasInternasTest confere o controller com uma view provisória; este
 * aqui abre a tela real e confere que ela desenha sem erro e mostra o que
 * precisa (e esconde o que não pode).
 */
class TelasMedicoTest extends TestCase
{
    public function test_todas_as_telas_do_medico_abrem(): void
    {
        foreach (['/medico/agenda', '/medico/horarios', '/medico/ausencias', '/medico/locais',
                  '/medico/precos', '/medico/avaliacoes', '/medico/perfil'] as $url) {
            $this->comoMedico()->get($url)->assertOk();
        }
    }

    public function test_agenda_mostra_consultas_do_dia_com_botoes_certos(): void
    {
        $consulta = Consulta::whereHas('medico.user', fn ($q) => $q->where('email', 'helena@facilmed.test'))
            ->where('status', 'agendada')->orderBy('data_consulta')->first();
        $this->assertNotNull($consulta, 'O seeder deveria ter consulta agendada da Helena.');

        $this->comoMedico()
            ->get('/medico/agenda?data=' . $consulta->data_consulta->toDateString())
            ->assertOk()
            ->assertSee($consulta->paciente->user->name)
            ->assertSee(route('medico.agenda.cancelar', $consulta), false)   // futura: dá para cancelar
            ->assertDontSee(route('medico.agenda.realizada', $consulta), false); // ...mas não marcar realizada
    }

    public function test_agenda_filtra_por_lugar_e_navega_entre_dias(): void
    {
        $helena = User::where('email', 'helena@facilmed.test')->first()->medico;
        $vinculo = $helena->vinculos()->first();

        $this->comoMedico()
            ->get('/medico/agenda?data=2026-10-05&vinculo=' . $vinculo->id)
            ->assertOk()
            ->assertSee('data=2026-10-04&amp;vinculo=' . $vinculo->id, false)
            ->assertSee('data=2026-10-06&amp;vinculo=' . $vinculo->id, false);
    }

    public function test_precos_so_edita_consultorio_proprio(): void
    {
        // A Helena só atende em clínica: nenhum formulário de preço aparece.
        $this->comoMedico()->get('/medico/precos')
            ->assertOk()
            ->assertSee('Definido por')
            ->assertDontSee('id="preco-', false);

        // Depois de criar consultório próprio, a linha vira formulário.
        $this->comoMedico()->post('/medico/locais', [
            'nome' => 'Consultório Teste', 'cep' => '12245-000', 'endereco' => 'Rua A', 'numero' => '10',
            'bairro' => 'Centro', 'cidade' => 'São José dos Campos', 'uf' => 'SP',
        ])->assertRedirect(route('medico.precos'));

        $this->comoMedico()->get('/medico/precos')
            ->assertOk()
            ->assertSee('id="preco-', false)
            ->assertSee('Você define');
    }

    public function test_preco_mostrado_com_virgula_volta_igual_ao_salvar(): void
    {
        // O back-end tira os pontos do valor. "1.250,00" tem que voltar 1250,
        // e a tela nunca pode mostrar "250.00" (viraria 25000).
        $this->comoMedico()->post('/medico/locais', [
            'nome' => 'Consultório Teste', 'cep' => '12245000', 'endereco' => 'Rua A', 'numero' => '10',
            'bairro' => 'Centro', 'cidade' => 'SJC', 'uf' => 'SP',
        ]);
        $helena  = User::where('email', 'helena@facilmed.test')->first()->medico;
        $vinculo = $helena->vinculos()->whereHas('local', fn ($q) => $q->whereNull('clinica_id'))->first();
        $esp     = $helena->especialidades()->first();

        $this->comoMedico()->post('/medico/precos', [
            'vinculo_id' => $vinculo->id, 'especialidade_id' => $esp->id, 'valor' => '1.250,00', 'ativo' => 1,
        ])->assertSessionHasNoErrors();

        $this->comoMedico()->get('/medico/precos')->assertSee('value="1.250,00"', false);
    }

    public function test_avaliacoes_mostra_comentario_para_o_medico(): void
    {
        $this->comoMedico()->get('/medico/avaliacoes')->assertOk()->assertSee('Todas as avaliações');
    }

    public function test_senha_temporaria_mostra_so_o_formulario_de_senha(): void
    {
        User::where('email', 'helena@facilmed.test')->first()->medico->update(['senha_temporaria' => true]);

        $this->comoMedico()->get('/medico/perfil')
            ->assertOk()
            ->assertSee('Crie a sua senha')
            ->assertDontSee(route('medico.perfil.convenios'), false);
    }

    public function test_perfil_mostra_dados_e_nunca_diz_validado_no_cfm(): void
    {
        $this->comoMedico()->get('/medico/perfil')
            ->assertOk()
            ->assertSee('112233')
            ->assertSee('base simulada do FacilMed')
            ->assertDontSee('validado', false);
    }
}
