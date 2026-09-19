<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Quais convenios o medico aceita.
     *
     * Decisao de 18/09/2026: o vinculo e com o MEDICO, nao com o
     * endereco. Simplifica, mas tem consequencia - o paciente pode
     * marcar num endereco onde, na vida real, o plano nao vale.
     * Por isso toda confirmacao de consulta por convenio exibe:
     * "Confirme na recepcao se o seu plano e aceito neste endereco."
     * Esse aviso e obrigatorio (AGENTS.md secao 6).
     *
     * O medico aceita a operadora INTEIRA: aceitando Unimed,
     * aceita todos os planos dela.
     */
    public function up(): void
    {
        Schema::create('convenio_medico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medico_id')->constrained()->cascadeOnDelete();
            $table->foreignId('convenio_id')->constrained()->cascadeOnDelete();

            $table->unique(['medico_id', 'convenio_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convenio_medico');
    }
};
