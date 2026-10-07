<?php

namespace Tests\Feature;

use App\Http\Middleware\GarantirContaAtiva;
use App\Models\Avaliacao;
use App\Models\UsuarioPlano;
use App\Models\Plano;
use App\Models\User;
use App\Models\Vinculo;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * 30/09/2026 — o usuário exclui a própria conta (LGPD, plano do app).
 *
 * Não é um DELETE de verdade: as notas que o usuário deu entram na média dos
 * locais e médicos. Então a conta é ANONIMIZADA — o dado pessoal some e a
 * nota fica, sem ninguém ligado a ela (revisto em 01/10/2026, sem consultas).
 */
class ExclusaoDeContaTest extends TestCase
{
    private const CONFIRMA = ['current_password' => 'facilmed2026', 'confirmacao' => '1'];

    private function ana(): User
    {
        return User::where('email', 'ana@facilmed.test')->firstOrFail();
    }

    private function excluir(array $dados = self::CONFIRMA, string $email = 'ana@facilmed.test')
    {
        return $this->comoUsuarioFinal($email)->from('/usuario/perfil')->delete('/usuario/perfil', $dados);
    }

    public function test_dados_pessoais_somem_e_a_pessoa_sai_do_sistema(): void
    {
        $user = $this->ana();

        $this->excluir()->assertRedirect(route('login'))->assertSessionHas('status');
        $this->assertGuest();

        $user->refresh();
        $this->assertSame('Conta excluída', $user->name);
        $this->assertSame("excluida-{$user->id}@facilmed.invalid", $user->email);
        $this->assertNull($user->telefone);
        $this->assertSame('inativo', $user->status);
        $this->assertNotNull($user->excluida_em);
        $this->assertFalse(Hash::check('facilmed2026', $user->password), 'a senha antiga não pode mais funcionar');

        $usuario = $user->usuario;
        $this->assertNull($usuario->cpf);
        $this->assertNull($usuario->data_nascimento);
        $this->assertNull($usuario->sexo);
    }

    public function test_sessoes_e_pedidos_de_troca_de_senha_somem(): void
    {
        $user = $this->ana();
        DB::table('sessions')->insert([
            'id' => 'sessao-em-outro-aparelho', 'user_id' => $user->id, 'ip_address' => '10.0.0.1',
            'user_agent' => 'Celular da Ana', 'payload' => '', 'last_activity' => time(),
        ]);
        DB::table('password_reset_tokens')->insert(['email' => $user->email, 'token' => 'x', 'created_at' => now()]);

        $this->excluir();

        $this->assertFalse(DB::table('sessions')->where('user_id', $user->id)->exists());
        $this->assertFalse(DB::table('password_reset_tokens')->where('email', 'ana@facilmed.test')->exists());
    }

    public function test_banco_nao_deixa_conta_excluida_voltar_a_ativa(): void
    {
        $user = $this->ana();
        $this->excluir();

        // Mesmo um UPDATE direto no banco é barrado pelo CHECK da migration.
        $this->expectException(QueryException::class);
        DB::table('users')->where('id', $user->id)->update(['status' => 'ativo']);
    }

    public function test_a_nota_fica_e_o_comentario_sai(): void
    {
        $usuario = $this->ana()->usuario;
        // 01/10/2026: avaliação direto no médico (seed: Ana avaliou a Dra. Helena).
        $avaliacao = Avaliacao::where('usuario_id', $usuario->id)->whereNotNull('medico_id')->firstOrFail();
        $avaliacao->update(['comentario' => 'Comentário que vai sumir']);
        $medico = $avaliacao->medico;
        [$media, $total] = [$medico->media_avaliacoes, $medico->total_avaliacoes];

        $this->excluir();

        $this->assertSame(0, Avaliacao::where('usuario_id', $usuario->id)->whereNotNull('comentario')->count());
        $this->assertSame($avaliacao->estrelas, $avaliacao->fresh()->estrelas);
        $medico->refresh();
        $this->assertEquals($media, $medico->media_avaliacoes, 'a nota do médico não muda');
        $this->assertSame($total, $medico->total_avaliacoes);
    }

    public function test_precisa_da_senha_certa_e_da_confirmacao(): void
    {
        $this->excluir(['current_password' => 'errada', 'confirmacao' => '1'])
            ->assertSessionHasErrorsIn('excluirConta', 'current_password')
            ->assertSessionMissing('_old_input.current_password');   // a senha errada não fica na sessão
        $this->excluir(['current_password' => 'facilmed2026'])
            ->assertSessionHasErrorsIn('excluirConta', 'confirmacao');

        $user = $this->ana()->fresh();
        $this->assertSame('ativo', $user->status);
        $this->assertSame('Ana Beatriz Lima', $user->name);
        $this->assertNotNull($user->usuario->cpf);
    }

    public function test_conta_excluida_nao_entra_mais(): void
    {
        $user = $this->ana();
        $this->excluir();

        $this->post('/login', ['email' => 'ana@facilmed.test', 'password' => 'facilmed2026'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        // Uma sessão que tenha ficado aberta em outro aparelho cai na próxima página.
        $this->actingAs($user->fresh())->get('/usuario')->assertRedirect(route('login'));
    }

    public function test_mesmo_e_mail_e_cpf_podem_se_cadastrar_de_novo(): void
    {
        $this->excluir();

        $this->post('/cadastro/usuario', [
            'name' => 'Ana Beatriz Lima', 'email' => 'ana@facilmed.test', 'cpf' => '802.301.401-30',
            'password' => 'SenhaForte2026', 'password_confirmation' => 'SenhaForte2026',
        ])->assertSessionHasNoErrors()->assertRedirect(route('usuario.dashboard'));
    }

    public function test_so_usuario_exclui_por_aqui(): void
    {
        $this->comoClinica()->delete('/usuario/perfil', self::CONFIRMA)->assertForbidden();
        $this->assertSame('ativo', User::where('email', 'contato@vidaplena.test')->first()->status);
    }

    public function test_admin_nao_reativa_conta_excluida(): void
    {
        $user = $this->ana();
        $this->excluir();

        // Bloquear e depois "desbloquear" colocaria a conta de volta como ativa.
        $this->comoAdmin()->post(route('admin.usuarios.bloquear', $user), ['motivo' => 'Motivo qualquer de teste'])
            ->assertStatus(422);
        $this->assertSame('inativo', $user->fresh()->status);

        $this->comoAdmin()->get('/admin/usuarios?status=inativo')->assertOk()
            ->assertSee('Excluída pelo próprio usuário')
            ->assertDontSee('ana@facilmed.test');
    }

    public function test_dashboard_do_admin_nao_conta_conta_excluida(): void
    {
        $contar = fn () => collect($this->comoAdmin()->get('/admin')->assertOk()->viewData('cartoes'))
            ->firstWhere('rotulo', 'Usuários cadastrados')['valor'];

        $antes = $contar();
        $this->excluir();

        $this->assertSame((string) ((int) $antes - 1), $contar());
    }

    public function test_perfil_mostra_o_bloco_de_exclusao(): void
    {
        $this->comoUsuarioFinal()->get('/usuario/perfil')->assertOk()
            ->assertSee('Excluir minha conta')
            ->assertSee('id="excluir_senha" name="current_password"', false)
            ->assertSee('name="confirmacao"', false);
    }
}
