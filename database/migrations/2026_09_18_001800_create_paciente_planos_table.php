<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * A carteirinha do paciente.
     *
     * NAO existe validacao automatica: nao ha API da ANS nem
     * integracao TISS. O paciente pode emitir o comprovante
     * COMPROVA no portal da ANS (com login gov.br proprio) e
     * informar o codigo de controle aqui; a equipe valida esse
     * codigo no site da ANS e muda o status para 'ativa'.
     *
     * Guardamos SO o codigo e a data - nunca o PDF do comprovante,
     * que traz CPF, nome da mae e caracteristicas do plano.
     * Minimizacao de dados (LGPD).
     */
    public function up(): void
    {
        Schema::create('paciente_planos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plano_id')->constrained()->cascadeOnDelete();
            $table->string('numero_carteirinha', 40);
            $table->date('validade')->nullable();
            $table->string('titular_nome', 150)->nullable();

            $table->string('codigo_comprova_ans', 8)->nullable();
            $table->date('comprova_emitido_em')->nullable();

            $table->enum('status', ['pendente', 'ativa', 'recusada'])->default('pendente');
            $table->string('motivo_recusa', 255)->nullable();
            $table->foreignId('conferido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('conferido_em')->nullable();

            $table->timestamps();

            $table->unique(['plano_id', 'numero_carteirinha']);
            $table->index(['paciente_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paciente_planos');
    }
};
