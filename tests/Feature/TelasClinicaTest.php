<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vinculo;
use Tests\TestCase;

/**
 * As telas internas da clínica (28/09/2026; 01/10 sem agenda e com
 * especialidades e edição de médico), com as views DE VERDADE.
 * A clínica padrão dos testes é a Vida Plena (contato@vidaplena.test).
 */
class TelasClinicaTest extends TestCase
{
    public function test_todas_as_telas_da_clinica_abrem(): void
    {
        foreach (['/clinica', '/clinica/medicos', '/clinica/medicos/novo', '/clinica/medicos/1/editar', '/clinica/especialidades',
                  '/clinica/unidades', '/clinica/convenios', '/clinica/avaliacoes', '/clinica/perfil'] as $url) {
            $this->comoClinica()->get($url)->assertOk();
        }
    }

    public function test_medicos_lista_e_mostra_confirmacao_de_desvinculo(): void
    {
        $this->comoClinica()->get('/clinica/medicos')
            ->assertOk()
            ->assertSee('Helena')
            ->assertSee('Confirmar desvínculo')
            ->assertSee('/clinica/medicos/1/editar', false)
            ->assertDontSee('cancelar_consultas', false)
            ->assertDontSee('/clinica/agenda', false);
    }

    public function test_unidades_mostra_horarios_e_formulario(): void
    {
        $this->comoClinica()->get('/clinica/unidades')
            ->assertOk()
            ->assertSee('Vida Plena')
            ->assertSee('horarios[segunda][abre]', false);
    }

    public function test_erro_no_horario_de_uma_unidade_so_aparece_nela(): void
    {
        $clinica = User::where('email', 'contato@vidaplena.test')->first()->clinica;
        $local = $clinica->locais()->first();

        $this->comoClinica()
            ->from('/clinica/unidades')
            ->put("/clinica/unidades/{$local->id}/horarios", [
                '_form' => 'horarios-' . $local->id,
                'horarios' => ['segunda' => ['abre' => '18:00', 'fecha' => '08:00']],
            ])->assertSessionHasErrors('horarios.segunda.fecha');
    }

    public function test_avaliacoes_da_clinica_mostram_comentario(): void
    {
        $this->comoClinica()->get('/clinica/avaliacoes')->assertOk()->assertSee('Comentários');
    }

    public function test_perfil_mostra_cnpj_so_leitura_e_nunca_diz_validado(): void
    {
        $this->comoClinica()->get('/clinica/perfil')
            ->assertOk()
            ->assertSee('base simulada do PointMed')
            ->assertDontSee('name="cnpj"', false)
            ->assertDontSee('validado', false);
    }
}
