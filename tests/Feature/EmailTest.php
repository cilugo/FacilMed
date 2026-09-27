<?php

namespace Tests\Feature;

use App\Mail\AvisoDeConsulta;
use App\Models\Consulta;
use App\Models\NotificacaoEnviada;
use App\Models\Vinculo;
use App\Services\CalculadoraDeHorarios;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Avisos por e-mail (Notificador) e o comando de lembretes. */
class EmailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function agendar(int $pular = 0): Consulta
    {
        $dias = app(CalculadoraDeHorarios::class)->proximosDias(Vinculo::find(1), 1);
        $d = array_key_first($dias);
        $this->comoPaciente()->post('/agendar', ['vinculo_id' => 1, 'especialidade_id' => 1, 'data_consulta' => $d, 'horario' => $dias[$d][$pular], 'forma_pagamento' => 'particular'])
            ->assertSessionHasNoErrors();

        return Consulta::latest('id')->first();
    }

    private function enviados(string $tipo): \Illuminate\Support\Collection
    {
        return Mail::sent(AvisoDeConsulta::class, fn ($m) => $m->tipo === $tipo);
    }

    public function test_agendar_manda_confirmacao_e_registra(): void
    {
        $c = $this->agendar();

        Mail::assertSent(AvisoDeConsulta::class, fn ($m) => $m->tipo === 'confirmacao' && $m->hasTo('ana@facilmed.test'));
        $this->assertTrue(NotificacaoEnviada::where('consulta_id', $c->id)->where('tipo', 'confirmacao')->value('sucesso'));
    }

    public function test_paciente_cancela_avisa_o_medico(): void
    {
        $c = $this->agendar();
        $this->comoPaciente()->post("/paciente/consultas/{$c->id}/cancelar");

        Mail::assertSent(AvisoDeConsulta::class, fn ($m) => $m->tipo === 'cancelamento' && $m->hasTo('helena@facilmed.test'));
    }

    public function test_medico_cancela_avisa_o_paciente_com_o_motivo(): void
    {
        $c = $this->agendar();
        $this->comoMedico()->post("/medico/agenda/{$c->id}/cancelar", ['motivo' => 'Cirurgia de emergência']);

        Mail::assertSent(AvisoDeConsulta::class, fn ($m) => $m->tipo === 'cancelamento' && $m->hasTo('ana@facilmed.test')
            && str_contains($m->render(), 'Cirurgia de emergência'));
    }

    public function test_remarcar_confirma_a_nova_e_avisa_o_medico(): void
    {
        $antiga = $this->agendar();
        $dias = app(CalculadoraDeHorarios::class)->proximosDias(Vinculo::find(1), 1);
        $d = array_key_first($dias);

        $this->comoPaciente()->post('/agendar', ['vinculo_id' => 1, 'especialidade_id' => 1, 'data_consulta' => $d, 'horario' => $dias[$d][1],
            'forma_pagamento' => 'particular', 'remarcar_consulta_id' => $antiga->id])->assertSessionHasNoErrors();

        $this->assertCount(2, $this->enviados('confirmacao'));
        Mail::assertSent(AvisoDeConsulta::class, fn ($m) => $m->tipo === 'remarcacao' && $m->hasTo('helena@facilmed.test'));
        Mail::assertNotSent(AvisoDeConsulta::class, fn ($m) => $m->tipo === 'cancelamento');
    }

    public function test_mesmo_aviso_nunca_sai_duas_vezes(): void
    {
        $c = $this->agendar();
        app(\App\Services\Notificador::class)->enviar($c, 'confirmacao');

        $this->assertCount(1, $this->enviados('confirmacao'));
    }

    public function test_lembrete_so_uma_vez_e_so_para_quem_marcou_com_antecedencia(): void
    {
        $base = ['paciente_id' => 1, 'medico_id' => 1, 'vinculo_id' => 1, 'especialidade_id' => 1, 'duracao_minutos' => 30,
            'forma_pagamento' => 'particular', 'valor' => 220, 'status' => 'agendada', 'origem' => 'medico'];
        $amanha = now()->addHours(20);

        $antiga = Consulta::create($base + ['data_consulta' => $amanha->toDateString(), 'horario' => $amanha->format('H:00')]);
        $antiga->forceFill(['created_at' => now()->subDays(5)])->save();

        $recente = Consulta::create($base + ['data_consulta' => $amanha->toDateString(), 'horario' => $amanha->copy()->addHour()->format('H:00')]);

        $this->artisan('facilmed:enviar-lembretes')->assertSuccessful();
        $this->artisan('facilmed:enviar-lembretes')->assertSuccessful();

        $lembretes = $this->enviados('lembrete_24h');
        $this->assertCount(1, $lembretes);
        $this->assertSame($antiga->id, $lembretes->first()->consulta->id);
        $this->assertFalse(NotificacaoEnviada::where('consulta_id', $recente->id)->exists());
    }

    public function test_falha_no_envio_nao_derruba_o_agendamento(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP fora do ar'));

        $c = $this->agendar();

        $this->assertSame('agendada', $c->status);
        $n = NotificacaoEnviada::where('consulta_id', $c->id)->first();
        $this->assertFalse($n->sucesso);
        $this->assertStringContainsString('SMTP fora do ar', $n->erro);
    }
}
