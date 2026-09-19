<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Um local pertence a UMA clinica OU a UM medico autonomo
     * (consultorio proprio) - exatamente um dos dois.
     *
     * Quem e o dono decide quem edita o preco em `precos`.
     */
    public function up(): void
    {
        Schema::create('locais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('medico_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('nome', 150);
            $table->enum('tipo', ['clinica', 'hospital', 'consultorio']);
            $table->string('cep', 9)->nullable();
            $table->string('endereco', 200);
            $table->string('numero', 20)->nullable();
            $table->string('complemento', 100)->nullable();
            $table->string('bairro', 100)->nullable();
            $table->string('cidade', 100);
            $table->char('uf', 2);
            $table->string('telefone', 20)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['cidade', 'uf']);
        });

        // Garante no banco que o local tem exatamente um dono.
        // Requer MySQL 8.0.16+ (CHECK e ignorado em versoes anteriores).
        DB::statement("
            ALTER TABLE locais
            ADD CONSTRAINT chk_local_um_dono
            CHECK (
                (clinica_id IS NOT NULL AND medico_id IS NULL)
                OR (clinica_id IS NULL AND medico_id IS NOT NULL)
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('locais');
    }
};
