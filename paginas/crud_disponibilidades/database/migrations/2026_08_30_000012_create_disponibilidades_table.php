<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executa a migration.
     *
     * Um registro de disponibilidade representa um período em que o médico
     * pode receber agendamentos. Pode ser recorrente (dia_semana) ou
     * pontual (data específica) — por exemplo, para liberar um dia extra
     * ou uma exceção na agenda.
     */
    public function up(): void
    {
        Schema::create('disponibilidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medico_id')->constrained('medicos')->cascadeOnDelete();
            $table->foreignId('estabelecimento_id')->nullable()->constrained('estabelecimentos')->nullOnDelete();

            // Recorrência semanal (ex: toda segunda) OU data específica — um dos dois.
            $table->enum('dia_semana', ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado', 'domingo'])->nullable();
            $table->date('data')->nullable();

            $table->time('hora_inicio');
            $table->time('hora_fim');
            $table->unsignedSmallInteger('duracao_consulta_minutos')->default(30);

            $table->enum('tipo_atendimento', ['particular', 'convenio', 'sus'])->default('particular');
            $table->foreignId('convenio_id')->nullable()->constrained('convenios')->nullOnDelete();

            // Permite bloquear um período específico (ex: férias, licença),
            // sem precisar excluir a disponibilidade recorrente.
            $table->boolean('bloqueado')->default(false);
            $table->string('motivo_bloqueio')->nullable();

            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverte a migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('disponibilidades');
    }
};
