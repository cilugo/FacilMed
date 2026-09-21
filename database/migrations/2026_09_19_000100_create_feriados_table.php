<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FERIADOS.
 *
 * Tabela 22 do sistema - nao estava nas 21 originais. Entrou pela
 * decisao de 19/09/2026: o sistema conhece os feriados nacionais
 * sozinho, e o admin cadastra os municipais.
 *
 * COMO A REGRA FUNCIONA
 * ---------------------
 * abrangencia = 'nacional'   -> fecha TODO MUNDO, sem escolha.
 *                               25 de dezembro ninguem atende.
 *
 * abrangencia = 'municipal'  -> so vale onde o local ACEITOU seguir.
 * abrangencia = 'facultativo'   Carnaval e Corpus Christi entram
 *                               aqui: nao sao feriado nacional por
 *                               lei, mas na pratica quase tudo fecha.
 *                               Quem decide e cada local.
 *
 * POR QUE O VINCULO E COM `locais` E NAO COM `clinicas`
 * ------------------------------------------------------
 * A decisao foi "a clinica escolhe se segue". Mas quem fecha as
 * portas e o ENDERECO, nao a empresa: uma rede pode ter unidade em
 * duas cidades, e o feriado da padroeira de uma nao fecha a outra.
 * Alem disso, prendendo no local o medico autonomo tambem e atendido
 * (o consultorio proprio dele e um local), e a permissao de editar
 * ja existe pronta: Local::donoUserId() diz quem manda, seja clinica
 * ou autonomo. Nao precisou de regra nova.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feriados', function (Blueprint $table) {
            $table->id();
            $table->date('data');
            $table->string('nome', 120);

            $table->enum('abrangencia', ['nacional', 'facultativo', 'municipal'])
                  ->default('nacional');

            // Preenchidos so quando abrangencia = 'municipal'. Servem
            // para nao oferecer a uma clinica de Sao Paulo o feriado
            // da padroeira de outra cidade.
            $table->string('cidade', 100)->nullable();
            $table->char('uf', 2)->nullable();

            $table->boolean('ativo')->default(true);
            $table->timestamps();

            // O mesmo dia pode ter feriado nacional e municipal.
            // O que nao pode e o MESMO feriado duplicado na mesma
            // cidade - e o que este indice impede.
            $table->unique(['data', 'nome', 'cidade']);
            $table->index(['data', 'abrangencia']);
        });

        /**
         * "Este local segue este feriado."
         *
         * So faz sentido para 'municipal' e 'facultativo'. Feriado
         * nacional nao passa por aqui - ele vale de qualquer jeito.
         */
        Schema::create('feriado_local', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feriado_id')->constrained('feriados')->cascadeOnDelete();
            $table->foreignId('local_id')->constrained('locais')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['feriado_id', 'local_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feriado_local');
        Schema::dropIfExists('feriados');
    }
};
