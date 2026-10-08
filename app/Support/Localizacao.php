<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Busca por distância, sem serviço externo (29/09/2026).
 *
 * - Onde fica um local: coordenada APROXIMADA do bairro ou do centro da cidade,
 *   tirada de config/localizacao.php (Local::booted chama coordenadas()).
 * - Onde está o usuário: o navegador informa (botão "Usar minha localização")
 *   ou ele escolhe a cidade e a conta parte do centro dela.
 * - Distância: fórmula de Haversine, em linha reta. Não é o caminho de carro —
 *   a tela diz "em linha reta" para não prometer o que não calcula.
 */
final class Localizacao
{
    /** Raio médio da Terra, em km. */
    private const RAIO_DA_TERRA_KM = 6371;

    /**
     * Coordenada do bairro (se conhecido) ou do centro da cidade.
     * Sem UF, procura a cidade em todas. Acento, maiúscula e espaço sobrando
     * não contam ("sao jose dos campos" acha "São José dos Campos").
     *
     * @return array{0: float, 1: float}|null  [latitude, longitude]
     */
    public static function coordenadas(?string $cidade, ?string $uf = null, ?string $bairro = null): ?array
    {
        if ($cidade === null || trim($cidade) === '') {
            return null;
        }

        foreach (config('localizacao.cidades', []) as $sigla => $cidades) {
            if ($uf !== null && self::comparavel($uf) !== self::comparavel($sigla)) {
                continue;
            }

            foreach ($cidades as $nome => $dados) {
                if (self::comparavel($nome) !== self::comparavel($cidade)) {
                    continue;
                }

                foreach ($dados['bairros'] ?? [] as $nomeBairro => $ponto) {
                    if ($bairro !== null && self::comparavel($nomeBairro) === self::comparavel($bairro)) {
                        return $ponto;
                    }
                }

                return $dados['centro'];
            }
        }

        return null;
    }

    /**
     * Distância em linha reta entre dois pontos, em km (Haversine: leva em conta
     * que a Terra é redonda; para distâncias de cidade o erro é desprezível).
     */
    public static function distanciaKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return self::RAIO_DA_TERRA_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /** "menos de 100 m", "800 m", "2,3 km", "38 km". */
    public static function formatar(float $km): string
    {
        if ($km < 0.1) {
            return 'menos de 100 m';
        }

        if ($km < 1) {
            return (int) (round($km * 10) * 100) . ' m';
        }

        if ($km < 10) {
            return number_format($km, 1, ',', '') . ' km';
        }

        return (int) round($km) . ' km';
    }

    /**
     * Latitude/longitude vinda da URL (?lat=...&lng=...). Só aceita número
     * dentro do limite (90 para latitude, 180 para longitude); qualquer outra
     * coisa ("abc", lista, 999) vira null em vez de erro 500.
     */
    public static function lerCoordenada(mixed $valor, float $limite): ?float
    {
        if (! is_string($valor) || ! is_numeric($valor)) {
            return null;
        }

        $numero = (float) $valor;

        return abs($numero) <= $limite ? $numero : null;
    }

    /**
     * De onde medir a distância, a partir da URL: ?cidade= (o centro dela) ou
     * ?lat=&lng= (a posição que o navegador do usuário deu). Sem nenhum dos
     * dois: null, e a tela lista sem distância.
     *
     * A cidade vem primeiro: se a pessoa usou a localização e depois escolheu
     * uma cidade na lista, vale a escolha mais nova (a lista), mesmo que o
     * formulário ainda carregue a posição antiga.
     *
     * 'params' é o que leva a mesma origem para o próximo link (a página do local).
     *
     * @return array{lat: float, lng: float, descricao: string, params: array<string, string|float>}|null
     */
    public static function origem(mixed $lat, mixed $lng, ?string $cidade, ?string $cep = null): ?array
    {
        $ponto = self::coordenadas($cidade);

        if ($ponto !== null) {
            return ['lat' => $ponto[0], 'lng' => $ponto[1], 'descricao' => 'do centro de ' . $cidade, 'params' => ['cidade' => $cidade]];
        }

        $lat = self::lerCoordenada($lat, 90);
        $lng = self::lerCoordenada($lng, 180);

        if ($lat !== null && $lng !== null) {
            // 07/10/2026 (trazido da main): posição que veio de um CEP - o
            // BuscaController trocou o CEP por lat/lng na URL - e a tela mostra
            // "do CEP 12245-000" em vez de "de você".
            $cep = preg_replace('/\D/', '', (string) $cep);
            if (strlen($cep) === 8) {
                return ['lat' => $lat, 'lng' => $lng, 'descricao' => 'do CEP ' . self::formatarCep($cep),
                    'params' => ['lat' => $lat, 'lng' => $lng, 'cep_origem' => $cep]];
            }

            return ['lat' => $lat, 'lng' => $lng, 'descricao' => 'de você', 'params' => ['lat' => $lat, 'lng' => $lng]];
        }

        return null;
    }

    /** "12245000" → "12245-000". */
    public static function formatarCep(string $cep): string
    {
        return substr($cep, 0, 5) . '-' . substr($cep, 5, 3);
    }

    /** Raios aceitos na busca de locais (plano do grupo: 5, 10 ou 20 km - RN07). */
    public const RAIOS = [5, 10, 20];

    private static function comparavel(string $texto): string
    {
        return Str::of($texto)->ascii()->lower()->squish()->toString();
    }
}
