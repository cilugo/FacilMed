<?php

namespace Tests\Feature;

use App\Models\Bloqueio;
use App\Models\Consulta;
use App\Models\Disponibilidade;
use App\Models\Medico;
use App\Models\Preco;
use App\Models\User;
use App\Models\Vinculo;
use App\Services\CalculadoraDeHorarios;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * 01/10/2026 (plano novo do grupo): o MÉDICO SÓ VÊ a agenda; horários,
 * ausências, perfil e realizada/falta/cancelar são da CLÍNICA.
 *
 * Helena (medico 1): vínculo 1 = Vida Plena (seg-sex 08-12), vínculo 2 =
 * Santa Clara (seg-sex 14-18, outra clínica). Vida Plena abre sábado 08-12.
 * comoClinica() = Vida Plena. Rafael atende na SpSaúde (vínculo 3).
 */
class MedicoTest extends TestCase
{
    private function bloco(array $extra = [])
    {
        return $this->comoClinica()->post('/clinica/horarios', array_merge([
            'vinculo_id' => 1, 'dia_semana' => 'sabado', 'hora_inicio' => '08:00', 'hora_fim' => '11:00',
            'duracao_consulta_minutos' => 30,
        ], $extra));
    }

    private function proximaVaga(int $vinculo = 1): array
    {
        $dias = app(CalculadoraDeHorarios::class)->proximosDias(Vinculo::find($vinculo), 1);
        $d = array_key_first($dias);

        return [$d, $dias[$d][0]];
    }

    private function agendarComHelena(): Consulta
    {
        [$data, $hora] = $this->proximaVaga();
        $this->comoPaciente()->post('/agendar', ['vinculo_id' => 1, 'especialidade_id' => 1, 'data_consulta' => $data, 'horario' => $hora, 'forma_pagamento' => 'particular']);

        return Consulta::latest('id')->first();
    }

    // ---------------- O médico só vê
    public function test_medico_nao_tem_mais_as_telas_de_editar(): void
    {
        foreach (['/medico/horarios', '/medico/ausencias', '/medico/locais', '/medico/precos'] as $url) {
            $this->comoMedico()->get($url)->assertNotFound();
        }
        $this->comoMedico()->post('/medico/horarios', [])->assertNotFound();
        $this->comoMedico()->put('/medico/perfil/especialidades', ['especialidades' => [2]])->assertNotFound();

        $c = $this->agendarComHelena();
        $this->comoMedico()->post("/medico/agenda/{$c->id}/cancelar", ['motivo' => 'x'])->assertNotFound();
        $this->assertSame('agendada', $c->fresh()->status);
    }

    public function test_medico_nao_entra_nas_telas_da_clinica(): void
    {
        $this->comoMedico()->get('/clinica/horarios')->assertForbidden();
        $this->comoMedico()->post('/clinica/horarios', ['vinculo_id' => 1])->assertForbidden();
    }

    // ---------------- Horários (pela clínica)
    public function test_clinica_adiciona_bloco_no_sabado_e_ele_gera_horarios(): void
    {
        $this->bloco()->assertSessionHasNoErrors();

        $sabado = Carbon::parse('next saturday');
        $this->assertContains('08:00', app(CalculadoraDeHorarios::class)->paraData(Vinculo::find(1), $sabado->addWeek()));
    }

    public function test_bloco_fora_do_funcionamento_da_unidade_e_recusado(): void
    {
        $this->bloco(['hora_fim' => '13:00'])->assertSessionHasErrors('hora_inicio');   // sábado fecha 12h
        $this->bloco(['dia_semana' => 'domingo'])->assertSessionHasErrors('dia_semana'); // não abre
    }

    public function test_bloco_que_choca_com_outro_lugar_do_medico_e_recusado(): void
    {
        // Segunda 13-15 na Vida Plena choca com o bloco dela no Santa Clara (14-18),
        // mesmo sendo outra clínica: ninguém está em dois lugares ao mesmo tempo.
        $this->bloco(['dia_semana' => 'segunda', 'hora_inicio' => '13:00', 'hora_fim' => '15:00'])
            ->assertSessionHasErrors('hora_inicio');
    }

