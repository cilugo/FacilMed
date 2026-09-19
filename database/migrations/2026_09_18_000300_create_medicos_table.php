<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('cpf', 14)->unique();

            $table->string('crm', 20);
            $table->char('uf', 2);

            /**
             * A verificacao do CRM e MANUAL: alguem confere no portal do CFM
             * e aprova. A API oficial e paga e exige CNPJ (ver AI_HANDOFF.md).
             * Medico so aparece na busca publica com status 'verificado'.
             */
            $table->enum('status_verificacao', ['pendente', 'verificado', 'rejeitado'])
                  ->default('pendente');
            $table->foreignId('verificado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verificado_em')->nullable();
            $table->string('motivo_rejeicao', 255)->nullable();

            $table->text('bio')->nullable();
            $table->string('telefone_profissional', 20)->nullable();
            $table->unsignedSmallInteger('anos_atuacao')->default(0);
            $table->string('foto')->nullable();

            /**
             * Cache das avaliacoes, recalculado quando chega avaliacao nova.
             * Sem isso, cada listagem da busca faria um AVG com JOIN.
             */
            $table->decimal('media_avaliacoes', 3, 2)->default(0);
            $table->unsignedInteger('total_avaliacoes')->default(0);

            // Conta criada pela clinica comeca com senha temporaria,
            // que o medico e obrigado a trocar no primeiro login.
            $table->boolean('senha_temporaria')->default(false);

            $table->timestamps();

            $table->unique(['crm', 'uf']);
            $table->index('status_verificacao');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicos');
    }
};
