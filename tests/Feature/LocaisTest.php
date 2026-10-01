<?php

namespace Tests\Feature;

use App\Models\Avaliacao;
use App\Models\Clinica;
use App\Models\Local;
use App\Models\Vinculo;
use App\Support\Localizacao;
use Tests\TestCase;

/**
 * 29/09/2026 — plano do app: "Locais perto de você" (/locais) e a página do
 * local (/local/{id}). Distância sem serviço externo: a posição do paciente vem
 * do navegador (ou do centro da cidade escolhida) e a de cada local, das
 * coordenadas aproximadas de config/localizacao.php.
 *
 * Locais do seed: Vida Plena, SpSaúde e Aurora (São José dos Campos),
 * Santa Clara (Taubaté), São Lucas (Jacareí) e Esperança (Caçapava).
 */
class LocaisTest extends TestCase
{
    private const TAUBATE = ['lat' => -23.0264, 'lng' => -45.5553];

    private function local(string $nome): Local
    {
        return Local::where('nome', $nome)->firstOrFail();
    }

    // --- Coordenadas e distância -------------------------------------

    public function test_distancia_entre_sao_jose_e_taubate_fica_perto_de_38_km(): void
    {
        $km = Localizacao::distanciaKm(-23.1794, -45.8869, self::TAUBATE['lat'], self::TAUBATE['lng']);

        $this->assertGreaterThan(35, $km);
        $this->assertLessThan(42, $km);
        $this->assertSame('800 m', Localizacao::formatar(0.8));
        $this->assertSame('2,3 km', Localizacao::formatar(2.34));
        $this->assertSame('38 km', Localizacao::formatar(37.9));
    }

    public function test_os_locais_do_seed_ja_tem_coordenada(): void
    {
        $this->assertSame(0, Local::whereNull('latitude')->count());
    }

    public function test_local_novo_ganha_a_coordenada_do_bairro_ou_da_cidade(): void
    {
        $clinica = Clinica::firstOrFail();
        $base = ['clinica_id' => $clinica->id, 'tipo' => 'clinica', 'endereco' => 'Rua Teste', 'uf' => 'SP'];

        $centro = Local::create($base + ['nome' => 'Unidade Centro', 'bairro' => 'Centro', 'cidade' => 'Taubaté']);
        $this->assertEqualsWithDelta(-23.03, $centro->latitude, 0.02);

        // Bairro que a tabela não conhece: fica o centro da cidade.
        $semBairro = Local::create($base + ['nome' => 'Unidade Nova', 'bairro' => 'Bairro Inventado', 'cidade' => 'sao jose dos campos']);
        $this->assertEqualsWithDelta(-23.18, $semBairro->latitude, 0.03);

        // Cidade fora da tabela: fica sem coordenada (e sem distância na busca).
        $longe = Local::create($base + ['nome' => 'Unidade Longe', 'bairro' => 'Centro', 'cidade' => 'Manaus', 'uf' => 'AM']);
        $this->assertNull($longe->latitude);
    }

    // --- /locais ------------------------------------------------------

    public function test_locais_mostra_so_quem_recebe_agendamento(): void
    {
        $this->get('/locais')->assertOk()
            ->assertSee('Vida Plena - Centro')->assertSee('Santa Clara - Taubaté');

        $this->local('Santa Clara - Taubaté')->clinica->user->update(['status' => 'bloqueado']);
        $this->local('Vida Plena - Centro')->update(['ativo' => false]);

        $this->get('/locais')->assertOk()
            ->assertDontSee('Santa Clara - Taubaté')->assertDontSee('Vida Plena - Centro')
            ->assertSee('Aurora - Vila Ema');
    }

    public function test_com_a_localizacao_o_mais_perto_vem_primeiro(): void
    {
        $this->get('/locais?' . http_build_query(self::TAUBATE))->assertOk()
            ->assertSeeInOrder(['Santa Clara - Taubaté', 'Esperança - Caçapava', 'São Lucas - Jacareí'])
            ->assertSee(' km');
    }

    public function test_com_a_cidade_ordena_a_partir_do_centro_dela(): void
    {
        $this->get('/locais?cidade=' . urlencode('Jacareí'))->assertOk()
            ->assertSeeInOrder(['São Lucas - Jacareí', 'Santa Clara - Taubaté']);
    }

    public function test_filtra_por_especialidade(): void
    {
        $this->get('/locais?especialidade=pediatria')->assertOk()
            ->assertSee('Aurora - Vila Ema')->assertSee('Esperança - Caçapava')
            ->assertDontSee('Santa Clara - Taubaté')->assertDontSee('Vida Plena - Centro');
    }