    public function test_clinica_nao_mexe_em_horario_de_outra_clinica(): void
    {
        // Vínculo 2 = Helena no Santa Clara (outra clínica).
        $this->bloco(['vinculo_id' => 2])->assertSessionHasErrors('vinculo_id');
        $doSantaClara = Disponibilidade::where('vinculo_id', 2)->first();
        $this->comoClinica()->delete("/clinica/horarios/{$doSantaClara->id}")->assertForbidden();
        $this->assertNotNull($doSantaClara->fresh());
    }

    public function test_remover_bloco_avisa_consultas_que_continuam(): void
    {
        $consulta = $this->agendarComHelena();
        $dia = Disponibilidade::DIAS[$consulta->data_consulta->dayOfWeek];
        $bloco = Disponibilidade::where('vinculo_id', 1)->where('dia_semana', $dia)->first();

        $this->comoClinica()->delete("/clinica/horarios/{$bloco->id}")->assertSessionHas('sucesso');
        $this->assertStringContainsString('continua', session('sucesso'));
        $this->assertSame('agendada', $consulta->fresh()->status);
    }

    // ---------------- Ausências (pela clínica)
    public function test_ausencia_tira_horarios_e_nao_cancela_consulta_sem_pedir(): void
    {
        $consulta = $this->agendarComHelena();
        $data = $consulta->data_consulta->toDateString();

        $this->comoClinica()->post('/clinica/ausencias', ['vinculo_id' => 1, 'inicio' => "$data 00:00", 'fim' => "$data 23:59", 'motivo' => 'Congresso'])
            ->assertSessionHas('erro');

        $this->assertSame('agendada', $consulta->fresh()->status);
        $this->assertSame([], app(CalculadoraDeHorarios::class)->paraData(Vinculo::find(1), Carbon::parse($data)));
    }

    public function test_ausencia_com_cancelar_consultas_cancela_com_motivo(): void
    {
        $consulta = $this->agendarComHelena();
        $data = $consulta->data_consulta->toDateString();

        $this->comoClinica()->post('/clinica/ausencias', ['vinculo_id' => 1, 'inicio' => "$data 00:00", 'fim' => "$data 23:59", 'motivo' => 'Congresso', 'cancelar_consultas' => 1])
            ->assertSessionHasNoErrors();

        $this->assertSame('cancelada', $consulta->fresh()->status);
        $this->assertSame('Ausência do médico: Congresso', $consulta->fresh()->motivo_cancelamento);
    }

    public function test_ausencia_so_vale_na_unidade_da_clinica(): void
    {
        // A ausência na Vida Plena não mexe nos horários dela no Santa Clara.
        [$data] = $this->proximaVaga(2);
        $this->comoClinica()->post('/clinica/ausencias', ['vinculo_id' => 1, 'inicio' => "$data 00:00", 'fim' => "$data 23:59"])
            ->assertSessionHasNoErrors();
        $this->assertNotSame([], app(CalculadoraDeHorarios::class)->paraData(Vinculo::find(2), Carbon::parse($data)));

        // E a clínica não registra ausência no vínculo de outra clínica.
        $this->comoClinica()->post('/clinica/ausencias', ['vinculo_id' => 2, 'inicio' => "$data 00:00", 'fim' => "$data 23:59"])
            ->assertSessionHasErrors('vinculo_id');
    }

    public function test_ausencia_invalida_e_remover_de_outra_clinica(): void
    {
        $this->comoClinica()->post('/clinica/ausencias', ['vinculo_id' => 1, 'inicio' => now()->addDays(3)->toDateString(), 'fim' => now()->addDay()->toDateString()])
            ->assertSessionHasErrors('fim');

        $b = Bloqueio::create(['medico_id' => 1, 'vinculo_id' => 2, 'inicio' => now()->addDay(), 'fim' => now()->addDays(2)]);
        $this->comoClinica()->delete("/clinica/ausencias/{$b->id}")->assertForbidden();
        $this->comoUsuario('contato@santaclara.test')->delete("/clinica/ausencias/{$b->id}")->assertSessionHas('sucesso');
        $this->assertNull($b->fresh());
    }

