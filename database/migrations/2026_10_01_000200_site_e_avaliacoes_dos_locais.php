<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 01/10/2026 (plano novo do grupo): a clínica é a "linha de frente".
 *
 *  - locais.site: endereço do site da clínica/hospital (botão "Visitar o site").
 *  - avaliacoes_locais: qualquer paciente logado avalia o LOCAL (decisão do
 *    Sidney em 01/10), uma vez por local - pode editar ou excluir depois. O
 *    comentário é privado (clínica e admin), como na avaliação do médico.
 *    UNIQUE (paciente_id, local_id) garante "uma por pessoa" no banco; o
 *    CHECK garante a nota de 1 a 5.
 *
 * A nota do local passa a sair DESTA tabela (antes era a média das avaliações
 * das consultas feitas ali). Para nenhum local "perder" a nota - inclusive no
 * site no ar, onde o seeder não roda de novo - a avaliação de consulta que já
 * existe vira avaliação do local daquela consulta (só a nota; o comentário
 * era sobre o médico). Se a pessoa avaliou duas consultas no mesmo local,
 * fica a nota mais recente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locais', function (Blueprint $table) {
            $table->string('site', 255)->nullable()->after('telefone');
        });

        Schema::create('avaliacoes_locais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('local_id')->constrained('locais')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->unsignedTinyInteger('estrelas');
            $table->text('comentario')->nullable();
            $table->timestamps();

            $table->unique(['paciente_id', 'local_id']);
            $table->index(['local_id', 'estrelas']);
        });

        DB::statement('ALTER TABLE avaliacoes_locais ADD CONSTRAINT chk_avaliacoes_locais_estrelas CHECK (estrelas BETWEEN 1 AND 5)');

        // Notas que já existiam (avaliação de consulta) → nota do local.
        $antigas = DB::table('avaliacoes')
            ->join('consultas', 'consultas.id', '=', 'avaliacoes.consulta_id')
            ->join('vinculos', 'vinculos.id', '=', 'consultas.vinculo_id')
            ->orderBy('avaliacoes.created_at')
            ->get(['avaliacoes.paciente_id', 'vinculos.local_id', 'avaliacoes.estrelas', 'avaliacoes.created_at']);

        foreach ($antigas as $a) {
            DB::table('avaliacoes_locais')->updateOrInsert(
                ['paciente_id' => $a->paciente_id, 'local_id' => $a->local_id],
                ['estrelas' => $a->estrelas, 'created_at' => $a->created_at, 'updated_at' => $a->created_at],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('avaliacoes_locais');
        Schema::table('locais', fn (Blueprint $table) => $table->dropColumn('site'));
    }
};
