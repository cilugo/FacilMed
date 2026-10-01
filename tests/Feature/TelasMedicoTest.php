<?php

namespace Tests\Feature;

use App\Models\Consulta;
use App\Models\User;
use Tests\TestCase;

/**
 * As telas internas do médico, com as views DE VERDADE. 01/10/2026 (plano
 * novo do grupo): o médico só vê — agenda, consultas, avaliações e perfil.
 *
 * O TelasInternasTest confere o controller com uma view provisória; este
 * aqui abre a tela real e confere que ela desenha sem erro e mostra o que
 * precisa (e esconde o que não pode).
 */
class TelasMedicoTest extends TestCase
{
    public function test_todas_as_telas_do_medico_abrem(): void
    {
        foreach (['/medico', '/medico/agenda', '/medico/consultas', '/medico/avaliacoes', '/medico/perfil'] as $url) {
            $this->comoMedico()->get($url)->assertOk();
        }
    }

    public function test_agenda_do_medico_so_mostra_sem_botoes(): void
    {
        $consulta = Consulta::whereHas('medico.user', fn ($q) => $q->where('email', 'helena@facilmed.test'))
            ->where('status', 'agendada')->orderBy('data_consulta')->first();
        $this->assertNotNull($consulta, 'O seeder deveria ter consulta agendada da Helena.');

        $this->comoMedico()
            ->get('/medico/agenda?data=' . $consulta->data_consulta->toDateString())
            ->assertOk()
            ->assertSee($consulta->paciente->user->name)
            ->assertDontSee('/agenda/' . $consulta->id . '/cancelar', false)
            ->assertDontSee('Confirmar cancelamento')
            ->assertSee('cuidados pela clínica');
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
            ->assertDontSee('Dados profissionais');
    }

    public function test_perfil_mostra_dados_e_nunca_diz_validado_no_cfm(): void
    {
        $this->comoMedico()->get('/medico/perfil')
            ->assertOk()
            ->assertSee('112233')
            ->assertSee('base simulada do FacilMed')
            ->assertSee('cadastrados pela clínica')
            ->assertDontSee('<textarea', false)
            ->assertDontSee('validado', false);
    }

    public function test_clinica_ve_a_agenda_com_botoes_e_as_telas_novas(): void
    {
        $consulta = Consulta::where('vinculo_id', 1)->where('status', 'agendada')->orderBy('data_consulta')->firstOrFail();

        $this->comoClinica()
            ->get('/clinica/agenda?data=' . $consulta->data_consulta->toDateString())
            ->assertOk()
            ->assertSee(route('clinica.agenda.cancelar', $consulta), false)     // futura: dá para cancelar
            ->assertDontSee(route('clinica.agenda.realizada', $consulta), false); // ...mas não marcar realizada

        $this->comoClinica()->get('/clinica/horarios')->assertOk()->assertSee('Dra. Helena Navarro')->assertSee('Intervalo de almoço');
        $this->comoClinica()->get('/clinica/ausencias')->assertOk()->assertSee('Registrar ausência');
        $this->comoClinica()->get('/clinica/medicos/1/editar')->assertOk()->assertSee('Santa Clara')->assertSee('Convênios que o médico aceita');
    }
}
