<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Convenio aceito pela plataforma, SEMPRE ancorado numa
     * operadora real da ANS. operadora_ans_id nao e nullable
     * de proposito: e o que impede convenio inventado.
     */
    public function up(): void
    {
        Schema::create('convenios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operadora_ans_id')->constrained('operadoras_ans')->cascadeOnDelete();
            $table->string('nome', 150);
            $table->text('descricao')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique('operadora_ans_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convenios');
    }
};
