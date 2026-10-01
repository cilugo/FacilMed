<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 01/10/2026 (plano novo do grupo): fotos do local (galeria), do médico e do
 * paciente (perfil).
 *
 * POR QUE A FOTO FICA NO BANCO, E NÃO NUMA PASTA: o Render grátis apaga os
 * arquivos enviados toda vez que o site atualiza ou reinicia (o disco dele é
 * temporário). No banco (Aiven) ela fica. A imagem vai em base64 (texto) numa
 * coluna LONGTEXT: funciona igual no MariaDB do XAMPP e no MySQL 8, sem se
 * preocupar com coluna binária. Limite de 2 MB por foto no formulário (o
 * navegador já reduz antes de enviar - public/javas/foto.js).
 *
 * Cada foto tem UM dono: um local, um médico OU um paciente - o CHECK garante
 * no banco, como em locais (chk_local_um_dono). Apagou o dono, a foto vai junto
 * (cascadeOnDelete).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('local_id')->nullable()->constrained('locais')->cascadeOnDelete();
            $table->foreignId('medico_id')->nullable()->constrained('medicos')->cascadeOnDelete();
            $table->foreignId('paciente_id')->nullable()->constrained('pacientes')->cascadeOnDelete();
            $table->string('mime', 30);            // image/jpeg, image/png ou image/webp
            $table->longText('conteudo');          // a imagem em base64
            $table->unsignedTinyInteger('ordem')->default(0);   // ordem na galeria do local
            $table->timestamps();
        });

        DB::statement("
            ALTER TABLE fotos
            ADD CONSTRAINT chk_fotos_um_dono
            CHECK (
                (local_id IS NOT NULL) + (medico_id IS NOT NULL) + (paciente_id IS NOT NULL) = 1
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('fotos');
    }
};
