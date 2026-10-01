<?php

namespace Tests\Feature;

use App\Http\Middleware\GarantirContaAtiva;
use App\Mail\AvisoDeConsulta;
use App\Models\Avaliacao;
use App\Models\Consulta;
use App\Models\NotificacaoEnviada;
use App\Models\PacientePlano;
use App\Models\Plano;
use App\Models\User;
use App\Models\Vinculo;
use App\Services\CalculadoraDeHorarios;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * 30/09/2026 — o paciente exclui a própria conta (LGPD, plano do app).
 *
 * Não é um DELETE de verdade: consultas.paciente_id é restrictOnDelete, e a
 * agenda e as notas dos médicos dependem dessas consultas. Então a conta é
 * ANONIMIZADA — o dado pessoal some e a consulta fica, sem ninguém ligado a ela.
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
        return $this->comoPaciente($email)->from('/paciente/perfil')->delete('/paciente/perfil', $dados);
    }

    /**
     * O ConsultaSeeder SORTEIA o paciente de cada consulta (o status é fixo). Para
     * o teste não depender da sorte, pega uma consulta pelo status e a passa para
     * a Ana — junto com a avaliação dela, se tiver.
     */
    private function consultaDaAna(string $status): Consulta
    {
        $pacienteId = $this->ana()->paciente->id;
        $consulta = Consulta::where('status', $status)->firstOrFail();
        $consulta->update(['paciente_id' => $pacienteId]);
        $consulta->avaliacao?->update(['paciente_id' => $pacienteId]);

        return $consulta->fresh();
    }

    /** Marca uma consulta futura para a Ana com a Dra. Helena (vínculo 1). */
    private function agendarFutura(): void
    {
        $dias = app(CalculadoraDeHorarios::class)->proximosDias(Vinculo::findOrFail(1), 3);
        $data = array_keys($dias)[0];

        $this->comoPaciente()->post('/agendar', [
            'vinculo_id' => 1, 'especialidade_id' => 1, 'data_consulta' => $data,
            'horario' => end($dias[$data]), 'forma_pagamento' => 'particular',
        ])->assertSessionHasNoErrors();
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

        $paciente = $user->paciente;
        $this->assertNull($paciente->cpf);
        $this->assertNull($paciente->data_nascimento);
        $this->assertNull($paciente->sexo);
    }

    public function test_consultas_futuras_sao_canceladas_e_o_medico_e_avisado(): void
    {
        Mail::fake();
        $this->consultaDaAna('realizada');
        $this->agendarFutura();

        $paciente = $this->ana()->paciente;
        $futuras = $this->ana()->consultasFuturasAfetadas()->pluck('id');
        $passadas = $paciente->consultas()->where('status', 'realizada')->count();
        $this->assertNotEmpty($futuras);

        $this->excluir()->assertSessionHasNoErrors();

        foreach (Consulta::whereKey($futuras)->get() as $c) {
            $this->assertSame('cancelada', $c->status);
            $this->assertSame($paciente->user_id, $c->cancelada_por);
        }
        $this->assertSame($passadas, $paciente->consultas()->where('status', 'realizada')->count(), 'o histórico fica');

        Mail::assertSent(AvisoDeConsulta::class, fn ($m) => $m->tipo === 'cancelamento' && $m->para === 'medico');
    }

    public function test_acessibilidade_some_e_carteirinha_usada_fica_sem_numero(): void
    {
        $paciente = $this->ana()->paciente;
        $paciente->acessibilidade()->updateOrCreate([], [
            'possui_deficiencia' => true, 'descricao' => 'Uso cadeira de rodas',
            'consentimento_em' => now(), 'consentimento_versao' => '1.0',
        ]);

        // Uma carteirinha usada numa consulta e outra que nunca foi usada.
        $usada = $paciente->planos()->firstOrFail();
        $consulta = $this->consultaDaAna('realizada');
        $consulta->update(['paciente_plano_id' => $usada->id]);
        $nuncaUsada = PacientePlano::create([
            'paciente_id' => $paciente->id, 'plano_id' => Plano::whereKeyNot($usada->plano_id)->value('id'),
            'numero_carteirinha' => '999000111', 'titular_nome' => 'Ana Beatriz Lima', 'status' => 'ativa',
        ]);

        $this->excluir();

        $this->assertNull($paciente->acessibilidade()->first());
        $this->assertNull(PacientePlano::find($nuncaUsada->id), 'a que nunca foi usada sai');

        $usada->refresh();
        $this->assertSame("excluida-{$usada->id}", $usada->numero_carteirinha);
        $this->assertNull($usada->titular_nome);
        $this->assertSame($usada->id, $consulta->fresh()->paciente_plano_id, 'a consulta continua sabendo o convênio');
    }

    public function test_texto_livre_do_paciente_nas_consultas_some(): void
    {
        $user = $this->ana();
        $paciente = $user->paciente;
        $this->consultaDaAna('realizada')->update(['observacoes' => 'Tenho dor no joelho, meu nome é Ana']);
        $canceladaPorEla = $this->consultaDaAna('cancelada');
        $canceladaPorEla->forceFill(['cancelada_por' => $user->id, 'motivo_cancelamento' => 'Viagem com a família'])->save();
        $this->agendarFutura();

        $this->excluir();

        $this->assertSame(0, $paciente->consultas()->whereNotNull('observacoes')->count());
        $this->assertNull($canceladaPorEla->fresh()->motivo_cancelamento);
        $this->assertSame(
            'O paciente excluiu a conta no FacilMed',
            $paciente->consultas()->latest('id')->first()->motivo_cancelamento,
            'o motivo novo, da exclusão, fica'
        );
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

    public function test_agendamento_que_chega_depois_da_exclusao_e_recusado(): void
    {
        // Simula o pedido que passou pelo middleware um instante ANTES da exclusão
        // (duas abas): sem o middleware, quem barra é a conferência depois da trava.
        $user = $this->ana();
        $this->excluir();
        $antes = Consulta::count();

        $dias = app(CalculadoraDeHorarios::class)->proximosDias(Vinculo::findOrFail(1), 3);
        $data = array_keys($dias)[0];

        $this->withoutMiddleware(GarantirContaAtiva::class)->actingAs($user->fresh())->post('/agendar', [
            'vinculo_id' => 1, 'especialidade_id' => 1, 'data_consulta' => $data,
            'horario' => $dias[$data][0], 'forma_pagamento' => 'particular',
        ])->assertForbidden();

        $this->assertSame($antes, Consulta::count());
    }

    public function test_a_nota_fica_e_o_comentario_sai(): void
    {
        $paciente = $this->ana()->paciente;
        $avaliacao = $this->consultaDaAna('realizada')->avaliacao;
        $avaliacao->update(['comentario' => 'Comentário que vai sumir']);
        $medico = $avaliacao->medico;
        [$media, $total] = [$medico->media_avaliacoes, $medico->total_avaliacoes];

        $this->excluir();

        $this->assertSame(0, Avaliacao::where('paciente_id', $paciente->id)->whereNotNull('comentario')->count());
        $this->assertSame($avaliacao->estrelas, $avaliacao->fresh()->estrelas);
        $medico->refresh();
        $this->assertEquals($media, $medico->media_avaliacoes, 'a nota do médico não muda');
        $this->assertSame($total, $medico->total_avaliacoes);
    }

    public function test_e_mail_antigo_sai_do_registro_de_avisos(): void
    {
        $this->agendarFutura();   // gera a confirmação para ana@facilmed.test
        $this->assertTrue(NotificacaoEnviada::where('destinatario', 'ana@facilmed.test')->exists());

        $this->excluir();

        $this->assertFalse(NotificacaoEnviada::where('destinatario', 'ana@facilmed.test')->exists());
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
        $this->assertNotNull($user->paciente->cpf);
    }

    public function test_conta_excluida_nao_entra_mais(): void
    {
        $user = $this->ana();
        $this->excluir();

        $this->post('/login', ['email' => 'ana@facilmed.test', 'password' => 'facilmed2026'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        // Uma sessão que tenha ficado aberta em outro aparelho cai na próxima página.
        $this->actingAs($user->fresh())->get('/paciente')->assertRedirect(route('login'));
    }

    public function test_mesmo_e_mail_e_cpf_podem_se_cadastrar_de_novo(): void
    {
        $this->excluir();

        $this->post('/cadastro/paciente', [
            'name' => 'Ana Beatriz Lima', 'email' => 'ana@facilmed.test', 'cpf' => '802.301.401-30',
            'password' => 'SenhaForte2026', 'password_confirmation' => 'SenhaForte2026',
        ])->assertSessionHasNoErrors()->assertRedirect(route('paciente.dashboard'));
    }

    public function test_so_paciente_exclui_por_aqui(): void
    {
        $this->comoMedico()->delete('/paciente/perfil', self::CONFIRMA)->assertForbidden();
        $this->assertSame('ativo', User::where('email', 'helena@facilmed.test')->first()->status);
    }

    public function test_admin_nao_reativa_conta_excluida(): void
    {
        $user = $this->ana();
        $this->excluir();

        // Bloquear e depois "desbloquear" colocaria a conta de volta como ativa.
        $this->comoAdmin()->post(route('admin.usuarios.bloquear', $user), ['motivo' => 'Motivo qualquer de teste'])
            ->assertStatus(422);
        $this->assertSame('inativo', $user->fresh()->status);

        $this->comoAdmin()->get('/admin/usuarios/excluidas')->assertOk()
            ->assertSee('Excluída pelo próprio paciente')
            ->assertDontSee('ana@facilmed.test');
        // E não aparece entre as ativas.
        $this->comoAdmin()->get('/admin/usuarios')->assertOk()->assertDontSee('excluida-' . $user->id);
    }

    public function test_dashboard_do_admin_nao_conta_conta_excluida(): void
    {
        $contar = fn () => collect($this->comoAdmin()->get('/admin')->assertOk()->viewData('cartoes'))
            ->firstWhere('rotulo', 'Pacientes cadastrados')['valor'];

        $antes = $contar();
        $this->excluir();

        $this->assertSame((string) ((int) $antes - 1), $contar());
    }

    public function test_perfil_mostra_o_bloco_de_exclusao(): void
    {
        $this->comoPaciente()->get('/paciente/perfil')->assertOk()
            ->assertSee('Excluir minha conta')
            ->assertSee('id="excluir_senha" name="current_password"', false)
            ->assertSee('name="confirmacao"', false);
    }
}
