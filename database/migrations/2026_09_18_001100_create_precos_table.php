<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Preco por VINCULO + ESPECIALIDADE.
     *
     * Precisa dos dois porque:
     *  - a clinica cobra por tabela de precos, e cardiologia nao
     *    custa o mesmo que clinica geral;
     *  - o mesmo medico pode atuar em duas especialidades;
     *  - o mesmo medico pode cobrar diferente em cada endereco.
     *
     * Quem edita: se o local pertence a uma clinica, a clinica.
     * Se e consultorio proprio, o medico. Regra na Policy.
     */
    public function up(): void
    {
        Schema::create('precos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vinculo_id')->constrained('vinculos')->cascadeOnDelete();
            $table->foreignId('especialidade_id')->constrained()->cascadeOnDelete();
            $table->decimal('valor', 10, 2);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['vinculo_id', 'especialidade_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('precos');
    }
};
