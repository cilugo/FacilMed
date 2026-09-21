<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ferias, feriado, congresso, imprevisto.
     * Horario dentro de um bloqueio nunca e oferecido ao paciente.
     */
    public function up(): void
    {
        Schema::create('bloqueios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medico_id')->constrained()->cascadeOnDelete();

            // Nulo = vale para todos os locais do medico.
            $table->foreignId('vinculo_id')->nullable()->constrained('vinculos')->cascadeOnDelete();

            $table->dateTime('inicio');
            $table->dateTime('fim');
            $table->string('motivo', 255)->nullable();
            $table->timestamps();

            $table->index(['medico_id', 'inicio', 'fim']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloqueios');
    }
};
