<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ATENCAO: os planos sao FICTICIOS - inventados para a
     * demonstracao. As operadoras acima e que sao reais.
     * Essa distincao precisa aparecer na tela e no texto do TCC.
     */
    public function up(): void
    {
        Schema::create('planos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convenio_id')->constrained()->cascadeOnDelete();
            $table->string('nome', 150);
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['convenio_id', 'nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planos');
    }
};
