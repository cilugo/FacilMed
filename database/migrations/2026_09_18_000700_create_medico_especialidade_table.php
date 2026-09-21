<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * N:N - um medico pode atuar em mais de uma area
     * (ex.: cardiologia e clinica geral).
     */
    public function up(): void
    {
        Schema::create('medico_especialidade', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medico_id')->constrained()->cascadeOnDelete();
            $table->foreignId('especialidade_id')->constrained()->cascadeOnDelete();
            $table->boolean('principal')->default(false);

            $table->unique(['medico_id', 'especialidade_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medico_especialidade');
    }
};
