<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executa a migration.
     * Tabela pivô: um médico pode aceitar vários convênios
     * (ex: Unimed + Amil + Bradesco Saúde), e um convênio pode
     * ser aceito por vários médicos.
     */
    public function up(): void
    {
        Schema::create('convenio_medico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medico_id')->constrained('medicos')->cascadeOnDelete();
            $table->foreignId('convenio_id')->constrained('convenios')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['medico_id', 'convenio_id']);
        });
    }

    /**
     * Reverte a migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('convenio_medico');
    }
};
