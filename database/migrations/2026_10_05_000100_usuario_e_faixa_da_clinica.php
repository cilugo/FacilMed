<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 05/10/2026 — notas do grupo sobre a versão de 01/10 (PDF "Notas sobre FacilMed").
 *
 * 1. "Paciente" vira "usuário" também no banco (decisão do grupo): o site
 *    não marca consulta, então quem usa não é paciente do FacilMed.
 *      pacientes        → usuarios
 *      paciente_planos  → usuario_planos
 *      *.paciente_id    → *.usuario_id   (avaliacoes, usuario_planos)
 *      users.tipo 'paciente' → 'usuario'
 *    ATENÇÃO aos nomes: `users` é a CONTA de acesso (de todo mundo);
 *    `usuarios` é o PERFIL da pessoa que busca clínicas (CPF, nascimento...).
 *    Os nomes antigos de índices e FKs (ex.: uq_avaliacao_paciente_local)
 *    ficam como estão: renomear índice não muda nada e o MariaDB do XAMPP
 *    (10.4) não tem RENAME INDEX.
 *
 * 2. A "Tabela de preços" sai (decisão do grupo): a CLÍNICA escolhe a faixa
 *    de preço ($ a $$$$) de cada UNIDADE. Nova coluna locais.faixa_preco
 *    (1 a 4, ou NULL = não informada). A faixa inicial de cada unidade sai
 *    da média dos preços que já existiam, e a tabela precos é apagada.
 *    Especialidade oferecida num local passa a ser a especialidade do médico.
 *
 * Sem `down`, como a de 01/10: para voltar, Git + migrate:fresh --seed.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- 2. Faixa de preço por unidade (antes de apagar precos) ----
        Schema::table('locais', function (Blueprint $table) {
            $table->unsignedTinyInteger('faixa_preco')->nullable()->after('total_avaliacoes');
        });
        DB::statement('ALTER TABLE locais ADD CONSTRAINT chk_local_faixa CHECK (faixa_preco IS NULL OR faixa_preco BETWEEN 1 AND 4)');

        // Mesmos limites de App\Support\FaixaDePreco::LIMITES (200 / 350 / 500).
        DB::statement("
            UPDATE locais l
            JOIN (
                SELECT v.local_id, AVG(p.valor) AS media
                FROM precos p JOIN vinculos v ON v.id = p.vinculo_id
                WHERE p.ativo = 1
                GROUP BY v.local_id
            ) m ON m.local_id = l.id
            SET l.faixa_preco = CASE
                WHEN m.media <= 200 THEN 1
                WHEN m.media <= 350 THEN 2
                WHEN m.media <= 500 THEN 3
                ELSE 4 END
        ");

        Schema::dropIfExists('precos');

        // ---- 1. Paciente → usuário ----
        DB::statement("ALTER TABLE users MODIFY tipo ENUM('paciente', 'usuario', 'clinica', 'admin') NOT NULL DEFAULT 'usuario'");
        DB::table('users')->where('tipo', 'paciente')->update(['tipo' => 'usuario']);
        DB::statement("ALTER TABLE users MODIFY tipo ENUM('usuario', 'clinica', 'admin') NOT NULL DEFAULT 'usuario'");

        Schema::rename('pacientes', 'usuarios');
        Schema::rename('paciente_planos', 'usuario_planos');

        Schema::table('usuario_planos', function (Blueprint $table) {
            $table->renameColumn('paciente_id', 'usuario_id');
        });
        Schema::table('avaliacoes', function (Blueprint $table) {
            $table->renameColumn('paciente_id', 'usuario_id');
        });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'A migração de 05/10 não tem volta automática. Volte o código pelo Git e rode migrate:fresh --seed.'
        );
    }
};
