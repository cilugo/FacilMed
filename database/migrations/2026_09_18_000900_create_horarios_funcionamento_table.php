<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Horario de funcionamento do LUGAR - diferente da
     * disponibilidade do medico, que fica em `disponibilidades`.
     */
    public function up(): void
    {
        Schema::create('horarios_funcionamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('local_id')->constrained('locais')->cascadeOnDelete();
            $table->enum('dia_semana', [
                'domingo', 'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado',
            ]);
            $table->time('abre');
            $table->time('fecha');

            $table->unique(['local_id', 'dia_semana']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_funcionamento');
    }
};
