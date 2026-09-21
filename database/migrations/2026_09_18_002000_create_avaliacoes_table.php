<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Uma avaliacao por consulta, garantida pelo UNIQUE em
     * consulta_id - no banco, nao na validacao que alguem esquece.
     *
     * Regras (Policy):
     *  - so o paciente daquela consulta avalia;
     *  - so se status = 'realizada';
     *  - o COMENTARIO e privado: visivel apenas para o medico
     *    avaliado, a clinica dele e o admin. O publico ve apenas
     *    a nota em estrelas. Isso nao e coluna, e autorizacao.
     */
    public function up(): void
    {
        Schema::create('avaliacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medico_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('estrelas');
            $table->text('comentario')->nullable();
            $table->timestamps();

            $table->index(['medico_id', 'estrelas']);
        });

        DB::statement("
            ALTER TABLE avaliacoes
            ADD CONSTRAINT chk_avaliacao_estrelas
            CHECK (estrelas BETWEEN 1 AND 5)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('avaliacoes');
    }
};
