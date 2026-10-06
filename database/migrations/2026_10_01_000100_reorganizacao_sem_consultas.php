<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * REORGANIZAÇÃO DE 01/10/2026 (documento "Modificações - 01/10").
 *
 * O FacilMed deixa de AGENDAR consultas: vira um guia de clínicas e
 * hospitais perto do paciente, com médicos, convênios, faixa de preço e
 * avaliações. Esta migration faz no banco o que o documento pediu:
 *
 *  1. Sai tudo que só existia por causa da consulta: consultas, lembretes
 *     por e-mail (notificacoes_enviadas), horários e ausências do médico
 *     (disponibilidades, bloqueios), feriados e a acessibilidade do
 *     paciente (dado de saúde que só era mostrado ao médico DA consulta;
 *     sem consulta ficou sem finalidade, e a LGPD manda não guardar dado
 *     sem finalidade).
 *  2. O médico deixa de ter CONTA: vira um perfil cadastrado pela clínica.
 *     O nome passa de users.name para medicos.nome e as contas de médico
 *     somem. users.tipo deixa de aceitar 'medico' (o banco garante).
 *  3. Consultório próprio de médico autônomo some junto (não tem mais quem
 *     o administre): todo local passa a ser de uma clínica/hospital.
 *  4. Avaliação deixa de depender de consulta: o paciente avalia o LOCAL
 *     ou o MÉDICO direto, uma vez cada (pode editar). A nota média de cada
 *     um fica guardada no próprio local/médico.
 *  5. Foto de perfil para paciente, clínica e admin (users.foto).
 *
 * Por que uma migration nova e não editar as antigas: AGENTS.md §4 —
 * migration já aplicada não se edita.
 *
 * ⚠ Rodar isto APAGA as consultas, horários e avaliações antigas do banco
 * local. Os dados são fictícios; o caminho recomendado é
 * `php artisan migrate:fresh --seed`.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- 1. Tudo que existia por causa da consulta ----
        // Ordem: quem aponta para consultas sai antes dela.
        Schema::dropIfExists('notificacoes_enviadas');
        Schema::dropIfExists('avaliacoes');
        Schema::dropIfExists('consultas');
        Schema::dropIfExists('bloqueios');
        Schema::dropIfExists('disponibilidades');
        Schema::dropIfExists('feriado_local');
        Schema::dropIfExists('feriados');
        Schema::dropIfExists('paciente_acessibilidade');

        // ---- 2. Médico sem conta ----
        Schema::table('medicos', function (Blueprint $table) {
            $table->string('nome', 150)->default('')->after('id');
        });

        DB::statement('UPDATE medicos m JOIN users u ON u.id = m.user_id SET m.nome = u.name');

        Schema::table('medicos', function (Blueprint $table) {
            // A FK sai ANTES de apagar as contas: ela é cascadeOnDelete e
            // levaria os médicos junto.
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);
            $table->dropColumn(['user_id', 'senha_temporaria']);
            $table->string('nome', 150)->default(null)->change();
        });

        DB::table('users')->where('tipo', 'medico')->delete();

        DB::statement("ALTER TABLE users MODIFY tipo ENUM('paciente', 'clinica', 'admin') NOT NULL DEFAULT 'paciente'");

        // ---- 3. Todo local é de uma clínica/hospital ----
        DB::table('locais')->whereNull('clinica_id')->delete();
        DB::statement('ALTER TABLE locais DROP CONSTRAINT chk_local_um_dono');

        Schema::table('locais', function (Blueprint $table) {
            $table->dropForeign(['medico_id']);
            $table->dropColumn('medico_id');
        });

        // ---- 4. Avaliação do local ou do médico, sem consulta ----
        Schema::table('locais', function (Blueprint $table) {
            $table->decimal('media_avaliacoes', 3, 2)->default(0)->after('longitude');
            $table->unsignedInteger('total_avaliacoes')->default(0)->after('media_avaliacoes');
        });

        DB::table('medicos')->update(['media_avaliacoes' => 0, 'total_avaliacoes' => 0]);

        Schema::create('avaliacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('local_id')->nullable()->constrained('locais')->cascadeOnDelete();
            $table->foreignId('medico_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('estrelas');
            $table->text('comentario')->nullable();
            $table->timestamps();

            // Uma avaliação por paciente em cada local e em cada médico.
            // (No MariaDB, NULL não conflita com NULL no UNIQUE: a avaliação
            // de local, com medico_id NULL, não trava a de médico.)
            $table->unique(['paciente_id', 'local_id'], 'uq_avaliacao_paciente_local');
            $table->unique(['paciente_id', 'medico_id'], 'uq_avaliacao_paciente_medico');
        });

        DB::statement('ALTER TABLE avaliacoes ADD CONSTRAINT chk_avaliacao_estrelas CHECK (estrelas BETWEEN 1 AND 5)');
        // Avalia UM alvo: o local OU o médico, nunca os dois nem nenhum.
        DB::statement('ALTER TABLE avaliacoes ADD CONSTRAINT chk_avaliacao_um_alvo CHECK (
            (local_id IS NOT NULL AND medico_id IS NULL) OR (local_id IS NULL AND medico_id IS NOT NULL)
        )');

        // ---- 5. Foto de perfil ----
        Schema::table('users', function (Blueprint $table) {
            $table->string('foto')->nullable()->after('telefone');
        });
    }

    /**
     * Sem volta automática: as tabelas de consulta tinham regras (coluna
     * virtual, índices, CHECKs) que só fazem sentido com o agendamento.
     * Para voltar ao estado anterior, use o Git (commit anterior) e
     * `php artisan migrate:fresh --seed`.
     */
    public function down(): void
    {
        throw new RuntimeException(
            'A reorganização de 01/10 não tem volta automática. Volte o código pelo Git e rode migrate:fresh --seed.'
        );
    }
};
