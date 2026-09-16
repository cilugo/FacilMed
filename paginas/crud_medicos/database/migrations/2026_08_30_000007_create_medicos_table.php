<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executa a migration.
     *
     * Assim como 'pacientes', a tabela 'medicos' é um perfil ligado a
     * 'users' — nome, e-mail, CPF, telefone, senha e data de nascimento
     * já ficam centralizados lá.
     */
    public function up(): void
    {
        Schema::create('medicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('crm', 20);
            $table->string('uf_crm', 2);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Um mesmo CRM só pode existir uma vez por UF
            $table->unique(['crm', 'uf_crm']);
        });
    }

    /**
     * Reverte a migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicos');
    }
};
