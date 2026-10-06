<?php

namespace App\Support;

use App\Models\Local;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * CEP ou endereço → coordenada (01/10/2026, plano novo do grupo: raio de
 * 5/10/20 km a partir do CEP do usuário).
 *
 * Dois serviços grátis, sem chave (config/localizacao.php):
 *   1. ViaCEP: CEP → rua, bairro, cidade e UF;
 *   2. Nominatim (OpenStreetMap): endereço → latitude e longitude.
 * Se um deles falhar (sem internet, fora do ar, CEP que não existe), cai na
 * coordenada APROXIMADA do bairro/cidade (Localizacao::coordenadas) - a busca
 * continua funcionando, só fica menos precisa.
 *
 * PRIVACIDADE (AGENTS.md §3): a coordenada do USUÁRIO não é guardada em lugar
 * nenhum - o controller troca o CEP por lat/lng arredondados NA URL e pronto.
 * Só a coordenada do LOCAL (endereço público de clínica) fica no banco.
 */
final class Geocodificador
{
    /**
     * @return array{lat: float, lng: float, cidade: ?string, uf: ?string, bairro: ?string, precisa: bool}|null
     *         null = CEP não encontrado e nenhuma reserva serviu
     */
    public static function doCep(string $cep): ?array
    {
        $cep = preg_replace('/\D/', '', $cep);
        if (strlen($cep) !== 8 || ! config('localizacao.servico_externo')) {
            return null;
        }

        $endereco = self::viaCep($cep);
        if ($endereco === null) {
            return null;
        }

        $ponto = self::nominatim([
            'street'     => $endereco['logradouro'] ?? null,
            'city'       => $endereco['localidade'] ?? null,
            'state'      => $endereco['uf'] ?? null,
            'postalcode' => substr($cep, 0, 5) . '-' . substr($cep, 5),
        ]) ?? self::nominatim([   // sem a rua (CEP geral de cidade, rua nova no mapa...)
            'city'  => $endereco['localidade'] ?? null,
            'state' => $endereco['uf'] ?? null,
            'county' => $endereco['bairro'] ?? null,
        ]);

        $precisa = $ponto !== null;
        $ponto ??= Localizacao::coordenadas($endereco['localidade'] ?? null, $endereco['uf'] ?? null, $endereco['bairro'] ?? null);

        if ($ponto === null) {
            return null;
        }

        return [
            'lat' => $ponto[0], 'lng' => $ponto[1],
            'cidade' => $endereco['localidade'] ?? null, 'uf' => $endereco['uf'] ?? null, 'bairro' => $endereco['bairro'] ?? null,
            'precisa' => $precisa,
        ];
    }

    /**
     * Coordenada do endereço de um local (rua, número, cidade). Chamado ao
     * salvar unidade/cadastro de clínica. Null = deixa a aproximada (Local::booted).
     *
     * @return array{0: float, 1: float}|null
     */
    public static function doLocal(Local $local): ?array
    {
        if (! config('localizacao.servico_externo')) {
            return null;
        }

        return self::nominatim([
            'street'     => trim(($local->numero ? $local->numero . ' ' : '') . $local->endereco),
            'city'       => $local->cidade,
            'state'      => $local->uf,
            'postalcode' => $local->cep ? substr($local->cep, 0, 5) . '-' . substr($local->cep, 5) : null,
        ]);
    }

    /** Preenche latitude/longitude do local com o endereço exato, se achar. */
    public static function atualizarLocal(Local $local): void
    {
        if ($ponto = self::doLocal($local)) {
            $local->forceFill(['latitude' => $ponto[0], 'longitude' => $ponto[1]])->save();
        }
    }

    private static function viaCep(string $cep): ?array
    {
        try {
            $resposta = Http::timeout(config('localizacao.timeout', 6))->acceptJson()
                ->get("https://viacep.com.br/ws/{$cep}/json/");

            $dados = $resposta->successful() ? $resposta->json() : null;

            return is_array($dados) && empty($dados['erro']) && ! empty($dados['localidade']) ? $dados : null;
        } catch (Throwable $e) {
            Log::info('ViaCEP fora do ar: ' . $e->getMessage());

            return null;
        }
    }

    /** @return array{0: float, 1: float}|null */
    private static function nominatim(array $campos): ?array
    {
        $campos = array_filter($campos, fn ($v) => is_string($v) && trim($v) !== '');
        if (! isset($campos['city'])) {
            return null;
        }

        try {
            $resposta = Http::timeout(config('localizacao.timeout', 6))
                ->withHeaders(['User-Agent' => config('localizacao.user_agent'), 'Accept-Language' => 'pt-BR'])
                ->get('https://nominatim.openstreetmap.org/search', $campos + ['country' => 'Brasil', 'format' => 'json', 'limit' => 1]);

            $primeiro = $resposta->successful() ? ($resposta->json()[0] ?? null) : null;

            if (! is_array($primeiro) || ! is_numeric($primeiro['lat'] ?? null) || ! is_numeric($primeiro['lon'] ?? null)) {
                return null;
            }

            return [(float) $primeiro['lat'], (float) $primeiro['lon']];
        } catch (Throwable $e) {
            Log::info('Nominatim fora do ar: ' . $e->getMessage());

            return null;
        }
    }
}
