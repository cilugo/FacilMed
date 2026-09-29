<?php

namespace Tests\Feature;

use App\Models\Consulta;
use App\Models\User;
use Tests\TestCase;

/** Quem pode ver/mexer em quê (Policies e middlewares). */
class SegurancaTest extends TestCase
{
    public function test_paciente_nao_ve_nem_cancela_consulta_de_outro(): void
    {
        $daAna = Consulta::whereHas('paciente.user', fn ($q) => $q->where('email', 'ana@facilmed.test'))->firstOrFail();

        $this->comoPaciente('marcos@facilmed.test')->get("/paciente/consultas/{$daAna->id}")->assertForbidden();
        $this->comoPaciente('marcos@facilmed.test')->post("/paciente/consultas/{$daAna->id}/cancelar")->assertForbidden();
    }

    public function test_cada_tipo_so_entra_no_proprio_painel(): void
    {
        $this->comoPaciente()->get('/admin')->assertForbidden();
        $this->comoPaciente()->get('/medico')->assertForbidden();
        $this->comoMedico()->get('/clinica')->assertForbidden();
        $this->comoClinica()->get('/paciente')->assertForbidden();
    }

    public function test_telas_do_painel_nao_sao_engolidas_pelo_perfil_publico(): void
    {
        // /medico/{medico} vinha antes e capturava /medico/agenda (404 em tudo).
        $this->assertSame('medico.agenda', app('router')->getRoutes()->match(request()->create('/medico/agenda'))->getName());
        $this->assertSame('clinica.unidades', app('router')->getRoutes()->match(request()->create('/clinica/unidades'))->getName());
        $this->assertSame('publico.medico', app('router')->getRoutes()->match(request()->create('/medico/1'))->getName());
    }

    public function test_conta_bloqueada_e_deslogada(): void
    {
        User::where('email', 'marcos@facilmed.test')->update(['status' => 'bloqueado']);

        $this->comoPaciente('marcos@facilmed.test')->get('/paciente')
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_errado_da_mensagem_em_portugues(): void
    {
        $this->post('/login', ['email' => 'ana@facilmed.test', 'password' => 'errada'])
            ->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);
    }

    public function test_login_certo_leva_ao_painel_do_tipo(): void
    {
        $this->post('/login', ['email' => 'helena@facilmed.test', 'password' => 'facilmed2026'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect(route('medico.dashboard'));
    }

    public function test_comentario_de_avaliacao_nao_aparece_no_perfil_publico(): void
    {
        $avaliacao = \App\Models\Avaliacao::whereNotNull('comentario')->firstOrFail();

        $this->get("/medico/{$avaliacao->medico_id}")->assertOk()->assertDontSee($avaliacao->comentario);
    }

    /**
     * Formulário velho (token CSRF que não bate mais com a sessão): em vez da
     * tela crua "419 PAGE EXPIRED", volta para a página com aviso e sem a senha.
     * Nos testes o Laravel desliga a checagem de CSRF; aqui ela é religada.
     */
    public function test_formulario_desatualizado_volta_com_aviso_em_vez_de_419(): void
    {
        $this->app->bind(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, fn ($app) => new class($app, $app['encrypter']) extends \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken {
            protected function runningUnitTests()
            {
                return false;
            }
        });

        $this->get('/login')->assertOk();

        $this->from('/login')
            ->post('/login', ['_token' => 'token-velho', 'email' => 'admin@facilmed.test', 'password' => 'facilmed2026'])
            ->assertRedirect('/login')
            ->assertSessionHas('erro')
            ->assertSessionHasInput('email', 'admin@facilmed.test')
            ->assertSessionMissing('_old_input.password');

        $this->assertGuest();
        $this->get('/login')->assertSee('A página ficou desatualizada', false);

        // Pedido JSON continua recebendo o 419 de verdade.
        $this->postJson('/login', ['_token' => 'token-velho'])->assertStatus(419);
    }
}
