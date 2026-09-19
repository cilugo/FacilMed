<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * O `users` padrao do Laravel ja tem name, email e password.
     * Aqui entram so os campos que o FacilMed precisa.
     *
     * CPF e CNPJ NAO ficam aqui de proposito: pessoa tem CPF e
     * clinica tem CNPJ, entao cada documento mora na tabela de
     * perfil correspondente.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('tipo', ['paciente', 'medico', 'clinica', 'admin'])
                  ->default('paciente')->after('email');
            $table->string('telefone', 20)->nullable()->after('tipo');
            $table->enum('status', ['ativo', 'inativo', 'bloqueado'])
                  ->default('ativo')->after('telefone');

            // Bloqueio sem registro de quem e por que e ingovernavel.
            $table->string('motivo_bloqueio', 255)->nullable()->after('status');
            $table->foreignId('bloqueado_por')->nullable()->after('motivo_bloqueio')
                  ->constrained('users')->nullOnDelete();
            $table->timestamp('bloqueado_em')->nullable()->after('bloqueado_por');

            $table->index(['tipo', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['bloqueado_por']);
            $table->dropIndex(['tipo', 'status']);
            $table->dropColumn([
                'tipo', 'telefone', 'status',
                'motivo_bloqueio', 'bloqueado_por', 'bloqueado_em',
            ]);
        });
    }
};
