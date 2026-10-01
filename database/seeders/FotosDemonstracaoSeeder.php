<?php

namespace Database\Seeders;

use App\Models\Foto;
use App\Models\Local;
use Illuminate\Database\Seeder;

/**
 * Fotos das 6 unidades de demonstração (01/10/2026), para a galeria da página
 * do local e os cartões da busca. São as fotos que o grupo já usava na home
 * (public/imgs/sliderhospcli/h1..h4.jpg), duas por unidade.
 *
 * Pode rodar quantas vezes quiser: só põe foto em unidade de demonstração que
 * ainda não tem nenhuma. Por isso o docker/entrypoint.sh chama este seeder a
 * cada boot - assim o site no ar (onde o DatabaseSeeder não roda de novo)
 * também ganha as fotos, sem apagar as que uma clínica enviou.
 */
class FotosDemonstracaoSeeder extends Seeder
{
    private const IMAGENS = ['h1.jpg', 'h2.jpg', 'h3.jpg', 'h4.jpg'];

    public function run(): void
    {
        $nomes = array_map(fn ($e) => $e[6][0], DadosFicticios::ESTABELECIMENTOS);

        Local::whereIn('nome', $nomes)->whereDoesntHave('fotos')->orderBy('id')->get()
            ->each(function (Local $local, int $i) use ($nomes) {
                $posicao = array_search($local->nome, $nomes, true);

                foreach ([0, 1] as $ordem) {
                    $arquivo = public_path('imgs/sliderhospcli/' . self::IMAGENS[($posicao + $ordem) % count(self::IMAGENS)]);
                    if (! is_file($arquivo)) {
                        continue;
                    }

                    Foto::create([
                        'local_id' => $local->id,
                        'mime'     => 'image/jpeg',
                        'conteudo' => base64_encode((string) file_get_contents($arquivo)),
                        'ordem'    => $ordem,
                    ]);
                }
            });
    }
}
