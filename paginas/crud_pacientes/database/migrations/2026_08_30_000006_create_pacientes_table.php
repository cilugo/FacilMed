<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executa a migration.
     *
     * A tabela 'pacientes' funciona como um perfil ligado a 'users'
     * (dados de autenticação, nome, CPF, e-mail, telefone e data de
     * nascimento já ficam em 'users' — aqui guardamos apenas o que é
     * específico do paciente dentro do FacilMed).
     */
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('numero_carteirinha')->nullable(); // carteirinha do convênio, se houver
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverte a migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
