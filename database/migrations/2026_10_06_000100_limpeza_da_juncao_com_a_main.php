<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Junção do PointMed (rodadas de 01/10 e 05/10) com a main do GitHub (06/10/2026).
 *
 * Em 01/10 a main ganhou duas migrations que o PointMed NÃO usa:
 *   - 2026_10_01_000100_create_fotos_table        (fotos no banco, em base64)
 *   - 2026_10_01_000200_site_e_avaliacoes_dos_locais (locais.site e avaliacoes_locais)
 * O PointMed faz isso de outro jeito: foto de perfil em users.foto e uma
 * tabela só de avaliações (avaliacoes, de local OU de médico). As duas
 * migrations saíram do projeto - com elas, o migrate:fresh quebrava (a
 * segunda lê a tabela consultas, que a reorganização de 01/10 apaga).
 *
 * Quem rodou a main de 01/10 no XAMPP ficou com essas sobras no banco. Esta
 * migration as remove. Em banco novo (migrate:fresh) ela não acha nada e
 * não faz nada - por isso tudo é "se existir".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('fotos');
        Schema::dropIfExists('avaliacoes_locais');

        if (Schema::hasColumn('locais', 'site')) {
            Schema::table('locais', fn (Blueprint $table) => $table->dropColumn('site'));
        }
    }

    public function down(): void
    {
        // Nada a desfazer: eram sobras que o código do PointMed não usa.
    }
};
