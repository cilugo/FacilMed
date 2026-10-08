<?php

namespace Tests\Feature;

use App\Models\BaseCnpj;
use App\Models\Especialidade;
use App\Models\Medico;
use App\Models\User;
use Tests\TestCase;

/**
 * As telas internas do admin (28/09/2026; 01/10 sem Consultas e Carteirinhas,
 * com Verificar CNPJ e Meu perfil), com as views DE VERDADE.
 */
class TelasAdminTest extends TestCase
{
    public function test_todas_as_telas_do_admin_abrem(): void
    {
        foreach (['/admin', '/admin/usuarios', '/admin/cnpjs', '/admin/clinicas', '/admin/especialidades',
                  '/admin/convenios', '/admin/perfil'] as $url) {
            $this->comoAdmin()->get($url)->assertOk();
        }

        // As telas que saíram em 01/10 não existem mais.
        foreach (['/admin/verificacoes', '/admin/carteirinhas', '/admin/consultas'] as $url) {
            $this->comoAdmin()->get($url)->assertNotFound();
        }

        // E o menu não aponta para elas.
        $this->comoAdmin()->get('/admin')
            ->assertSee('Verificar CNPJ')
            ->assertDontSee('Verificar CRM')
            ->assertDontSee('Conferir carteirinhas');
    }

    public function test_usuarios_filtra_e_nao_oferece_bloquear_admin(): void
    {
        $admin = User::where('email', 'admin@facilmed.test')->first();

        $this->comoAdmin()->get('/admin/usuarios?tipo=usuário')
            ->assertOk()->assertSee('ana@facilmed.test')->assertDontSee('contato@vidaplena.test');

        // Médico não tem conta desde 01/10: nem aparece como tipo no filtro.
        $this->comoAdmin()->get('/admin/usuarios')->assertOk()->assertDontSee('helena@facilmed.test');

        $this->comoAdmin()->get('/admin/usuarios?tipo=admin')
            ->assertOk()->assertDontSee(route('admin.usuarios.bloquear', $admin), false);
    }

    public function test_usuario_bloqueado_mostra_motivo_e_desbloquear(): void
    {
        $marcos = User::where('email', 'marcos@facilmed.test')->first();
        $marcos->forceFill(['status' => 'bloqueado', 'motivo_bloqueio' => 'Teste de bloqueio interno', 'bloqueado_em' => now()])->save();

        $this->comoAdmin()->get('/admin/usuarios?status=bloqueado')
            ->assertOk()
            ->assertSee('Teste de bloqueio interno')
            ->assertSee(route('admin.usuarios.desbloquear', $marcos), false);
    }

    public function test_verificar_cnpj_mostra_a_situacao_na_base_e_nunca_diz_validado(): void
    {
        $this->comoAdmin()->get('/admin/cnpjs')
            ->assertOk()
            ->assertSee('base simulada do PointMed')
            ->assertSee('Clínica Vida Plena')
            ->assertSee('Todos ativos na base')
            ->assertDontSee('validado', false);

        // CNPJ baixado DEPOIS do cadastro: a tela aponta, e o filtro mostra só ele.
        BaseCnpj::where('cnpj', '41100002000130')->update(['situacao' => 'baixada']);

        $this->comoAdmin()->get('/admin/cnpjs?situacao=problema')
            ->assertOk()
            ->assertSee('Clínica Vida Plena')
            ->assertSee('Baixada na base simulada')
            ->assertDontSee('Hospital Santa Clara');

        // Só o admin abre.
        $this->comoClinica()->get('/admin/cnpjs')->assertForbidden();
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

}
