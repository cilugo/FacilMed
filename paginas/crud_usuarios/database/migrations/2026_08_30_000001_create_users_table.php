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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('cpf', 14)->unique();
            $table->string('telefone', 20)->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('password');

            // Define o tipo de usuário dentro do sistema FacilMed
            $table->enum('tipo_usuario', ['paciente', 'medico', 'administrador'])
                  ->default('paciente');

            // Permite inativação lógica em vez de exclusão física
            // (preserva o histórico de consultas, conforme discutido no TCC)
            $table->boolean('ativo')->default(true);

            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes(); // exclusão lógica (soft delete)
        });
    }

    /**
     * Reverte a migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
