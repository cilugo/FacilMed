<?php

namespace Tests\Feature;

use App\Models\Avaliacao;
use App\Models\Local;
use App\Models\Medico;
use App\Models\User;
use Tests\TestCase;

/**
 * Avaliação direto no local ou no médico (01/10/2026, documento de
 * modificações): sem consulta, o usuário logado avalia. Uma por usuário
 * em cada local/médico; avaliar de novo edita. Comentário privado.
 *
 * Seed (AvaliacaoSeeder): Ana deu 5 na Vida Plena e Marcos deu 4.
 */
class AvaliacaoTest extends TestCase
{
    private function local(string $nome = 'Aurora - Vila Ema'): Local
    {
        return Local::where('nome', $nome)->firstOrFail();
    }

    public function test_usuario_avalia_local_e_a_media_e_recalculada(): void
    {
        $aurora = $this->local();   // sem avaliação no seed
        $this->assertSame(0, $aurora->total_avaliacoes);

        $this->comoUsuarioFinal()->post("/local/{$aurora->id}/avaliar", ['estrelas' => 4, 'comentario' => 'Fui bem atendida.'])
            ->assertSessionHas('sucesso');

        $aurora->refresh();
        $this->assertSame(1, $aurora->total_avaliacoes);
        $this->assertEquals(4.0, (float) $aurora->media_avaliacoes);

        $this->comoUsuarioFinal('marcos@facilmed.test')->post("/local/{$aurora->id}/avaliar", ['estrelas' => 2]);
        $this->assertEquals(3.0, (float) $aurora->fresh()->media_avaliacoes);
    }

    public function test_avaliar_de_novo_edita_em_vez_de_duplicar(): void
    {
        $vidaPlena = $this->local('Vida Plena - Centro');

        $this->comoUsuarioFinal()->post("/local/{$vidaPlena->id}/avaliar", ['estrelas' => 2, 'comentario' => 'Mudei de ideia.'])
            ->assertSessionHas('sucesso', 'Sua avaliação do local foi atualizada.');

        $daAna = Avaliacao::where('local_id', $vidaPlena->id)->whereHas('usuario.user', fn ($u) => $u->where('email', 'ana@facilmed.test'));
        $this->assertSame(1, $daAna->count());
        $this->assertSame(2, $daAna->first()->estrelas);
        $this->assertEquals(3.0, (float) $vidaPlena->fresh()->media_avaliacoes);   // (2 + 4) / 2
    }

    public function test_usuario_avalia_medico(): void
    {
        $camila = Medico::where('crm', '334455')->firstOrFail();   // Marcos deu 5 no seed

        $this->comoUsuarioFinal()->post("/medico/{$camila->id}/avaliar", ['estrelas' => 3])->assertSessionHas('sucesso');

        $camila->refresh();
        $this->assertSame(2, $camila->total_avaliacoes);
        $this->assertEquals(4.0, (float) $camila->media_avaliacoes);
    }

    public function test_nota_fora_de_1_a_5_e_recusada_com_mensagem(): void
    {
        $aurora = $this->local();

        foreach ([0, 6, 'abc', null] as $nota) {
            $this->comoUsuarioFinal()->post("/local/{$aurora->id}/avaliar", ['estrelas' => $nota])->assertSessionHasErrors('estrelas');
        }
        $this->assertSame(0, Avaliacao::where('local_id', $aurora->id)->count());
    }

    public function test_so_usuario_logado_avalia(): void
    {
        $aurora = $this->local();

        $this->post("/local/{$aurora->id}/avaliar", ['estrelas' => 5])->assertRedirect(route('login'));
        $this->comoClinica()->post("/local/{$aurora->id}/avaliar", ['estrelas' => 5])->assertForbidden();
        $this->comoAdmin()->post('/medico/1/avaliar', ['estrelas' => 1])->assertForbidden();

        $this->assertSame(0, Avaliacao::where('local_id', $aurora->id)->count());
    }

    public function test_nao_avalia_local_fora_do_ar_nem_medico_nao_verificado(): void
    {
        $aurora = $this->local();
        User::where('email', 'contato@aurora.test')->update(['status' => 'bloqueado']);
        $this->comoUsuarioFinal()->post("/local/{$aurora->id}/avaliar", ['estrelas' => 5])->assertNotFound();

        Medico::where('crm', '223344')->update(['status_verificacao' => 'rejeitado']);
        $this->comoUsuarioFinal()->post('/medico/' . Medico::where('crm', '223344')->value('id') . '/avaliar', ['estrelas' => 5])->assertNotFound();
    }

    public function test_formulario_vem_preenchido_com_a_avaliacao_do_proprio_usuario(): void
    {
        $vidaPlena = $this->local('Vida Plena - Centro');

        // A Ana vê o comentário DELA (para editar)...
        $this->comoUsuarioFinal()->get("/local/{$vidaPlena->id}")
            ->assertOk()
            ->assertSee('Sua avaliação')
            ->assertSee('Recepção atenciosa e sala de espera acessível.')
            ->assertSee('Atualizar avaliação');

        // ...e o visitante é convidado a entrar. 05/10: o comentário é público,
        // com o nome encurtado ("Ana L.").
        auth()->logout();
        $this->get("/local/{$vidaPlena->id}")
            ->assertOk()
            ->assertSee('Avalie este local')
            ->assertDontSee('name="estrelas"', false)
            ->assertSee('Recepção atenciosa')
            ->assertSee('Ana L.')
            ->assertDontSee('Ana Beatriz Lima');
    }

    public function test_minhas_avaliacoes_lista_e_so_o_autor_exclui(): void
    {
        $daAna = Avaliacao::whereHas('usuario.user', fn ($u) => $u->where('email', 'ana@facilmed.test'))->firstOrFail();

        $this->comoUsuarioFinal()->get('/usuario/avaliacoes')
            ->assertOk()
            ->assertSee('Vida Plena - Centro')
            ->assertSee('Dra. Helena Navarro');

        // O Marcos não apaga a avaliação da Ana.
        $this->comoUsuarioFinal('marcos@facilmed.test')->delete("/usuario/avaliacoes/{$daAna->id}")->assertForbidden();
        $this->assertNotNull($daAna->fresh());

        $alvo = $daAna->local ?? $daAna->medico;
        $this->comoUsuarioFinal()->delete("/usuario/avaliacoes/{$daAna->id}")->assertSessionHas('sucesso');
        $this->assertNull($daAna->fresh());
        $this->assertSame($alvo->total_avaliacoes - 1, $alvo->fresh()->total_avaliacoes, 'a média é recalculada ao excluir');
    }

    public function test_clinica_le_comentario_das_suas_unidades_e_medicos_mas_nao_de_outras(): void
    {
        // Vida Plena: comentário da Ana na unidade + Dr. Lucas Ferreira atende lá.
        $this->comoClinica()->get('/clinica/avaliacoes')
            ->assertOk()
            ->assertSee('Recepção atenciosa e sala de espera acessível.')
            // Comentário do Santa Clara (outra clínica, e nenhum médico da Vida Plena):
            ->assertDontSee('Estrutura boa, estacionamento cheio.');
    }
}
