<?php

namespace Tests\Feature;

use App\Models\Especialidade;
use App\Models\Medico;
use App\Models\User;
use App\Models\Vinculo;
use Tests\TestCase;

class AdminTest extends TestCase
{

    public function test_nao_bloqueia_admin_e_desbloqueia(): void
    {
        $admin = User::where('email', 'admin@facilmed.test')->first();
        $this->comoAdmin()->post("/admin/usuarios/{$admin->id}/bloquear", ['motivo' => 'Teste de bloqueio do admin'])->assertForbidden();

        $marcos = User::where('email', 'marcos@facilmed.test')->first();
        $this->comoAdmin()->post("/admin/usuarios/{$marcos->id}/desbloquear")->assertStatus(422);
        $this->comoAdmin()->post("/admin/usuarios/{$marcos->id}/bloquear", ['motivo' => 'Avaliações falsas repetidas'])->assertSessionHas('sucesso');
        $this->comoAdmin()->post("/admin/usuarios/{$marcos->id}/desbloquear")->assertSessionHas('sucesso');
        $this->assertSame('ativo', $marcos->fresh()->status);
    }

    public function test_especialidade_editar_e_desativar(): void
    {
        $orto = Especialidade::where('slug', 'ortopedia')->first();
        $this->comoAdmin()->put("/admin/especialidades/{$orto->slug}", ['nome' => 'Ortopedia e Traumatologia', 'destaque' => 0, 'ativo' => 0])
            ->assertSessionHasNoErrors();
        $orto->refresh();
        $this->assertSame('ortopedia', $orto->slug, 'slug não muda');
        $this->assertFalse($orto->ativo);

        $this->comoAdmin()->put("/admin/especialidades/{$orto->slug}", ['nome' => 'Cardiologia'])->assertSessionHasErrors('nome');
    }

}
