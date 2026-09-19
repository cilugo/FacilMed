<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * DADO SENSIVEL DE SAUDE - LGPD art. 11.
     *
     * Fica em tabela SEPARADA de `pacientes` de proposito:
     *  - permite restringir o acesso numa Policy unica;
     *  - permite auditar quem leu;
     *  - permite atender pedido de exclusao do titular apagando
     *    uma linha, sem mexer no cadastro da pessoa.
     *
     * Nunca aparece em listagem, busca ou exportacao. So o
     * profissional que TEM consulta agendada com o paciente le.
     * Nao existe upload de laudo: ver AGENTS.md secao 2.
     */
    public function up(): void
    {
        Schema::create('paciente_acessibilidade', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('possui_deficiencia')->default(false);
            $table->text('descricao')->nullable();

            // Consentimento explicito, com data e versao do texto aceito.
            $table->timestamp('consentimento_em')->nullable();
            $table->string('consentimento_versao', 20)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paciente_acessibilidade');
    }
};
