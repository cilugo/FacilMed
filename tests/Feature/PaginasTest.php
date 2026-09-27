<?php

namespace Tests\Feature;

use Tests\TestCase;

/** As telas que já existem abrem sem erro. */
class PaginasTest extends TestCase
{
    public function test_paginas_publicas(): void
    {
        foreach (['/', '/buscar', '/buscar?especialidade=cardiologia&convenio=1', '/medico/1', '/clinica/1', '/login', '/cadastro', '/cadastro/paciente', '/cadastro/medico', '/cadastro/clinica', '/forgot-password'] as $url) {
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
        foreach (['/paciente', '/paciente/consultas', '/paciente/planos', '/paciente/perfil', '/agendar/1'] as $url) {
            $this->comoPaciente()->get($url)->assertOk();
        }
        $this->comoMedico()->get('/medico')->assertOk();
        $this->comoMedico()->get('/medico/consultas')->assertOk();
        $this->comoClinica()->get('/clinica')->assertOk();
        $this->comoClinica()->get('/clinica/convenios')->assertOk();
        $this->comoAdmin()->get('/admin')->assertOk();
        $this->comoAdmin()->get('/admin/convenios')->assertOk();
    }

    public function test_busca_ordena_por_preco_e_nome(): void
    {
        // Particular a partir de: Rafael 200, Helena 220, Camila 280.
        $this->get('/buscar?ordem=preco')->assertOk()->assertSeeInOrder(['Rafael Moreira', 'Helena Navarro', 'Camila Reis']);
        $this->get('/buscar?ordem=nome')->assertOk()->assertSeeInOrder(['Dr. Rafael Moreira', 'Dra. Camila Reis', 'Dra. Helena Navarro']);
        $this->get('/buscar?ordem=qualquer')->assertOk();
    }
}
