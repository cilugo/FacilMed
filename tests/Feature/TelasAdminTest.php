<?php

namespace Tests\Feature;

use App\Models\Consulta;
use App\Models\Especialidade;
use App\Models\Medico;
use App\Models\User;
use Tests\TestCase;

/**
 * As 6 telas internas do admin (28/09/2026), com as views DE VERDADE.
 */
class TelasAdminTest extends TestCase
{
    public function test_todas_as_telas_do_admin_abrem(): void
    {
        foreach (['/admin/usuarios', '/admin/usuarios/bloqueadas', '/admin/usuarios/excluidas', '/admin/verificacoes', '/admin/carteirinhas', '/admin/clinicas',
                  '/admin/especialidades', '/admin/consultas'] as $url) {
            $this->comoAdmin()->get($url)->assertOk();
        }
    }

    public function test_usuarios_filtra_e_nao_oferece_bloquear_admin(): void
    {
        $admin = User::where('email', 'admin@facilmed.test')->first();

        $this->comoAdmin()->get('/admin/usuarios?tipo=medico')
            ->assertOk()->assertSee('helena@facilmed.test')->assertDontSee('ana@facilmed.test');

        $this->comoAdmin()->get('/admin/usuarios?tipo=admin')
            ->assertOk()->assertDontSee(route('admin.usuarios.bloquear', $admin), false);
    }

    public function test_usuario_bloqueado_mostra_motivo_e_desbloquear(): void
    {
        $marcos = User::where('email', 'marcos@facilmed.test')->first();
        $marcos->forceFill(['status' => 'bloqueado', 'motivo_bloqueio' => 'Teste de bloqueio interno', 'bloqueado_em' => now()])->save();

        $this->comoAdmin()->get('/admin/usuarios/bloqueadas')
            ->assertOk()
            ->assertSee('Teste de bloqueio interno')
            ->assertSee(route('admin.usuarios.desbloquear', $marcos), false);

        // 01/10/2026: a tela das ativas não mostra quem está bloqueado.
        $this->comoAdmin()->get('/admin/usuarios')->assertOk()->assertDontSee('marcos@facilmed.test');
        // Endereço antigo leva para a tela nova.
        $this->comoAdmin()->get('/admin/usuarios?status=bloqueado')->assertRedirect(route('admin.usuarios.bloqueadas'));
    }

    public function test_tres_telas_de_contas_separadas(): void
    {
        $marcos = User::where('email', 'marcos@facilmed.test')->first();
        $marcos->forceFill(['status' => 'bloqueado', 'motivo_bloqueio' => 'Teste de bloqueio interno', 'bloqueado_em' => now()])->save();

        $this->comoAdmin()->get('/admin/usuarios')->assertOk()
            ->assertSee('Contas ativas')->assertSee('ana@facilmed.test')->assertSee('Bloquear')
            ->assertDontSee('Desbloquear');
        $this->comoAdmin()->get('/admin/usuarios/bloqueadas')->assertOk()
            ->assertSee('marcos@facilmed.test')->assertDontSee('ana@facilmed.test');
        $this->comoAdmin()->get('/admin/usuarios/excluidas')->assertOk()
            ->assertSee('Nenhuma conta excluída')->assertDontSee('Bloquear');
    }

    public function test_verificacoes_nunca_dizem_validado_no_cfm(): void
    {
        $this->comoAdmin()->get('/admin/verificacoes')
            ->assertOk()
            ->assertSee('base simulada do FacilMed')
            ->assertDontSee('validado', false);
    }

    public function test_aprovar_crm_confere_na_base_simulada(): void
    {
        // Médico pendente com CRM CASSADO na base (998877/SP): não pode ser aprovado.
        $helena = User::where('email', 'helena@facilmed.test')->first()->medico;
        $helena->update(['crm' => '998877', 'uf' => 'SP', 'status_verificacao' => 'pendente']);

        $this->comoAdmin()->from('/admin/verificacoes')->post("/admin/verificacoes/{$helena->id}/aprovar")
            ->assertRedirect('/admin/verificacoes')
            ->assertSessionHas('erro');
        $this->assertSame('pendente', $helena->fresh()->status_verificacao);

        // Com o CRM dela de verdade (ativo na base), aprova.
        $helena->update(['crm' => '112233']);
        $this->comoAdmin()->post("/admin/verificacoes/{$helena->id}/aprovar")->assertSessionHas('sucesso');
        $this->assertSame('verificado', $helena->fresh()->status_verificacao);
    }

    public function test_carteirinhas_lista_e_filtra_por_situacao(): void
    {
        $this->comoAdmin()->get('/admin/carteirinhas')->assertOk()->assertSee('SpSaúde');
        $this->comoAdmin()->get('/admin/carteirinhas?status=recusada')->assertOk()->assertSee('Nenhuma carteirinha');
    }

    public function test_clinicas_mostra_cnpj_e_unidades(): void
    {
        $this->comoAdmin()->get('/admin/clinicas?busca=Vida')
            ->assertOk()
            ->assertSee('Clínica Vida Plena')
            ->assertSee('41.100.002/0001-30')
            ->assertDontSee('Santa Clara');
    }

    public function test_especialidades_cria_edita_e_recusa_slug_repetido(): void
    {
        $this->comoAdmin()->post('/admin/especialidades', ['nome' => 'Reumatologia', 'icone' => 'bone', 'destaque' => '0'])
            ->assertSessionHas('sucesso');
        $this->assertTrue(Especialidade::where('slug', 'reumatologia')->exists());

        // Mesmo slug com nome "diferente": antes dava erro 500 no UNIQUE do banco.
        $this->comoAdmin()->from('/admin/especialidades')
            ->post('/admin/especialidades', ['nome' => 'Reumatologia!', 'icone' => 'bone'])
            ->assertSessionHasErrors('nome');

        // Desmarcar "destaque" funciona (hidden 0 + checkbox).
        $this->comoAdmin()->put('/admin/especialidades/cardiologia', [
            'nome' => 'Cardiologia', 'icone' => 'heart', 'destaque' => '0', 'ativo' => '1',
        ])->assertSessionHas('sucesso');
        $this->assertFalse(Especialidade::where('slug', 'cardiologia')->first()->destaque);

        $this->comoAdmin()->get('/admin/especialidades')->assertOk()->assertSee('Reumatologia');
    }

    public function test_consultas_filtra_e_nao_mostra_observacoes(): void
    {
        $consulta = Consulta::first();
        $consulta->update(['observacoes' => 'Texto que o admin nao pode ver']);
        $medico = Medico::with('user')->find($consulta->medico_id);

        $this->comoAdmin()->get('/admin/consultas?medico=' . $medico->id)
            ->assertOk()
            ->assertSee($medico->user->name)
            ->assertDontSee('Texto que o admin nao pode ver');
    }
}
