<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CONVÊNIOS E PLANOS 100% FICTÍCIOS — decisão do grupo em 24/09/2026.
 *
 * Antes: todo convênio era obrigado a apontar para uma operadora real
 * importada do CSV da ANS (operadora_ans_id NOT NULL). Na prática o CSV
 * nunca foi baixado (database/data/ não existe), então o ConvenioSeeder
 * pulava e o sistema ficava SEM NENHUM convênio.
 *
 * Agora:
 *   - operadora_ans_id fica OPCIONAL. A tabela operadoras_ans continua
 *     existindo (não apagamos nada), só deixa de ser obrigatória.
 *   - Se um dia apagarem uma operadora, o convênio NÃO some junto
 *     (antes era cascadeOnDelete, que levaria planos e carteirinhas
 *     dos pacientes em cascata). Passa a ser nullOnDelete.
 *   - Convênio ganha dados de contato (CNPJ, telefone, e-mail) e nome
 *     único — dois "SpSaúde" confundiriam o paciente.
 *   - Plano ganha `tipo` (individual/familiar/empresarial) e
 *     `abrangencia`, que é como o paciente reconhece o próprio plano
 *     ("SpSaúde Família", "SpSaúde Empresarial").
 *
 * POR QUE UMA MIGRATION NOVA, e não editar a 001500/001600:
 * migration que já rodou na máquina de alguém não se edita (AGENTS.md).
 * Esta aqui só ALTERA as tabelas, então funciona tanto para quem já
 * rodou `php artisan migrate` quanto para quem vai rodar pela primeira vez.
 *
 * Requer Laravel 11+ (o ->change() funciona sem o pacote doctrine/dbal).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('convenios', function (Blueprint $table) {
            // 1) Solta a FK antiga (cascadeOnDelete) para poder mudar a coluna.
            $table->dropForeign(['operadora_ans_id']);
        });

        Schema::table('convenios', function (Blueprint $table) {
            // 2) Coluna passa a aceitar NULL. O índice UNIQUE que já existe
            //    continua valendo: o MySQL permite vários NULLs num UNIQUE.
            $table->unsignedBigInteger('operadora_ans_id')->nullable()->change();

            // 3) FK de volta, agora sem apagar em cascata.
            $table->foreign('operadora_ans_id')
                  ->references('id')->on('operadoras_ans')
                  ->nullOnDelete();

            // 4) Dados de contato do convênio fictício.
            $table->string('cnpj', 18)->nullable()->unique()->after('nome');
            $table->string('telefone', 20)->nullable()->after('cnpj');
            $table->string('email', 150)->nullable()->after('telefone');

            $table->unique('nome');
        });

        Schema::table('planos', function (Blueprint $table) {
            $table->enum('tipo', ['individual', 'familiar', 'empresarial'])
                  ->default('individual')->after('nome');
            $table->enum('abrangencia', ['municipal', 'estadual', 'nacional'])
                  ->default('estadual')->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('planos', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'abrangencia']);
        });

        Schema::table('convenios', function (Blueprint $table) {
            $table->dropUnique(['nome']);
            $table->dropUnique(['cnpj']);
            $table->dropColumn(['cnpj', 'telefone', 'email']);
            $table->dropForeign(['operadora_ans_id']);
        });

        // ATENÇÃO: o down só funciona se TODOS os convênios tiverem
        // operadora_ans_id preenchido. Com convênios fictícios (NULL),
        // o MySQL recusa voltar a coluna para NOT NULL — de propósito,
        // para não apagar dado sem ninguém perceber.
        Schema::table('convenios', function (Blueprint $table) {
            $table->unsignedBigInteger('operadora_ans_id')->nullable(false)->change();
            $table->foreign('operadora_ans_id')
                  ->references('id')->on('operadoras_ans')
                  ->cascadeOnDelete();
        });
    }
};
