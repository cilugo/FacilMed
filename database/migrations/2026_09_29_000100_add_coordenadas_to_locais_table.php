<?php

use App\Support\Localizacao;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 29/09/2026 — busca por distância (plano do app).
 *
 * Latitude e longitude APROXIMADAS de cada local: a do bairro ou a do centro
 * da cidade (config/localizacao.php). Nulas quando a cidade não está na lista:
 * o local continua aparecendo, só que sem distância.
 *
 * decimal(9,6): 6 casas depois da vírgula = precisão de ~10 cm, bem mais do
 * que o necessário (a coordenada já é aproximada).
 *
 * Os locais que já existem (inclusive no banco do site no ar, que não roda o
 * seeder de novo) ganham a coordenada aqui mesmo. Os novos, sozinhos ao salvar
 * (Local::booted).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locais', function (Blueprint $table) {
            $table->decimal('latitude', 9, 6)->nullable()->after('uf');
            $table->decimal('longitude', 9, 6)->nullable()->after('latitude');
        });

        foreach (DB::table('locais')->get(['id', 'cidade', 'uf', 'bairro']) as $local) {
            $ponto = Localizacao::coordenadas($local->cidade, $local->uf, $local->bairro);

            if ($ponto !== null) {
                DB::table('locais')->where('id', $local->id)
                    ->update(['latitude' => $ponto[0], 'longitude' => $ponto[1]]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('locais', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
