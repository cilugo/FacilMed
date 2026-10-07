<?php

namespace Tests\Feature;

use App\Models\Convenio;
use App\Models\Local;
use App\Models\Medico;
use App\Models\User;
use App\Support\FaixaDePreco;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Itens do documento "Modificações - 01/10" que não cabem em outro arquivo:
 * faixa de preço, filtro de convênio nos locais, menu de quem entrou, foto
 * de perfil e "Médicos bem avaliados" da home.
 */
class ReorganizacaoTest extends TestCase
{
    public function test_faixa_de_preco_por_valor_e_pela_media(): void
    {
        $this->assertNull(FaixaDePreco::nivel(null));
        $this->assertSame(1, FaixaDePreco::nivel(150));
        $this->assertSame(1, FaixaDePreco::nivel(200));     // o limite entra na faixa de baixo
        $this->assertSame(2, FaixaDePreco::nivel(200.01));
        $this->assertSame(3, FaixaDePreco::nivel(500));
        $this->assertSame(4, FaixaDePreco::nivel(9000));

        $this->assertSame(2, FaixaDePreco::nivelDaMedia([180, 300]));   // média 240
        $this->assertNull(FaixaDePreco::nivelDaMedia([]));

        $this->assertSame('$$$', FaixaDePreco::simbolo(3));
        $this->assertSame('até R$ 200', FaixaDePreco::descricao(1));
        $this->assertSame('R$ 200 a R$ 350', FaixaDePreco::descricao(2));
        $this->assertSame('acima de R$ 500', FaixaDePreco::descricao(4));
    }

    public function test_usuario_ve_a_faixa_e_nunca_o_valor(): void
    {
        $vidaPlena = Local::where('nome', 'Vida Plena - Centro')->firstOrFail();

        // Helena na Vida Plena: Cardiologia R$ 380,00 -> $$$.
        foreach (["/local/{$vidaPlena->id}", '/medico/1', '/locais', '/buscar'] as $url) {
            $this->get($url)->assertOk()
                ->assertDontSee('380,00')
                ->assertDontSee('a partir de R$');
        }

        $this->get('/medico/1')->assertSee('R$ 350 a R$ 500')->assertSee('faixa-preco', false);
    }

    public function test_locais_filtram_por_convenio(): void
    {
        $bemViver = Convenio::where('nome', 'Bem Viver Saúde')->firstOrFail();

        // Santa Clara: Helena (SpSaúde, Horizonte), Marcelo (Horizonte) e Thiago (Bem Viver).
        // Cardiologia + Bem Viver: ninguém da cardiologia aceita Bem Viver.
        $this->get('/locais?especialidade=cardiologia&convenio=' . $bemViver->id)
            ->assertOk()->assertSee('Nenhum local com esses filtros');

        // Endocrinologia + Bem Viver: o Thiago, na Aurora e no Santa Clara.
        $this->get('/locais?especialidade=endocrinologia&convenio=' . $bemViver->id)
            ->assertOk()
            ->assertSee('Aurora - Vila Ema')
            ->assertSee('Santa Clara - Taubaté')
            ->assertSee('aceitando Bem Viver Saúde')
            ->assertDontSee('Vida Plena - Centro');

        // A clínica desliga "atende convênio" do Thiago na Aurora: a Aurora sai.
        $thiago = Medico::where('crm', '889900')->firstOrFail();
        $thiago->vinculos()->whereHas('local', fn ($l) => $l->where('nome', 'Aurora - Vila Ema'))->update(['aceita_convenio' => false]);

        $this->get('/locais?especialidade=endocrinologia&convenio=' . $bemViver->id)
            ->assertOk()->assertDontSee('Aurora - Vila Ema')->assertSee('Santa Clara - Taubaté');
    }

    public function test_menu_de_quem_entrou_e_diferente_do_menu_da_home(): void
    {
        $this->get('/locais')->assertOk()
            ->assertSee('Como funciona')->assertSee('Entrar')->assertDontSee('Minhas avaliações');

        $this->comoUsuarioFinal()->get('/locais')->assertOk()
            ->assertDontSee('Como funciona')
            ->assertSee('Minhas avaliações')
            ->assertSee('Meu plano')
            ->assertSee('Sair');

        $this->comoClinica()->get('/locais')->assertOk()
            ->assertSee('Meus médicos')->assertDontSee('Como funciona');
    }

    public function test_home_mostra_ate_seis_medicos_bem_avaliados(): void
    {
        $this->assertGreaterThanOrEqual(6, Medico::visivel()->count(), 'O seed precisa de pelo menos 6 médicos.');

        $resposta = $this->get('/')->assertOk()->assertSee('Médicos bem avaliados');
        preg_match_all('#href="[^"]*/medico/(\d+)"#', $resposta->getContent(), $m);
        $this->assertCount(6, array_unique($m[1]), 'A home mostra 6 médicos diferentes (eram só 3 no seed antigo).');
    }

    public function test_usuario_clinica_e_admin_trocam_a_propria_foto(): void
    {
        foreach (['ana@facilmed.test', 'contato@vidaplena.test', 'admin@facilmed.test'] as $email) {
            $this->comoUsuario($email)->post('/minha-foto', ['foto' => UploadedFile::fake()->image('eu.png', 120, 120)])
                ->assertSessionHas('sucesso');

            $user = User::where('email', $email)->first();
            $this->assertStringStartsWith(\App\Support\FotoDePerfil::PASTA_TESTES . '/', $user->foto);
            $this->assertFileExists(public_path($user->foto));
            $arquivo = $user->foto;

            $this->comoUsuario($email)->delete('/minha-foto')->assertSessionHas('sucesso');
            $this->assertNull($user->fresh()->foto);
            $this->assertFileDoesNotExist(public_path($arquivo));
        }

        // Só imagem.
        $this->comoUsuarioFinal()->post('/minha-foto', ['foto' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('foto');
        // Visitante não.
        auth()->logout();
        $this->post('/minha-foto', ['foto' => UploadedFile::fake()->image('x.png')])->assertRedirect(route('login'));
    }

    public function test_foto_aparece_para_o_admin_e_na_pagina_da_clinica(): void
    {
        $this->comoClinica()->post('/minha-foto', ['foto' => UploadedFile::fake()->image('logo.png', 120, 120)]);
        $vidaPlena = User::where('email', 'contato@vidaplena.test')->first();

        $this->comoAdmin()->get('/admin/usuarios?busca=vidaplena')->assertSee($vidaPlena->foto, false);
        auth()->logout();
        $this->get('/clinica/' . $vidaPlena->clinica->id)->assertSee($vidaPlena->foto, false);

    }

    public function test_excluir_conta_apaga_a_foto(): void
    {
        $this->comoUsuarioFinal()->post('/minha-foto', ['foto' => UploadedFile::fake()->image('eu.png', 120, 120)]);
        $arquivo = User::where('email', 'ana@facilmed.test')->first()->foto;
        $this->assertFileExists(public_path($arquivo));

        $this->comoUsuarioFinal()->delete('/usuario/perfil', ['current_password' => 'facilmed2026', 'confirmacao' => '1'])
            ->assertRedirect(route('login'));

        $this->assertFileDoesNotExist(public_path($arquivo));
    }
}
