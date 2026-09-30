<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 30/09/2026 — exclusão de conta pelo paciente (LGPD, plano do app).
 *
 * A conta é ANONIMIZADA, não apagada (Paciente::excluirConta explica o porquê).
 * Três mudanças no banco para isso:
 *
 * 1. users.excluida_em — quando a pessoa pediu a exclusão. É o registro de que
 *    o pedido foi atendido (e a diferença entre "excluída pelo titular" e uma
 *    conta que só ficou inativa).
 *
 * 2. CHECK: conta excluída fica 'inativo' para sempre. É a trava no próprio
 *    banco (AGENTS.md §3): nem um "desbloquear" do admin, nem um seeder, nem um
 *    UPDATE na mão consegue deixar ativa uma conta que o paciente excluiu.
 *    Mesmo jeito dos CHECKs de 24/09 (travas_e_indices).
 *
 * 3. pacientes.cpf passa a aceitar NULL. Antes era obrigatório, e o CPF é o
 *    dado que mais identifica a pessoa: sem isso, teríamos que guardar um CPF
 *    inventado no lugar. O ->change() NÃO mexe no índice: o UNIQUE continua, e
 *    o MariaDB/MySQL aceitam vários NULL num índice único. Por isso a mesma
 *    pessoa pode se cadastrar de novo com o mesmo CPF depois de excluir.
 *    (No ->change() é preciso repetir tudo o que a coluna tinha: string, 14.)
 */
return new class extends Migration
{
    private const CHECK = 'chk_users_excluida_inativa';

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('excluida_em')->nullable()->after('bloqueado_em');
        });

        DB::statement('ALTER TABLE users ADD CONSTRAINT ' . self::CHECK .
            " CHECK (excluida_em IS NULL OR status = 'inativo')");

        Schema::table('pacientes', function (Blueprint $table) {
            $table->string('cpf', 14)->nullable()->change();
        });
    }

    /**
     * O CPF continua aceitando NULL na volta: se alguém já excluiu a conta, ele
     * está vazio, e voltar para NOT NULL falharia (e o down() não apaga dado).
     */
    public function down(): void
    {
        $existe = DB::selectOne(
            'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = ?',
            [self::CHECK]
        );

        if ($existe) {
            DB::statement('ALTER TABLE users DROP CONSTRAINT ' . self::CHECK);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('excluida_em');
        });
    }
};
