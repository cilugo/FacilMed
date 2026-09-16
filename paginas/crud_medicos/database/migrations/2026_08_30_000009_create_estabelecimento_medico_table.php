<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executa a migration.
     * Tabela pivô: um médico pode atender em vários estabelecimentos,
     * e um estabelecimento pode ter vários médicos.
     */
    public function up(): void
    {
        Schema::create('estabelecimento_medico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medico_id')->constrained('medicos')->cascadeOnDelete();
            $table->foreignId('estabelecimento_id')->constrained('estabelecimentos')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['medico_id', 'estabelecimento_id']);
        });
    }

    /**
     * Reverte a migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('estabelecimento_medico');
    }
};
