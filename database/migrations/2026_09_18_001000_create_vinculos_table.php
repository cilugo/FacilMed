<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * TABELA CENTRAL DO SISTEMA.
     *
     * Liga um medico a um local de atendimento. Tudo que depende de
     * "onde o medico atende" pendura aqui: preco, disponibilidade e,
     * por consequencia, a consulta.
     *
     * Sem ela, o sistema nao sabe em qual endereco o medico esta
     * as 14h de terca - e aceita agendar em lugar onde ele nunca atendeu.
     */
    public function up(): void
    {
        Schema::create('vinculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medico_id')->constrained()->cascadeOnDelete();
            $table->foreignId('local_id')->constrained('locais')->cascadeOnDelete();
            $table->boolean('aceita_particular')->default(true);
            $table->boolean('aceita_convenio')->default(false);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['medico_id', 'local_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vinculos');
    }
};
