<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executa a migration.
     */
    public function up(): void
    {
        Schema::create('enderecos', function (Blueprint $table) {
            $table->id();
            $table->string('cep', 9);
            $table->string('logradouro');
            $table->string('numero', 20)->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro');
            $table->string('cidade');
            $table->string('estado', 2); // UF, ex: SP, RJ, MG

            // Relacionamento polimórfico: um endereço pode pertencer a um
            // Estabelecimento, a um Médico (local de atendimento) ou a
            // qualquer outra entidade que precise de endereço no futuro.
            $table->nullableMorphs('enderecavel');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverte a migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('enderecos');
    }
};
