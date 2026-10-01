<?php

namespace Tests\Feature;

use App\Models\AvaliacaoLocal;
use App\Models\Foto;
use App\Models\Local;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 01/10/2026 (plano novo do grupo: "rastreador de clínicas perto do
 * paciente"): avaliação do local, fotos e site da unidade, busca por CEP com
 * raio e os filtros "Aceita meu plano" e "Só particular".
 */
class PlanoNovoLocaisTest extends TestCase
{
    /**
     * O ConsultaSeeder sorteia o paciente das consultas, e a avaliação de
     * consulta vira avaliação do local (AvaliacaoLocalSeeder). Então o Marcos
     * PODE já ter avaliado o local: o teste começa sem a dele.
     */
    private function local(string $nome): Local
    {
        $local = Local::where('nome', $nome)->firstOrFail();
        AvaliacaoLocal::where('local_id', $local->id)
            ->where('paciente_id', User::where('email', 'marcos@facilmed.test')->first()->paciente->id)
            ->delete();

        return $local;
    }

    // ---------------- Avaliação do local
    public function test_paciente_logado_avalia_o_local_uma_vez_e_pode_atualizar(): void
    {
        $local = $this->local('Aurora - Vila Ema');
        $marcos = User::where('email', 'marcos@facilmed.test')->first()->paciente;

        $this->comoPaciente('marcos@facilmed.test')->post("/local/{$local->id}/avaliar", ['estrelas' => 4, 'comentario' => 'Sala de espera cheia.'])
            ->assertRedirect(route('publico.local', $local) . '#avaliar')->assertSessionHas('sucesso', 'Obrigado pela avaliação!');
        $this->comoPaciente('marcos@facilmed.test')->post("/local/{$local->id}/avaliar", ['estrelas' => 2])
            ->assertSessionHas('sucesso', 'Avaliação atualizada.');

        $minhas = AvaliacaoLocal::where('local_id', $local->id)->where('paciente_id', $marcos->id)->get();
        $this->assertCount(1, $minhas);
        $this->assertSame(2, $minhas->first()->estrelas);
        $this->assertNull($minhas->first()->comentario);   // mandou de novo sem comentário

        $this->comoPaciente('marcos@facilmed.test')->post("/local/{$local->id}/avaliar", ['estrelas' => 0])->assertSessionHasErrors('estrelas');
    }

    public function test_so_paciente_logado_avalia_e_so_local_publico(): void
    {
        $local = $this->local('Aurora - Vila Ema');

        $this->post("/local/{$local->id}/avaliar", ['estrelas' => 5])->assertRedirect(route('login'));
        $this->comoMedico()->post("/local/{$local->id}/avaliar", ['estrelas' => 5])->assertForbidden();
        $this->comoClinica()->post("/local/{$local->id}/avaliar", ['estrelas' => 5])->assertForbidden();

        $local->update(['ativo' => false]);
        $this->comoPaciente()->post("/local/{$local->id}/avaliar", ['estrelas' => 5])->assertNotFound();
    }

    public function test_comentario_do_local_e_privado_e_a_clinica_dona_le(): void
    {
        $local = $this->local('Aurora - Vila Ema');
        $this->comoPaciente('marcos@facilmed.test')->post("/local/{$local->id}/avaliar", ['estrelas' => 3, 'comentario' => 'Banheiro sem papel.']);

        auth()->logout();
        $this->get("/local/{$local->id}")->assertOk()->assertDontSee('Banheiro sem papel.');
        $this->comoPaciente()->get("/local/{$local->id}")->assertOk()->assertDontSee('Banheiro sem papel.');

        // O autor vê o que escreveu, no quadro de avaliar.
        $this->comoPaciente('marcos@facilmed.test')->get("/local/{$local->id}")->assertOk()
            ->assertSee('Banheiro sem papel.')->assertSee('Atualizar avaliação');

        $this->comoUsuario('contato@aurora.test')->get('/clinica/avaliacoes')->assertOk()->assertSee('Banheiro sem papel.');
        $this->comoClinica()->get('/clinica/avaliacoes')->assertOk()->assertDontSee('Banheiro sem papel.');
    }

    public function test_nota_do_local_vem_das_avaliacoes_do_local(): void
    {
        $local = $this->local('Aurora - Vila Ema');
        AvaliacaoLocal::where('local_id', $local->id)->delete();
        $this->comoPaciente()->post("/local/{$local->id}/avaliar", ['estrelas' => 5]);
        $this->comoPaciente('marcos@facilmed.test')->post("/local/{$local->id}/avaliar", ['estrelas' => 2]);

        $nota = Local::notas([$local->id])->get($local->id);
        $this->assertEquals(3.5, (float) $nota->media);
        $this->assertSame(2, (int) $nota->total);
        $this->get("/local/{$local->id}")->assertSee('3,5');
    }

    public function test_paciente_exclui_a_avaliacao_do_local_e_outro_nao(): void
    {
        $local = $this->local('Aurora - Vila Ema');
        $this->comoPaciente('marcos@facilmed.test')->post("/local/{$local->id}/avaliar", ['estrelas' => 4]);
        $a = AvaliacaoLocal::latest('id')->first();

        $this->comoPaciente()->delete("/paciente/avaliacoes-locais/{$a->id}")->assertForbidden();
        $this->comoPaciente('marcos@facilmed.test')->get('/paciente/perfil')->assertOk()->assertSee('Aurora - Vila Ema');
        $this->comoPaciente('marcos@facilmed.test')->delete("/paciente/avaliacoes-locais/{$a->id}")->assertSessionHas('sucesso');
        $this->assertNull($a->fresh());
    }

    public function test_banco_garante_uma_avaliacao_por_pessoa_e_nota_de_1_a_5(): void
    {
        $local = $this->local('Aurora - Vila Ema');
        $ana = User::where('email', 'ana@facilmed.test')->first()->paciente;
        AvaliacaoLocal::where('local_id', $local->id)->where('paciente_id', $ana->id)->delete();
        AvaliacaoLocal::create(['local_id' => $local->id, 'paciente_id' => $ana->id, 'estrelas' => 4]);

        try {
            AvaliacaoLocal::create(['local_id' => $local->id, 'paciente_id' => $ana->id, 'estrelas' => 5]);
            $this->fail('O UNIQUE deveria recusar a segunda avaliação.');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertTrue(true);
        }

        $this->expectException(\Illuminate\Database\QueryException::class);
        AvaliacaoLocal::where('local_id', $local->id)->where('paciente_id', $ana->id)->update(['estrelas' => 9]);
    }

    public function test_excluir_conta_tira_o_comentario_do_local_e_deixa_a_nota(): void
    {
        $local = $this->local('Aurora - Vila Ema');
        $this->comoPaciente('marcos@facilmed.test')->post("/local/{$local->id}/avaliar", ['estrelas' => 4, 'comentario' => 'Texto do Marcos']);
        $a = AvaliacaoLocal::latest('id')->first();

        $this->comoPaciente('marcos@facilmed.test')->delete('/paciente/perfil', ['current_password' => 'facilmed2026', 'confirmacao' => '1']);

        $this->assertSame(4, $a->fresh()->estrelas);
        $this->assertNull($a->fresh()->comentario);
    }

    // ---------------- Fotos e site da unidade
    public function test_unidades_de_demonstracao_tem_fotos_e_a_pagina_mostra(): void
    {
        $local = $this->local('Vida Plena - Centro');
        $this->assertSame(2, Foto::where('local_id', $local->id)->count());

        $foto = Foto::where('local_id', $local->id)->first();
        $this->get("/local/{$local->id}")->assertOk()->assertSee('/foto/' . $foto->id, false)->assertSee('Também atende particular');
        $this->get("/foto/{$foto->id}")->assertOk()->assertHeader('Content-Type', 'image/jpeg');   // pública
    }

    public function test_clinica_salva_site_e_recusa_endereco_perigoso(): void
    {
        $local = $this->local('Vida Plena - Centro');

        $this->comoClinica()->put("/clinica/unidades/{$local->id}/site", ['site' => 'www.vidaplena.example'])->assertSessionHasNoErrors();
        $this->assertSame('https://www.vidaplena.example', $local->fresh()->site);
        $this->get("/local/{$local->id}")->assertSee('https://www.vidaplena.example', false)->assertSee('Visitar o site');

        $this->comoClinica()->put("/clinica/unidades/{$local->id}/site", ['site' => 'javascript:alert(1)'])->assertSessionHasErrors('site');
        $this->comoClinica()->put("/clinica/unidades/{$local->id}/site", ['site' => ''])->assertSessionHasNoErrors();
        $this->assertNull($local->fresh()->site);

        $outra = $this->local('Aurora - Vila Ema');
        $this->comoClinica()->put("/clinica/unidades/{$outra->id}/site", ['site' => 'https://x.example'])->assertForbidden();
    }

    public function test_clinica_envia_e_remove_fotos_ate_seis(): void
    {
        $local = $this->local('Vida Plena - Centro');

        for ($i = 0; $i < 4; $i++) {
            $this->comoClinica()->post("/clinica/unidades/{$local->id}/fotos", ['foto' => PerfilPacienteTest::png()])->assertSessionHas('sucesso');
        }
        $this->assertSame(6, Foto::where('local_id', $local->id)->count());
        $this->comoClinica()->post("/clinica/unidades/{$local->id}/fotos", ['foto' => PerfilPacienteTest::png()])->assertSessionHas('erro');
        $this->assertSame(6, Foto::where('local_id', $local->id)->count());

        $foto = Foto::where('local_id', $local->id)->first();
        $this->comoUsuario('contato@aurora.test')->delete("/clinica/unidades/fotos/{$foto->id}")->assertForbidden();
        $this->comoClinica()->delete("/clinica/unidades/fotos/{$foto->id}")->assertSessionHas('sucesso');
        $this->assertNull($foto->fresh());

        $outra = $this->local('Aurora - Vila Ema');
        $this->comoClinica()->post("/clinica/unidades/{$outra->id}/fotos", ['foto' => PerfilPacienteTest::png()])->assertForbidden();
    }

    public function test_clinica_envia_foto_do_medico_dela(): void
    {
        $this->comoClinica()->post('/clinica/medicos/1/foto', ['foto' => PerfilPacienteTest::png()])->assertSessionHas('sucesso');
        $foto = Foto::where('medico_id', 1)->firstOrFail();

        $local = $this->local('Vida Plena - Centro');
        $this->get("/local/{$local->id}/medicos")->assertOk()->assertSee('/foto/' . $foto->id, false);

        $this->comoUsuario('contato@aurora.test')->post('/clinica/medicos/1/foto', ['foto' => PerfilPacienteTest::png()])->assertForbidden();
    }

    // ---------------- Busca: raio, CEP, filtros
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

    public function test_raio_sem_ninguem_oferece_aumentar(): void
    {
        $this->get('/locais?cidade=Ubatuba&raio=5')->assertOk()
            ->assertSee('Nenhum local com esses filtros')->assertSee('Ver até 10 km');
    }

    public function test_cep_vira_coordenada_e_a_url_leva_lat_lng(): void
    {
        config(['localizacao.servico_externo' => true]);
        Http::fake([
            'viacep.com.br/*' => Http::response(['cep' => '12243-700', 'logradouro' => 'Rua República do Iraque', 'bairro' => 'Vila Ema', 'localidade' => 'São José dos Campos', 'uf' => 'SP']),
            'nominatim.openstreetmap.org/*' => Http::response([['lat' => '-23.2006', 'lon' => '-45.8999']]),
        ]);

        $this->get('/locais?onde=12243-700&especialidade=pediatria&raio=5')
            ->assertRedirect(route('busca.locais', ['especialidade' => 'pediatria', 'cep' => '12243700', 'lat' => -23.201, 'lng' => -45.9, 'raio' => 5]));

        $this->get('/locais?especialidade=pediatria&cep=12243700&lat=-23.201&lng=-45.9&raio=5')->assertOk()
            ->assertSee('do CEP 12243-700')->assertSee('Aurora - Vila Ema');

        Http::assertSent(fn ($r) => str_contains($r->url(), 'nominatim') && $r->hasHeader('User-Agent'));
    }

    public function test_cep_que_nao_existe_e_cidade_desconhecida_avisam(): void
    {
        config(['localizacao.servico_externo' => true]);
        Http::fake(['viacep.com.br/*' => Http::response(['erro' => true])]);

        $this->get('/locais?onde=99999-999')->assertOk()->assertSee('Não encontramos o CEP 99999-999');
        $this->get('/locais?onde=Cidade Que Não Existe')->assertOk()->assertSee('Ainda não conhecemos a cidade');
    }

    public function test_cep_com_nominatim_fora_do_ar_usa_o_bairro(): void
    {
        config(['localizacao.servico_externo' => true]);
        Http::fake([
            'viacep.com.br/*' => Http::response(['localidade' => 'São José dos Campos', 'uf' => 'SP', 'bairro' => 'Vila Ema']),
            'nominatim.openstreetmap.org/*' => Http::response([], 500),
        ]);

        // Vila Ema na lista de bairros: -23.2006, -45.8999.
        $this->get('/locais?onde=12243700')->assertRedirect(route('busca.locais', ['cep' => '12243700', 'lat' => -23.201, 'lng' => -45.9]));
    }

    public function test_sem_internet_o_cep_nao_quebra_a_busca(): void
    {
        // Nos testes o serviço externo está desligado - é o XAMPP offline na banca.
        $this->get('/locais?onde=12243700')->assertOk()->assertSee('Não encontramos o CEP');
    }

    public function test_aceita_meu_plano_usa_a_carteirinha_da_ana(): void
    {
        // Ana tem SpSaúde Família: Helena e Rafael aceitam SpSaúde; Camila (Aurora, Esperança) não.
        $this->comoPaciente()->get('/locais?plano=1')->assertOk()
            ->assertSee('Vida Plena - Centro')->assertSee('São Lucas - Jacareí')
            ->assertDontSee('Aurora - Vila Ema')->assertDontSee('Esperança - Caçapava');

        // Marcos não tem carteirinha: o botão leva para "Meu plano" e o filtro não vale.
        $this->comoPaciente('marcos@facilmed.test')->get('/locais?plano=1')->assertOk()
            ->assertSee('Aurora - Vila Ema')->assertSee(route('paciente.planos'), false);
    }

    public function test_so_particular_e_convenio_escolhido(): void
    {
        $aurora = $this->local('Aurora - Vila Ema');
        $aurora->vinculos()->update(['aceita_particular' => false]);

        $this->get('/locais?particular=1')->assertOk()->assertDontSee('Aurora - Vila Ema')->assertSee('Vida Plena - Centro');

        $bemViver = \App\Models\Convenio::where('nome', 'Bem Viver Saúde')->value('id');
        // Bem Viver: Rafael (SpSaúde, São Lucas) e Camila (Aurora, Esperança). Helena não.
        $this->get('/locais?convenio=' . $bemViver)->assertOk()
            ->assertSee('Esperança - Caçapava')->assertDontSee('Vida Plena - Centro');
    }

    public function test_home_tem_a_busca_nova(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('<label for="b-especialidade">Especialidade</label>', false)->assertSee('Onde você está?')
            ->assertSee('Buscar clínicas próximas')->assertSee('name="raio"', false);
    }
}
