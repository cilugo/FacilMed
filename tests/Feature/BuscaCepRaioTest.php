<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 07/10/2026: busca de locais por CEP e raio de 5/10/20 km (RF03 e RN07 dos
 * slides), trazida da main de 01/10 para o PointMed. Nenhum teste fala com a
 * internet: Http::fake() responde no lugar do ViaCEP e do Nominatim.
 */
class BuscaCepRaioTest extends TestCase
{
    public function test_raio_de_5_km_a_partir_da_cidade(): void
    {
        $this->get('/locais?cidade=São José dos Campos&raio=5')->assertOk()
            ->assertSee('Vida Plena - Centro')->assertSee('Aurora - Vila Ema')
            ->assertDontSee('Santa Clara - Taubaté')
            ->assertSee('fora dos 5 km');

        // Raio inválido vira "qualquer distância".
        $this->get('/locais?cidade=São José dos Campos&raio=999')->assertOk()->assertSee('Santa Clara - Taubaté');
    }

    public function test_cep_vira_coordenada_e_a_url_leva_lat_lng(): void
    {
        config(['localizacao.servico_externo' => true]);
        Http::fake([
            'viacep.com.br/*' => Http::response(['cep' => '12243-700', 'logradouro' => 'Rua República do Iraque', 'bairro' => 'Vila Ema', 'localidade' => 'São José dos Campos', 'uf' => 'SP']),
            'nominatim.openstreetmap.org/*' => Http::response([['lat' => '-23.2006', 'lon' => '-45.8999']]),
        ]);

        // O CEP digitado vale mais que a cidade que estava no formulário.
        $this->get('/locais?cep=12243-700&cidade=Taubaté&raio=5')
            ->assertRedirect(route('busca.locais', ['raio' => 5, 'lat' => -23.201, 'lng' => -45.9, 'cep_origem' => '12243700']));

        $this->get('/locais?lat=-23.201&lng=-45.9&cep_origem=12243700&raio=5')->assertOk()
            ->assertSee('do CEP 12243-700')->assertSee('Aurora - Vila Ema')->assertDontSee('Santa Clara - Taubaté');

        Http::assertSent(fn ($r) => str_contains($r->url(), 'nominatim') && $r->hasHeader('User-Agent'));
    }

    public function test_cep_que_nao_existe_ou_incompleto_avisa(): void
    {
        config(['localizacao.servico_externo' => true]);
        Http::fake(['viacep.com.br/*' => Http::response(['erro' => true])]);

        $this->get('/locais?cep=99999-999')->assertOk()->assertSee('Não encontramos o CEP 99999-999');
        $this->get('/locais?cep=123')->assertOk()->assertSee('O CEP precisa ter 8 números.');
    }

    public function test_cep_com_nominatim_fora_do_ar_usa_o_bairro(): void
    {
        config(['localizacao.servico_externo' => true]);
        Http::fake([
            'viacep.com.br/*' => Http::response(['localidade' => 'São José dos Campos', 'uf' => 'SP', 'bairro' => 'Vila Ema']),
            'nominatim.openstreetmap.org/*' => Http::response([], 500),
        ]);

        // Vila Ema na lista de bairros (config/localizacao.php): -23.2006, -45.8999.
        $this->get('/locais?cep=12243700')
            ->assertRedirect(route('busca.locais', ['lat' => -23.201, 'lng' => -45.9, 'cep_origem' => '12243700']));
    }

    public function test_sem_internet_o_cep_nao_quebra_a_busca(): void
    {
        // Nos testes o serviço externo está desligado - é o XAMPP offline na banca.
        $this->get('/locais?cep=12243700')->assertOk()->assertSee('Não encontramos o CEP');
    }

    public function test_home_tem_o_campo_de_cep(): void
    {
        $this->get('/')->assertOk()->assertSee('name="cep"', false);
    }
}
