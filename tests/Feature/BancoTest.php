<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Tests\TestCase;

/**
 * Travas do PRÓPRIO BANCO, independentes do PHP. Se alguém gravar direto
 * (tinker, seeder, bug), o banco recusa.
 */
class BancoTest extends TestCase
{
    private function espera23000(callable $fn, string $msg): void
    {
        try {
            $fn();
            $this->fail("O banco aceitou: {$msg}");
        } catch (QueryException $e) {
            $this->assertSame('23000', $e->getCode(), $msg);
        }
    }

    public function test_checks_do_banco(): void
    {
        // 05/10/2026: faixa de preço da unidade só de 1 a 4.
        $this->espera23000(fn () => \App\Models\Local::whereKey(1)->update(['faixa_preco' => 5]), 'faixa 5');
        // 01/10/2026: avaliação de local OU médico (CHECKs da migration de reorganização).
        $this->espera23000(fn () => \App\Models\Avaliacao::create(['usuario_id' => 2, 'local_id' => 3, 'estrelas' => 6]), 'nota 6');
        $this->espera23000(fn () => \App\Models\Avaliacao::create(['usuario_id' => 2, 'estrelas' => 4]), 'avaliação sem alvo');
        $this->espera23000(fn () => \App\Models\Avaliacao::create(['usuario_id' => 2, 'local_id' => 3, 'medico_id' => 4, 'estrelas' => 4]), 'avaliação com dois alvos');
        // Uma por usuário em cada local (UNIQUE): a Ana já avaliou a Vida Plena no seed.
        $this->espera23000(fn () => \App\Models\Avaliacao::create(['usuario_id' => 1, 'local_id' => \App\Models\Local::where('nome', 'Vida Plena - Centro')->value('id'), 'estrelas' => 2]), 'segunda avaliação do mesmo local');
        // O banco não aceita mais conta de médico (users.tipo sem 'medico').
        // Valor fora do ENUM dá "Data truncated" (outro código), não 23000.
        $this->expectException(QueryException::class);
        \Illuminate\Support\Facades\DB::table('users')->insert(['name' => 'X', 'email' => 'x@facilmed.test', 'password' => 'x', 'tipo' => 'medico']);
    }
}
