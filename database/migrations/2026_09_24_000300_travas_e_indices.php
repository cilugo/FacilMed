<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Travas no banco + índices das telas novas (24/09/2026).
 *
 * AGENTS.md §6: "toda regra precisa estar numa constraint, Policy ou
 * FormRequest — não só na intenção de quem escreveu a tela". Os
 * FormRequests já validam isto; os CHECKs garantem que nem um seeder,
 * nem o tinker, nem um bug futuro gravem dado impossível.
 *
 * MariaDB 10.2+ (XAMPP 8.2 traz a 10.4) e MySQL 8.0.16+ respeitam CHECK.
 * Não edita migration antiga (AGENTS.md §7): tudo aqui é novo.
 */
return new class extends Migration
{
    private const CHECKS = [
        'precos'                 => ['chk_preco_valor',        'valor >= 0'],
        'disponibilidades'       => ['chk_disp_horario',       'hora_fim > hora_inicio AND duracao_consulta_minutos > 0'],
        'bloqueios'              => ['chk_bloqueio_periodo',   'fim > inicio'],
        'horarios_funcionamento' => ['chk_funcionamento',      'fecha > abre'],
        'consultas'              => ['chk_consulta_valor',     'valor >= 0 AND duracao_minutos > 0'],
    ];

    public function up(): void
    {
        foreach (self::CHECKS as $tabela => [$nome, $regra]) {
            DB::statement("ALTER TABLE {$tabela} ADD CONSTRAINT {$nome} CHECK ({$regra})");
        }

        Schema::table('consultas', function (Blueprint $table) {
            // Painel do admin, lembretes e contagens por situação/período.
            $table->index(['status', 'data_consulta'], 'idx_consultas_status_data');
            // (Sem índice novo começando por vinculo_id: o MariaDB passa a usá-lo
            // na chave estrangeira e depois não deixa apagar no down.)
        });

        Schema::table('disponibilidades', function (Blueprint $table) {
            // Checagem de choque de horário entre lugares do mesmo médico.
            $table->index(['dia_semana', 'ativo'], 'idx_disp_dia_ativo');
        });
    }

    public function down(): void
    {
        // Cada passo só roda se o item existir: um down interrompido no meio
        // pode ser rodado de novo sem erro.
        $indices = fn (string $tabela) => collect(DB::select("SHOW INDEX FROM {$tabela}"))->pluck('Key_name')->all();

        if (in_array('idx_disp_dia_ativo', $indices('disponibilidades'), true)) {
            Schema::table('disponibilidades', fn (Blueprint $t) => $t->dropIndex('idx_disp_dia_ativo'));
        }
        foreach (['idx_consultas_status_data'] as $idx) {
            if (in_array($idx, $indices('consultas'), true)) {
                Schema::table('consultas', fn (Blueprint $t) => $t->dropIndex($idx));
            }
        }

        $existentes = collect(DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_TYPE = ?', ['CHECK']
        ))->pluck('CONSTRAINT_NAME')->all();

        foreach (self::CHECKS as $tabela => [$nome]) {
            if (in_array($nome, $existentes, true)) {
                DB::statement("ALTER TABLE {$tabela} DROP CONSTRAINT {$nome}");
            }
        }
    }
};
