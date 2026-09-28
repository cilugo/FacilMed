<?php

namespace Tests\Feature;

use App\Models\Local;
use App\Models\Preco;
use App\Models\User;
use App\Models\Vinculo;
use App\Support\Dinheiro;
use Tests\TestCase;

/**
 * 3ª rodada da revisão de 28/09/2026 (README §11, itens 30 em diante).
 * Mesma ideia do RevisaoTest: cada teste reproduz o furo como ele era e
 * confere que agora o sistema faz o certo.
 */
class RevisaoRodada3Test extends TestCase
{
    // -----------------------------------------------------------------
    // Preço digitado com ponto (item 30)
    // -----------------------------------------------------------------

    public function test_valor_digitado_entende_virgula_e_ponto(): void
    {
        $casos = [
            '250'         => '250',
            '250,00'      => '250.00',
            '250,5'       => '250.5',
            'R$ 250,00'   => '250.00',
            '1.250,00'    => '1250.00',
            '1.250'       => '1250',
            '12.500'      => '12500',
            '150.00'      => '150.00',   // antes: 15000
            '150.5'       => '150.5',    // antes: 1505
            ' 99,90 '     => '99.90',
        ];
        foreach ($casos as $digitado => $esperado) {
            $this->assertSame($esperado, Dinheiro::lerDigitado($digitado), "Digitado: \"$digitado\"");
        }

        // Ambíguo ou inválido: melhor recusar do que salvar um valor errado.
        foreach (['', 'abc', '1,250.00', '12,345', '1.25.0', '-10', '150.000,5x'] as $digitado) {
            $this->assertNull(Dinheiro::lerDigitado($digitado), "Digitado: \"$digitado\"");
        }
    }

    public function test_grade_da_clinica_nao_multiplica_preco_com_ponto(): void
    {
        $clinica = User::where('email', 'contato@vidaplena.test')->first()->clinica;
        $v = $clinica->vinculos()->with('medico.especialidades')->first();
        $esp = $v->medico->especialidades->first();
        $valor = fn () => Preco::where('vinculo_id', $v->id)->where('especialidade_id', $esp->id)->value('valor');

        // Antes: "150.00" era salvo como R$ 15.000,00, com a mensagem "salva".
        $this->comoClinica()->post('/clinica/precos', ['precos' => [$v->id => [$esp->id => '150.00']]])
            ->assertSessionHasNoErrors()->assertSessionHas('sucesso');
        $this->assertEquals(150.00, (float) $valor());

        $this->comoClinica()->post('/clinica/precos', ['precos' => [$v->id => [$esp->id => '1.250,00']]])
            ->assertSessionHasNoErrors();
        $this->assertEquals(1250.00, (float) $valor());

        // Ambíguo: recusa e não mexe no valor.
        $this->comoClinica()->post('/clinica/precos', ['precos' => [$v->id => [$esp->id => '1,250.00']]])
            ->assertSessionHasErrors("precos.{$v->id}.{$esp->id}");
        $this->assertEquals(1250.00, (float) $valor());
    }

    public function test_preco_do_consultorio_nao_multiplica_valor_com_ponto(): void
    {
        $this->comoMedico()->post('/medico/locais', [
            'nome' => 'Consultório Dra. Helena', 'cep' => '12245-000', 'endereco' => 'Av. Teste', 'numero' => '100',
            'bairro' => 'Centro', 'cidade' => 'São José dos Campos', 'uf' => 'SP',
        ])->assertSessionHasNoErrors();
        $vinculo = Vinculo::where('local_id', Local::where('nome', 'Consultório Dra. Helena')->value('id'))->firstOrFail();

        $this->comoMedico()->post('/medico/precos', ['vinculo_id' => $vinculo->id, 'especialidade_id' => 2, 'valor' => '150.00'])
            ->assertSessionHasNoErrors();
        $this->assertSame('150.00', Preco::where('vinculo_id', $vinculo->id)->where('especialidade_id', 2)->value('valor'));

        $this->comoMedico()->post('/medico/precos', ['vinculo_id' => $vinculo->id, 'especialidade_id' => 2, 'valor' => '1,250.00'])
            ->assertSessionHasErrors('valor');
        $this->assertSame('150.00', Preco::where('vinculo_id', $vinculo->id)->where('especialidade_id', 2)->value('valor'));
    }
}
