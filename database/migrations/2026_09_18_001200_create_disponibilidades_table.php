<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Bloco recorrente semanal, preso ao VINCULO (medico + local).
     *
     * O horario de almoco NAO tem campo proprio: sao dois blocos
     * no mesmo dia (08:00-12:00 e 14:00-18:00) e o buraco entre
     * eles e o almoco. Menos uma tabela para manter.
     */
    public function up(): void
    {
        Schema::create('disponibilidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vinculo_id')->constrained('vinculos')->cascadeOnDelete();
            $table->enum('dia_semana', [
                'domingo', 'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado',
            ]);
            $table->time('hora_inicio');
            $table->time('hora_fim');
            $table->unsignedSmallInteger('duracao_consulta_minutos')->default(30);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['vinculo_id', 'dia_semana']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disponibilidades');
    }
};
