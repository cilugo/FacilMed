<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * O UNIQUE (consulta_id, tipo) e o coracao desta tabela: e o que
     * impede o mesmo lembrete sair varias vezes quando o scheduler
     * roda de hora em hora, ou quando alguem reinicia o servico.
     *
     * Regra: nenhum e-mail sai sem gravar aqui.
     *
     * CUIDADO em teste: depois de disparar e-mail de verdade, limpe
     * as linhas de teste - senao o lembrete real daquela consulta
     * nunca sai, porque o UNIQUE bloqueia.
     */
    public function up(): void
    {
        Schema::create('notificacoes_enviadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')->constrained()->cascadeOnDelete();
            $table->enum('tipo', [
                'confirmacao',
                'lembrete_24h',
                'lembrete_dia',
                'cancelamento',
                'remarcacao',
            ]);
            $table->string('destinatario', 150);
            $table->timestamp('enviada_em')->nullable();
            $table->boolean('sucesso')->default(false);
            $table->text('erro')->nullable();
            $table->timestamps();

            $table->unique(['consulta_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacoes_enviadas');
    }
};
