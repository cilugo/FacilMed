<?php

namespace Tests\Feature;

use App\Models\Avaliacao;
use App\Models\User;
use Tests\TestCase;

/** Quem pode ver/mexer em quê (Policies e middlewares). */
class SegurancaTest extends TestCase
{

    public function test_cada_tipo_so_entra_no_proprio_painel(): void
    {
        $this->comoUsuarioFinal()->get('/admin')->assertForbidden();
        $this->comoUsuarioFinal()->get('/clinica')->assertForbidden();
        $this->comoClinica()->get('/usuario')->assertForbidden();
        $this->comoClinica()->get('/admin')->assertForbidden();

        // 01/10/2026: a área do médico não existe mais.
        $this->comoUsuarioFinal()->get('/medico/agenda')->assertNotFound();
    }

    public function test_telas_do_painel_nao_sao_engolidas_pelo_perfil_publico(): void
    {
        // whereNumber: /clinica/{clinica} não pode capturar /clinica/unidades.
        $this->assertSame('clinica.unidades', app('router')->getRoutes()->match(request()->create('/clinica/unidades'))->getName());
        $this->assertSame('publico.medico', app('router')->getRoutes()->match(request()->create('/medico/1'))->getName());
    }

    public function test_conta_bloqueada_e_deslogada(): void
    {
        User::where('email', 'marcos@facilmed.test')->update(['status' => 'bloqueado']);

        $this->comoUsuarioFinal('marcos@facilmed.test')->get('/usuario')
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
        $this->post('/login', ['email' => 'contato@vidaplena.test', 'password' => 'facilmed2026'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect(route('clinica.dashboard'));

        // Médico não tem login desde 01/10/2026.
        $this->post('/logout');
        $this->post('/login', ['email' => 'helena@facilmed.test', 'password' => 'facilmed2026'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_comentario_de_avaliacao_nao_aparece_no_perfil_publico(): void
    {
        // AGENTS.md §3: em tela pública só a nota. Vale para local e médico,
        // para visitante e para OUTRO usuário (o Marcos não vê o comentário da Ana).
        $doMedico = Avaliacao::whereNotNull('comentario')->whereNotNull('medico_id')->firstOrFail();
        $doLocal = Avaliacao::whereNotNull('comentario')->whereNotNull('local_id')->firstOrFail();

        $this->get("/medico/{$doMedico->medico_id}")->assertOk()->assertDontSee($doMedico->comentario);
        $this->get("/local/{$doLocal->local_id}")->assertOk()->assertDontSee($doLocal->comentario);

        $outro = $doLocal->usuario->user->email === 'ana@facilmed.test' ? 'marcos@facilmed.test' : 'ana@facilmed.test';
        $this->comoUsuarioFinal($outro)->get("/local/{$doLocal->local_id}")->assertOk()->assertDontSee($doLocal->comentario);
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
