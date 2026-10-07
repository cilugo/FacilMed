<?php

namespace Tests\Feature;

use Tests\TestCase;

/** As telas que já existem abrem sem erro. */
class PaginasTest extends TestCase
{
    public function test_paginas_publicas(): void
    {
        foreach (['/', '/buscar', '/buscar?especialidade=cardiologia&convenio=1', '/medico/1', '/clinica/1', '/login', '/cadastro', '/cadastro/usuario', '/cadastro/clinica', '/forgot-password'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_medico_nao_verificado_nao_aparece(): void
    {
        \App\Models\Medico::where('id', 1)->update(['status_verificacao' => 'pendente']);

        $this->get('/medico/1')->assertNotFound();
        $this->get('/buscar')->assertDontSee('Helena Navarro');
    }

    public function test_paineis(): void
    {
        foreach (['/usuario', '/usuario/avaliacoes', '/usuario/planos', '/usuario/perfil'] as $url) {
            $this->comoUsuarioFinal()->get($url)->assertOk();
        }
        // 01/10/2026: sem agendamento nem consultas.
        foreach (['/usuario/consultas', '/agendar/1'] as $url) {
            $this->comoUsuarioFinal()->get($url)->assertNotFound();
        }
        $this->comoClinica()->get('/clinica')->assertOk();
        $this->comoClinica()->get('/clinica/convenios')->assertOk();
        $this->comoAdmin()->get('/admin')->assertOk();
        $this->comoAdmin()->get('/admin/convenios')->assertOk();
    }

    public function test_busca_ordena_por_preco_e_nome(): void
    {
        // 05/10: menor faixa das unidades onde atende — Camila $ (Esperança), Rafael $$ (SpSaúde), Helena $$$.
        $this->get('/buscar?ordem=preco')->assertOk()->assertSeeInOrder(['Camila Reis', 'Rafael Moreira', 'Helena Navarro']);
        $this->get('/buscar?ordem=nome')->assertOk()->assertSeeInOrder(['Dr. Rafael Moreira', 'Dra. Camila Reis', 'Dra. Helena Navarro']);
        $this->get('/buscar?ordem=qualquer')->assertOk();
    }
}
