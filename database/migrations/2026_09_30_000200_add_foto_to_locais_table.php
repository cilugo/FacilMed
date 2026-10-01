<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 30/09/2026 — foto da fachada do local, para "Clínicas e hospitais bem
 * avaliados" na home (mesmo card dos médicos, que já têm medicos.foto).
 *
 * Caminho relativo a public/ (ex.: imgs/sliderhospcli/h1.jpg), igual ao
 * medicos.foto. Nula = a home mostra o ícone de prédio no lugar.
 *
 * Os 6 locais do seed que já existem (inclusive no banco do site no ar, que
 * não roda o seeder de novo) ganham a foto aqui mesmo - o mesmo jeito da
 * migration das coordenadas (29/09). Os novos, pelo ClinicaSeeder.
 */
return new class extends Migration
{
    /** nome do local (DadosFicticios::ESTABELECIMENTOS) => foto */
    private const FOTOS = [
        'Santa Clara - Taubaté'     => 'imgs/sliderhospcli/h1.jpg',
        'Vida Plena - Centro'       => 'imgs/sliderhospcli/h2.jpg',
        'São Lucas - Jacareí'       => 'imgs/sliderhospcli/h3.jpg',
        'Aurora - Vila Ema'         => 'imgs/sliderhospcli/h4.jpg',
        'Esperança - Caçapava'      => 'imgs/sliderhospcli/h5.jpg',
        'SpSaúde - Jardim Satélite' => 'imgs/sliderhospcli/h6.jpg',
    ];

    public function up(): void
    {
        Schema::table('locais', function (Blueprint $table) {
            $table->string('foto')->nullable()->after('telefone');
        });

        foreach (self::FOTOS as $nome => $foto) {
            DB::table('locais')->where('nome', $nome)->whereNull('foto')->update(['foto' => $foto]);
        }
    }

    public function down(): void
    {
        Schema::table('locais', function (Blueprint $table) {
            $table->dropColumn('foto');
        });
    }
};