    // ---------------- Perfil do médico (pela clínica)
    public function test_clinica_edita_dados_do_medico_dela(): void
    {
        $this->comoClinica()->put('/clinica/medicos/1', ['bio' => 'Nova bio', 'anos_atuacao' => 12, 'telefone_profissional' => '(12) 3333-4444'])
            ->assertSessionHasNoErrors();

        $helena = Medico::find(1);
        $this->assertSame('Nova bio', $helena->bio);
        $this->assertSame(12, (int) $helena->anos_atuacao);
        $this->assertSame('1233334444', $helena->telefone_profissional);
    }

    public function test_clinica_sem_vinculo_nao_edita_o_medico(): void
    {
        // A SpSaúde não tem a Helena.
        $this->comoUsuario('contato@clinicaspsaude.test')->get('/clinica/medicos/1/editar')->assertForbidden();
        $this->comoUsuario('contato@clinicaspsaude.test')->put('/clinica/medicos/1', ['bio' => 'invasão'])->assertForbidden();
        $this->comoUsuario('contato@clinicaspsaude.test')->put('/clinica/medicos/1/convenios', ['convenios' => []])->assertForbidden();
        $this->assertNotSame('invasão', Medico::find(1)->bio);
    }

    public function test_especialidades_com_principal_e_precos_desativados(): void
    {
        // Helena: Clínica Geral (1) + Cardiologia (2). Fica com Cardiologia + 5.
        $this->comoClinica()->put('/clinica/medicos/1/especialidades', ['especialidades' => [2, 5], 'principal' => 5])->assertSessionHasNoErrors();

        $esp = Medico::find(1)->especialidades()->get();
        $this->assertEqualsCanonicalizing([2, 5], $esp->pluck('id')->all());
        $this->assertSame(5, $esp->firstWhere('pivot.principal', true)->id);
        $this->assertFalse((bool) Preco::where('vinculo_id', 1)->where('especialidade_id', 1)->value('ativo'));
    }

    public function test_nao_tira_especialidade_com_consulta_futura(): void
    {
        $this->agendarComHelena();

        $this->comoClinica()->put('/clinica/medicos/1/especialidades', ['especialidades' => [2]])->assertSessionHas('erro');
        $this->assertTrue(Medico::find(1)->especialidades()->where('especialidades.id', 1)->exists());
    }

    public function test_convenios_do_medico_pela_clinica(): void
    {
        $this->comoClinica()->put('/clinica/medicos/1/convenios', ['convenios' => [999]])->assertSessionHasErrors('convenios.0');
        $this->comoClinica()->put('/clinica/medicos/1/convenios', ['convenios' => []])->assertSessionHasNoErrors();
        $this->assertSame(0, Medico::find(1)->convenios()->count());
    }

    // ---------------- Agenda (pela clínica)
    public function test_clinica_cancela_consulta_futura_com_motivo(): void
    {
        $c = $this->agendarComHelena();

        $this->comoClinica()->post("/clinica/agenda/{$c->id}/cancelar")->assertSessionHasErrors('motivo');
        $this->comoUsuario('contato@clinicaspsaude.test')->post("/clinica/agenda/{$c->id}/cancelar", ['motivo' => 'x'])->assertForbidden();
        $this->comoClinica()->post("/clinica/agenda/{$c->id}/cancelar", ['motivo' => 'Imprevisto do médico'])->assertSessionHasNoErrors();

        $this->assertSame('cancelada', $c->fresh()->status);
        $this->assertSame('Imprevisto do médico', $c->fresh()->motivo_cancelamento);
    }

    public function test_clinica_marca_realizada_e_falta_so_depois_do_horario(): void
    {
        $futura = $this->agendarComHelena();
        $this->comoClinica()->post("/clinica/agenda/{$futura->id}/realizada")->assertForbidden();

        // Uma consulta de ontem, ainda "agendada", numa unidade da Vida Plena.
        $passada = Consulta::where('vinculo_id', 1)->where('status', 'realizada')->firstOrFail();
        $passada->forceFill(['status' => 'agendada'])->save();

        $this->comoUsuario('contato@clinicaspsaude.test')->post("/clinica/agenda/{$passada->id}/falta")->assertForbidden();
        $this->comoClinica()->post("/clinica/agenda/{$passada->id}/falta")->assertSessionHas('sucesso');
        $this->assertSame('nao_compareceu', $passada->fresh()->status);
    }
}
