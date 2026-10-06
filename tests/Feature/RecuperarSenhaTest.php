<?php

namespace Tests\Feature;

use App\Mail\BrevoTransport;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

/**
 * 01/10/2026 (plano do grupo): "Esqueci minha senha" funcionando de ponta a
 * ponta, e-mail de verdade pelo Brevo e a conta de admin com e-mail real.
 * Nenhum teste fala com o Brevo de verdade: Http::fake() responde no lugar.
 */
class RecuperarSenhaTest extends TestCase
{
    public function test_pedido_de_link_tem_a_mesma_resposta_com_ou_sem_conta(): void
    {
        Notification::fake();
        $ana = User::where('email', 'ana@facilmed.test')->first();

        $this->post('/forgot-password', ['email' => 'ana@facilmed.test'])
            ->assertSessionHas('status')->assertSessionHasNoErrors();
        Notification::assertSentTo($ana, ResetPassword::class);
        $comConta = session('status');

        $this->post('/forgot-password', ['email' => 'ninguem@facilmed.test'])
            ->assertSessionHas('status', $comConta)->assertSessionHasNoErrors();
    }

    public function test_link_troca_a_senha_e_a_pessoa_entra_com_a_nova(): void
    {
        Notification::fake();
        $ana = User::where('email', 'ana@facilmed.test')->first();
        $this->post('/forgot-password', ['email' => $ana->email]);

        $token = null;
        Notification::assertSentTo($ana, ResetPassword::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });

        $this->get('/reset-password/' . $token . '?email=' . urlencode($ana->email))->assertOk();
        $this->post('/reset-password', [
            'token' => $token, 'email' => $ana->email,
            'password' => 'SenhaNova2026', 'password_confirmation' => 'SenhaNova2026',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('SenhaNova2026', $ana->fresh()->password));
        $this->post('/login', ['email' => $ana->email, 'password' => 'SenhaNova2026'])->assertRedirect();
        $this->assertAuthenticatedAs($ana->fresh());
    }

    public function test_brevo_recebe_o_email_pela_api_com_a_chave(): void
    {
        config(['mail.mailers.brevo.key' => 'chave-de-teste', 'mail.from.address' => 'grupo@facilmed.test']);
        Http::fake([BrevoTransport::URL => Http::response(['messageId' => '<abc@brevo>'], 201)]);

        Mail::mailer('brevo')->raw('Seu link para criar uma senha nova.', fn ($m) => $m->to('ana@facilmed.test', 'Ana')->subject('PointMed — troca de senha'));

        Http::assertSent(fn ($r) => $r->url() === BrevoTransport::URL
            && $r->header('api-key') === ['chave-de-teste']
            && $r['sender']['email'] === 'grupo@facilmed.test'
            && $r['to'] === [['email' => 'ana@facilmed.test', 'name' => 'Ana']]
            && $r['subject'] === 'PointMed — troca de senha'
            && str_contains($r['textContent'], 'senha nova'));
    }

    public function test_brevo_recusou_vira_erro_no_log_e_nao_some_em_silencio(): void
    {
        config(['mail.mailers.brevo.key' => 'chave-errada']);
        Http::fake([BrevoTransport::URL => Http::response(['message' => 'Key not found'], 401)]);

        $this->expectException(TransportException::class);
        Mail::mailer('brevo')->raw('x', fn ($m) => $m->to('ana@facilmed.test')->subject('x'));
    }

    public function test_falha_no_envio_mostra_aviso_e_nao_erro_500(): void
    {
        config(['mail.default' => 'brevo', 'mail.mailers.brevo.key' => 'chave-errada']);
        Http::fake([BrevoTransport::URL => Http::response(['message' => 'Key not found'], 401)]);

        $this->post('/forgot-password', ['email' => 'ana@facilmed.test'])
            ->assertSessionHasErrors(['email' => 'Não conseguimos enviar o e-mail agora. Tente de novo em alguns minutos.']);
    }

    public function test_conta_de_admin_com_email_real_pelas_variaveis_do_servidor(): void
    {
        try {
            putenv('ADMIN_EMAIL=grupo.admin@facilmed.test');
            putenv('ADMIN_PASSWORD=curta');
            $this->artisan('facilmed:garantir-admin')->assertSuccessful();
            $this->assertNull(User::where('email', 'grupo.admin@facilmed.test')->first());   // senha curta: não cria

            putenv('ADMIN_PASSWORD=SenhaDoGrupo2026');
            $this->artisan('facilmed:garantir-admin')->assertSuccessful();
            $admin = User::where('email', 'grupo.admin@facilmed.test')->firstOrFail();
            $this->assertTrue($admin->ehAdmin());
            $this->assertTrue(Hash::check('SenhaDoGrupo2026', $admin->password));

            // Rodar de novo (todo boot) não mexe na senha que a pessoa trocou.
            $admin->forceFill(['password' => 'TroqueiDepois2026'])->save();
            $this->artisan('facilmed:garantir-admin')->assertSuccessful();
            $this->assertTrue(Hash::check('TroqueiDepois2026', $admin->fresh()->password));

            // E-mail de conta de outro tipo: não vira admin.
            putenv('ADMIN_EMAIL=ana@facilmed.test');
            $this->artisan('facilmed:garantir-admin')->assertSuccessful();
            $this->assertSame('usuario', User::where('email', 'ana@facilmed.test')->value('tipo'));
        } finally {
            putenv('ADMIN_EMAIL');
            putenv('ADMIN_PASSWORD');
        }
    }
}
