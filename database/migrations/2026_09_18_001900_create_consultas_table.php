<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained()->restrictOnDelete();
            $table->foreignId('medico_id')->constrained()->restrictOnDelete();
            $table->foreignId('vinculo_id')->constrained('vinculos')->restrictOnDelete();
            $table->foreignId('especialidade_id')->constrained()->restrictOnDelete();

            $table->date('data_consulta');
            $table->time('horario');
            $table->unsignedSmallInteger('duracao_minutos')->default(30);

            $table->enum('forma_pagamento', ['particular', 'convenio'])->default('particular');
            $table->foreignId('paciente_plano_id')->nullable()
                  ->constrained('paciente_planos')->nullOnDelete();
            $table->decimal('valor', 10, 2)->default(0);

            /**
             * 'nao_compareceu' existe para separar falta de consulta
             * realizada: sem ele, quem faltou consegue avaliar o medico
             * e as metricas do painel ficam erradas.
             */
            $table->enum('status', ['agendada', 'realizada', 'cancelada', 'nao_compareceu'])
                  ->default('agendada');

            // Registra se o paciente escolheu o medico ou se a clinica
            // alocou a partir da especialidade.
            $table->enum('origem', ['medico', 'clinica'])->default('medico');

            $table->text('observacoes')->nullable();

            $table->foreignId('cancelada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelada_em')->nullable();
            $table->string('motivo_cancelamento', 255)->nullable();

            // Cancelamento abaixo de 24h e PERMITIDO, mas registrado.
            // Bloquear nao faz a pessoa comparecer - faz ela faltar.
            $table->boolean('cancelamento_tardio')->default(false);

            $table->timestamps();

            $table->index(['medico_id', 'data_consulta']);
            $table->index(['paciente_id', 'status']);
        });

        /**
         * NAO REMOVER.
         *
         * Coluna virtual + indice unico: e o que impede duas pessoas
         * marcarem o mesmo horario quando as duas requisicoes chegam
         * juntas. Um SELECT de checagem antes do INSERT nao resolve -
         * as duas passam na checagem.
         *
         * A coluna vira NULL quando a consulta e cancelada, e o MySQL
         * nao considera NULLs duplicados entre si: o horario volta a
         * ficar livre para reagendamento.
         *
         * Vai como DB::statement porque o Blueprint nao gera coluna
         * virtual com CASE.
         */
        DB::statement("
            ALTER TABLE consultas
            ADD COLUMN horario_ativo TIME
                GENERATED ALWAYS AS (
                    CASE WHEN status <> 'cancelada' THEN horario ELSE NULL END
                ) VIRTUAL,
            ADD UNIQUE KEY uq_consulta_horario_ativo (medico_id, data_consulta, horario_ativo)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('consultas');
    }
};
