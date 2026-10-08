<?php

namespace Tests\Feature;

use App\Models\Convenio;
use App\Models\Local;
use App\Models\Medico;
use App\Models\User;
use App\Support\FaixaDePreco;
use App\Support\FotoDePerfil;
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

            // 07/10: a foto fica no banco (tabela fotos), não numa pasta.
            $user = User::where('email', $email)->first();
            $chave = FotoDePerfil::chave($user->foto);
            $this->assertNotNull($chave);
            $this->assertDatabaseHas('fotos', ['chave' => $chave, 'mime' => 'image/png']);

            $this->comoUsuario($email)->delete('/minha-foto')->assertSessionHas('sucesso');
            $this->assertNull($user->fresh()->foto);
            $this->assertDatabaseMissing('fotos', ['chave' => $chave]);
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

        $this->comoAdmin()->get('/admin/usuarios?busca=vidaplena')->assertSee($vidaPlena->foto_url, false);
        auth()->logout();
        $this->get('/clinica/' . $vidaPlena->clinica->id)->assertSee($vidaPlena->foto_url, false);

        // E o endereço devolve a imagem, para qualquer visitante (como a página da clínica).
        $this->get($vidaPlena->foto_url)->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_foto_no_banco_chave_desconhecida_e_foto_do_seeder(): void
    {
        // Chave que não existe: 404, sem erro 500.
        $this->get('/foto/' . str_repeat('a', 40))->assertNotFound();

        // Foto de demonstração do seeder continua sendo arquivo em public/ e nunca é apagada.
        $this->assertSame(asset('imgs/medicos/medico3.jpeg'), FotoDePerfil::url('imgs/medicos/medico3.jpeg'));
        FotoDePerfil::apagar('imgs/medicos/medico3.jpeg');
        $this->assertFileExists(public_path('imgs/medicos/medico3.jpeg'));
    }

    public function test_home_mostra_hospitais_e_clinicas_do_banco_com_a_imagem_de_cada_um(): void
    {
        $resposta = $this->get('/')->assertOk()->assertSee('Hospitais e Clínicas');

        // 07/10: a lista fixa saiu; o hospital que não existia não aparece mais.
        $resposta->assertDontSee('Vale Sereno');

        foreach (\App\Http\Controllers\HomeController::IMAGENS_DA_VITRINE as $nome => $imagem) {
            $local = Local::where('nome', $nome)->firstOrFail();
            $this->assertFileExists(public_path($imagem));
            $resposta->assertSee($local->nome)
                ->assertSee($local->endereco_completo)
                ->assertSee(asset($imagem), false)
                ->assertSee(route('publico.local', $local), false);
        }

        // Local sem imagem na lista aparece com o ícone, sem imagem quebrada.
        Local::where('nome', 'Aurora - Vila Ema')->update(['nome' => 'Aurora - Unidade nova']);
        $this->get('/')->assertSee('Aurora - Unidade nova')->assertDontSee('imgs/inst/aurora.png', false)
            ->assertSee('clinica-card__sem-foto', false);
    }

    public function test_excluir_conta_apaga_a_foto(): void
    {
        $this->comoUsuarioFinal()->post('/minha-foto', ['foto' => UploadedFile::fake()->image('eu.png', 120, 120)]);
        $chave = FotoDePerfil::chave(User::where('email', 'ana@facilmed.test')->first()->foto);
        $this->assertDatabaseHas('fotos', ['chave' => $chave]);

        $this->comoUsuarioFinal()->delete('/usuario/perfil', ['current_password' => 'facilmed2026', 'confirmacao' => '1'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('fotos', ['chave' => $chave]);
    }
}
