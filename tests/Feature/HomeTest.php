<?php

namespace Tests\Feature;

use App\Models\Local;
use App\Models\User;
use Tests\TestCase;

/**
 * 30/09/2026 — home nova (proposta sem agendamento): busca com dois campos,
 * "Como funciona" reescrito e "Clínicas e hospitais bem avaliados" no lugar da
 * vitrine fixa do protótipo.
 */
class HomeTest extends TestCase
{
    public function test_sem_cadastro_a_busca_pede_especialidade_e_cidade(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('name="especialidade"', false)
            ->assertSee('name="cidade"', false)
            ->assertDontSee('name="cep"', false)
            ->assertDontSee('Usar minha localização');
    }

    public function test_logado_a_busca_pede_especialidade_e_cep(): void
    {
        $this->comoPaciente()->get('/')->assertOk()
            ->assertSee('name="especialidade"', false)
            ->assertSee('name="cep"', false)
            ->assertDontSee('name="cidade"', false);
    }

    public function test_logado_ve_o_resto_da_home_igual(): void
    {
        $this->comoPaciente()->get('/')->assertOk()
            ->assertSee('Como funciona?')
            ->assertSee('Médicos bem avaliados')
            ->assertSee('Clínicas e hospitais bem avaliados')
            ->assertSee('Sobre nós');
    }

    public function test_home_nao_fala_mais_em_agendar(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Encontre hospitais e clínicas')
            ->assertDontSee('Agende sua consulta')
            ->assertDontSee('receba agendamentos')
            ->assertDontSee('Agende em poucos cliques')
            ->assertDontSee('horários disponíveis');
    }

    public function test_clinicas_e_hospitais_em_destaque_com_foto_e_link(): void
    {
        $santaClara = Local::where('nome', 'Santa Clara - Taubaté')->firstOrFail();

        $this->get('/')->assertOk()
            ->assertSee('Clínicas e hospitais bem avaliados')
            ->assertSee('Hospital Santa Clara')
            ->assertSee('imgs/sliderhospcli/h1.jpg', false)
            ->assertSee(route('publico.local', $santaClara), false)
            // A vitrine fixa antiga (com o "Hospital Vale Sereno") saiu.
            ->assertDontSee('Hospital Vale Sereno');
    }

    public function test_os_6_locais_do_seed_tem_foto(): void
    {
        $this->assertSame(0, Local::whereNull('foto')->whereIn('nome', [
            'Santa Clara - Taubaté', 'Vida Plena - Centro', 'São Lucas - Jacareí',
            'Aurora - Vila Ema', 'Esperança - Caçapava', 'SpSaúde - Jardim Satélite',
        ])->count());
    }

    public function test_clinica_bloqueada_sai_do_destaque(): void
    {
        User::where('email', 'contato@santaclara.test')->update(['status' => 'bloqueado']);

        $this->get('/')->assertOk()->assertDontSee('Hospital Santa Clara');
    }

    public function test_sobre_nos_continua_igual(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Surgindo apenas como uma ideia em sala de aula');
    }
}