    // --- CEP (30/09: busca da home para quem está logado) -------------

    public function test_cep_vira_a_cidade_dele(): void
    {
        $this->assertSame('São José dos Campos', Localizacao::cidadePorCep('12230-000'));
        $this->assertSame('Taubaté', Localizacao::cidadePorCep('12020270'));
        $this->assertSame('Jacareí', Localizacao::cidadePorCep(' 12307-000 '));
        $this->assertNull(Localizacao::cidadePorCep('99999-999'));
        $this->assertNull(Localizacao::cidadePorCep('1230'));
        $this->assertNull(Localizacao::cidadePorCep(null));
    }

    public function test_com_o_cep_ordena_a_partir_do_centro_da_cidade_dele(): void
    {
        $this->get('/locais?cep=12307-000')->assertOk()
            ->assertSee('Pelo CEP 12307-000')
            ->assertSeeInOrder(['São Lucas - Jacareí', 'Santa Clara - Taubaté']);
    }

    public function test_cidade_escolhida_vale_mais_que_o_cep(): void
    {
        $this->get('/locais?cep=12307-000&cidade=' . urlencode('Taubaté'))->assertOk()
            ->assertDontSee('Pelo CEP')
            ->assertSeeInOrder(['Santa Clara - Taubaté', 'São Lucas - Jacareí']);
    }

    public function test_cep_que_nao_esta_na_lista_avisa_e_nao_quebra(): void
    {
        $this->get('/locais?cep=99999-999')->assertOk()
            ->assertSee('Não encontramos o CEP 99999-999');
    }

    public function test_parametros_estranhos_na_url_nao_quebram_a_pagina(): void
    {
        $this->get('/locais?lat[]=1&lng=abc&cidade[]=x&especialidade[]=y&cep[]=1')->assertOk();
        $this->get('/locais?lat=999&lng=999')->assertOk()->assertDontSee(' km de você');
    }

    // --- /local/{id} --------------------------------------------------

    public function test_pagina_do_local_mostra_endereco_medicos_e_nota_sem_comentario(): void
    {
        $local = $this->local('Vida Plena - Centro');
        $vinculo = Vinculo::where('local_id', $local->id)->firstOrFail();
        $media = Avaliacao::whereHas('consulta.vinculo', fn ($v) => $v->where('local_id', $local->id))->avg('estrelas');

        $this->assertNotNull($media, 'O seed deveria ter avaliação na Vida Plena.');

        $this->get("/local/{$local->id}")->assertOk()
            ->assertSee('Rua Quinze de Novembro')
            ->assertSee('Dra. Helena Navarro')
            ->assertSee(route('agendamento.horario', $vinculo), false)
            ->assertSee(number_format((float) $media, 1, ',', ''))
            ->assertDontSee('Atendimento pontual');   // comentário é privado (AGENTS §3)
    }

    public function test_pagina_do_local_repete_o_aviso_do_convenio(): void
    {
        $local = Vinculo::where('aceita_convenio', true)->firstOrFail()->local;

        $this->get("/local/{$local->id}")->assertOk()
            ->assertSee('Confirme na recepção se o seu plano é aceito neste endereço.');
    }

    public function test_pagina_do_local_filtra_os_medicos_pela_especialidade(): void
    {
        $local = $this->local('Vida Plena - Centro');

        $this->get("/local/{$local->id}?especialidade=cardiologia")->assertOk()->assertSee('Dra. Helena Navarro');
        $this->get("/local/{$local->id}?especialidade=pediatria")->assertOk()
            ->assertDontSee('Dra. Helena Navarro')->assertSee('Nenhum médico');
    }

    public function test_local_inativo_ou_de_clinica_bloqueada_nao_abre(): void
    {
        $inativo = $this->local('Vida Plena - Centro');
        $inativo->update(['ativo' => false]);
        $this->get("/local/{$inativo->id}")->assertNotFound();

        $bloqueado = $this->local('Santa Clara - Taubaté');
        $bloqueado->clinica->user->update(['status' => 'bloqueado']);
        $this->get("/local/{$bloqueado->id}")->assertNotFound();
    }

    public function test_home_e_busca_levam_para_os_locais(): void
    {
        // 30/09: a busca da home (especialidade + cidade ou CEP) vai para /locais.
        $this->get('/')->assertOk()->assertSee('action="' . route('busca.locais') . '"', false);
        $this->get('/buscar')->assertOk()->assertSee(route('busca.locais'), false);
    }
}
