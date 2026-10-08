<?php

namespace Tests\Feature;

use App\Models\UsuarioPlano;
use App\Models\User;
use App\Models\Vinculo;
use Tests\TestCase;

/**
 * Problemas achados na revisão de 28/09/2026. Cada teste reproduz o furo
 * como ele era e confere que agora o sistema recusa.
 */
class RevisaoTest extends TestCase
{


    public function test_bloqueio_grava_motivo_autor_e_data_e_desbloqueio_limpa(): void
    {
        $admin  = User::where('email', 'admin@facilmed.test')->first();
        $marcos = User::where('email', 'marcos@facilmed.test')->first();

        $this->comoAdmin()->post("/admin/usuarios/{$marcos->id}/bloquear", [
            'motivo' => 'Conta usada para avaliações falsas',
        ])->assertSessionHas('sucesso');

        $marcos->refresh();
        $this->assertSame('bloqueado', $marcos->status);
        $this->assertSame('Conta usada para avaliações falsas', $marcos->motivo_bloqueio);
        $this->assertSame($admin->id, (int) $marcos->bloqueado_por);
        $this->assertNotNull($marcos->bloqueado_em);

        $this->comoAdmin()->post("/admin/usuarios/{$marcos->id}/desbloquear")->assertSessionHas('sucesso');
        $marcos->refresh();
        $this->assertSame('ativo', $marcos->status);
        $this->assertNull($marcos->motivo_bloqueio);
    }

    public function test_mensagens_de_erro_tem_acento(): void
    {
        $this->post('/cadastro/usuario', [
            'name' => 'Teste da Silva', 'email' => 'teste.acento@facilmed.test', 'cpf' => '111.111.111-11',
            'password' => 'senha-longa-123', 'password_confirmation' => 'outra-senha-123',
        ])->assertSessionHasErrors([
            'cpf'      => 'Esse CPF não é válido.',
            'password' => 'As duas senhas não são iguais.',
        ]);
    }

    // -----------------------------------------------------------------
    // 2ª rodada da revisão de 28/09 (README §11, itens 27 a 29)
    // -----------------------------------------------------------------

}
