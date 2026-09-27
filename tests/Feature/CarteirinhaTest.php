<?php

namespace Tests\Feature;

use App\Models\PacientePlano;
use App\Models\Plano;
use Tests\TestCase;

class CarteirinhaTest extends TestCase
{
    private function enviar(string $email, string $plano, string $numero)
    {
        return $this->comoPaciente($email)->post('/paciente/planos', [
            'plano_id' => Plano::where('nome', $plano)->value('id'),
            'numero_carteirinha' => $numero,
        ]);
    }

    public function test_carteirinha_valida_entra_ativa_com_a_validade_da_base(): void
    {
        $this->enviar('marcos@facilmed.test', 'Bem Viver Individual', '300000000002')->assertSessionHasNoErrors();

        $pp = PacientePlano::where('numero_carteirinha', '300000000002')->firstOrFail();
        $this->assertSame('ativa', $pp->status);
        $this->assertNotNull($pp->validade);
    }

    public function test_carteirinhas_recusadas_com_o_motivo(): void
    {
        $casos = [
            ['marcos@facilmed.test', 'Horizonte Essencial', '200000000003', 'vencida'],
            ['ana@facilmed.test', 'Horizonte Família', '200000000004', 'cancelada'],
            ['marcos@facilmed.test', 'SpSaúde Individual', '100000000005', 'outra pessoa'],
            ['marcos@facilmed.test', 'SpSaúde Família', '100000000001', 'outra pessoa'],
        ];

        foreach ($casos as [$email, $plano, $numero, $motivo]) {
            $this->enviar($email, $plano, $numero)->assertSessionHasErrors();
            $this->assertStringContainsString($motivo, collect(session('errors')->all())->join(' '), "$plano / $numero");
            $this->assertFalse(PacientePlano::where('numero_carteirinha', $numero)->where('status', 'ativa')
                ->whereHas('paciente.user', fn ($q) => $q->where('email', $email))->exists());
        }
    }
}
