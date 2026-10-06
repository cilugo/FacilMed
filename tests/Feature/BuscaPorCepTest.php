<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Busca de locais por CEP e raio de 5/10/20 km (plano de 01/10/2026; feito
 * na main e trazido para o PointMed em 06/10/2026). Os testes não falam com
 * a internet: o ViaCEP e o Nominatim são simulados com Http::fake().
 */
class BuscaPorCepTest extends TestCase
{
    public function test_raio_de_5_km_a_partir_da_cidade(): void
    {
        $this->get('/locais?cidade=São José dos Campos&raio=5')->assertOk()
            ->assertSee('Vida Plena - Centro')->assertSee('Aurora - Vila Ema')
            ->assertDontSee('Santa Clara - Taubaté')->assertDontSee('SpSaúde - Jardim Satélite');

        $this->get('/locais?cidade=São José dos Campos&raio=20')->assertOk()
            ->assertSee('SpSaúde - Jardim Satélite')->assertDontSee('Santa Clara - Taubaté');

        // Raio inválido vira "qualquer distância".
        $this->get('/locais?cidade=São José dos Campos&raio=999')->assertOk()->assertSee('Santa Clara - Taubaté');
    }

    public function test_raio_sem_ninguem_avisa_quantos_ficaram_de_fora(): void
    {
        $this->get('/locais?cidade=Ubatuba&raio=5')->assertOk()
            ->assertSee('Nenhum local com esses filtros')->assertSee('mais longe que 5 km');
    }

    public function test_cep_vira_coordenada_e_a_url_leva_lat_lng(): void
    {
        config(['localizacao.servico_externo' => true]);
        Http::fake([
            'viacep.com.br/*' => Http::response(['cep' => '12243-700', 'logradouro' => 'Rua República do Iraque', 'bairro' => 'Vila Ema', 'localidade' => 'São José dos Campos', 'uf' => 'SP']),
            'nominatim.openstreetmap.org/*' => Http::response([['lat' => '-23.2006', 'lon' => '-45.8999']]),
        ]);

        $this->get('/locais?cep=12243-700&especialidade=pediatria&raio=5')
            ->assertRedirect(route('busca.locais', ['especialidade' => 'pediatria', 'raio' => 5, 'cep' => '12243700', 'lat' => -23.201, 'lng' => -45.9]));

        $this->get('/locais?especialidade=pediatria&cep=12243700&origem_cep=12243700&lat=-23.201&lng=-45.9&raio=5')->assertOk()
            ->assertSee('do CEP 12243-700')->assertSee('Aurora - Vila Ema');

        Http::assertSent(fn ($r) => str_contains($r->url(), 'nominatim') && $r->hasHeader('User-Agent'));
    }

    public function test_cep_novo_no_campo_passa_na_frente_da_posicao_antiga(): void
    {
        config(['localizacao.servico_externo' => true]);
        Http::fake([
            'viacep.com.br/*' => Http::response(['localidade' => 'Taubaté', 'uf' => 'SP', 'bairro' => 'Centro']),
            'nominatim.openstreetmap.org/*' => Http::response([['lat' => '-23.0264', 'lon' => '-45.5553']]),
        ]);

        // A URL ainda traz a posição do CEP anterior, mas o campo tem outro CEP.
        $this->get('/locais?cep=12020-000&origem_cep=12243700&lat=-23.201&lng=-45.9')
            ->assertRedirect(route('busca.locais', ['cep' => '12020000', 'lat' => -23.026, 'lng' => -45.555]));
    }

    public function test_cep_que_nao_existe_ou_incompleto_avisa(): void
    {
        config(['localizacao.servico_externo' => true]);
        Http::fake(['viacep.com.br/*' => Http::response(['erro' => true])]);

        $this->get('/locais?cep=99999-999')->assertOk()->assertSee('Não encontramos o CEP 99999-999');
        $this->get('/locais?cep=1224')->assertOk()->assertSee('O CEP tem 8 números');
    }

    public function test_cep_com_nominatim_fora_do_ar_usa_o_bairro(): void
    {
        config(['localizacao.servico_externo' => true]);
        Http::fake([
            'viacep.com.br/*' => Http::response(['localidade' => 'São José dos Campos', 'uf' => 'SP', 'bairro' => 'Vila Ema']),
            'nominatim.openstreetmap.org/*' => Http::response([], 500),
        ]);

        // Vila Ema na lista de bairros (config/localizacao.php): -23.2006, -45.8999.
        $this->get('/locais?cep=12243700')->assertRedirect(route('busca.locais', ['cep' => '12243700', 'lat' => -23.201, 'lng' => -45.9]));
    }

    public function test_sem_internet_o_cep_nao_quebra_a_busca(): void
    {
        // Nos testes o serviço externo está desligado (phpunit.xml) - é o XAMPP offline na banca.
        $this->get('/locais?cep=12243700')->assertOk()->assertSee('Não encontramos o CEP')->assertSee('Vida Plena - Centro');
    }

    public function test_a_tela_tem_os_campos_de_cep_e_raio(): void
    {
        $this->get('/locais')->assertOk()->assertSee('name="cep"', false)->assertSee('name="raio"', false);
    }
}
