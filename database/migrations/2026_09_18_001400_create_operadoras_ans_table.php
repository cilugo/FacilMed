<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Importada do CSV de dados abertos da ANS (operadoras ativas).
     * E a UNICA validacao externa real deste projeto: garante que
     * nao existe convenio inventado no sistema.
     *
     * Populada por comando Artisan, nunca digitada a mao.
     */
    public function up(): void
    {
        Schema::create('operadoras_ans', function (Blueprint $table) {
            $table->id();
            $table->string('registro_ans', 20)->unique();
            $table->string('cnpj', 18)->nullable();
            $table->string('razao_social', 200);
            $table->string('nome_fantasia', 200)->nullable();
            $table->string('modalidade', 100)->nullable();
            $table->char('uf', 2)->nullable();
            $table->timestamps();

            $table->index('razao_social');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operadoras_ans');
    }
};
