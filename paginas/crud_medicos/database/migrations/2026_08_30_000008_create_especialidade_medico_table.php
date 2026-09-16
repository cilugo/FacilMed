<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executa a migration.
     * Tabela pivô: um médico pode ter várias especialidades,
     * e uma especialidade pode ser exercida por vários médicos.
     */
    public function up(): void
    {
        Schema::create('especialidade_medico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medico_id')->constrained('medicos')->cascadeOnDelete();
            $table->foreignId('especialidade_id')->constrained('especialidades')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['medico_id', 'especialidade_id']);
        });
    }

    /**
     * Reverte a migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('especialidade_medico');
    }
};
