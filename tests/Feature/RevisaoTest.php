<?php

namespace Tests\Feature;

use App\Models\Consulta;
use App\Models\PacientePlano;
use App\Models\User;
use App\Models\Vinculo;
use App\Services\CalculadoraDeHorarios;
use Tests\TestCase;

/**
 * Problemas achados na revisão de 28/09/2026. Cada teste reproduz o furo
 * como ele era e confere que agora o sistema recusa.
 */
class RevisaoTest extends TestCase
{
    /** Primeiro horário livre de um vínculo: [data, hora]. */
    private function primeiraVaga(Vinculo $vinculo): array
    {
        $dias = app(CalculadoraDeHorarios::class)->proximosDias($vinculo, 1);
        $this->assertNotEmpty($dias, 'O vínculo deveria ter vaga nos dados de teste.');
        $data = array_key_first($dias);

        return [$data, $dias[$data][0]];
    }

    private function contarAgendadas(Vinculo $vinculo, string $data, string $hora): int
    {
        return Consulta::where('vinculo_id', $vinculo->id)->whereDate('data_consulta', $data)
            ->where('horario', $hora . ':00')->where('status', 'agendada')->count();
    }

    public function test_medico_com_conta_bloqueada_nao_recebe_agendamento(): void
    {
        $helena = User::where('email', 'helena@facilmed.test')->first();
        $vinculo = $helena->medico->vinculos()->with('precos')->first();
        [$data, $hora] = $this->primeiraVaga($vinculo);
        $esp = $vinculo->precos->where('ativo', true)->first()->especialidade_id;

        // Bloqueio direto no banco: simula conta bloqueada (sem consultas a cancelar).
        $helena->update(['status' => 'bloqueado']);

        $this->assertSame([], app(CalculadoraDeHorarios::class)->paraData($vinculo->fresh(), \Carbon\Carbon::parse($data)));
        $this->comoPaciente('marcos@facilmed.test')->get('/agendar/' . $vinculo->id)->assertNotFound();

        $this->comoPaciente('marcos@facilmed.test')->post('/agendar', [
            'vinculo_id' => $vinculo->id, 'especialidade_id' => $esp, 'data_consulta' => $data,
            'horario' => $hora, 'forma_pagamento' => 'particular',
        ])->assertSessionHasErrors('vinculo_id');

        $this->assertSame(0, $this->contarAgendadas($vinculo, $data, $hora));
    }

    public function test_convenio_nao_marca_especialidade_com_preco_desativado(): void
    {
        $ana = User::where('email', 'ana@facilmed.test')->first();
        $carteirinha = PacientePlano::where('paciente_id', $ana->paciente->id)->where('status', 'ativa')->with('plano')->first();

        $vinculo = Vinculo::where('ativo', true)->where('aceita_convenio', true)->with('medico.convenios', 'precos')->get()
            ->first(fn ($v) => $v->medico->convenios->contains('id', $carteirinha->plano->convenio_id));
        $this->assertNotNull($vinculo, 'Deveria haver médico que aceita o convênio da Ana.');

        $preco = $vinculo->precos->where('ativo', true)->first();
        [$data, $hora] = $this->primeiraVaga($vinculo);

        // A clínica deixou o preço em branco / o médico tirou a especialidade.
        $preco->update(['ativo' => false]);

        $this->comoPaciente()->post('/agendar', [
            'vinculo_id' => $vinculo->id, 'especialidade_id' => $preco->especialidade_id,
            'data_consulta' => $data, 'horario' => $hora,
            'forma_pagamento' => 'convenio', 'paciente_plano_id' => $carteirinha->id,
        ])->assertSessionHasErrors('especialidade_id');

        $this->assertSame(0, $this->contarAgendadas($vinculo, $data, $hora));
    }

    public function test_agendar_pela_clinica_com_data_invalida_nao_da_erro_500(): void
    {
        $clinica = User::where('email', 'contato@vidaplena.test')->first()->clinica;

        $this->comoPaciente()
            ->from('/clinica/' . $clinica->id)
            ->get('/agendar/clinica/' . $clinica->id . '/cardiologia?data=banana')
            ->assertRedirect('/clinica/' . $clinica->id)
            ->assertSessionHasErrors('data');
    }

    public function test_pagina_publica_da_clinica_esconde_medico_bloqueado(): void
    {
        $clinica = User::where('email', 'contato@vidaplena.test')->first()->clinica;

        $this->get('/clinica/' . $clinica->id)->assertOk()->assertSee('Helena');

        User::where('email', 'helena@facilmed.test')->update(['status' => 'bloqueado']);

        $this->get('/clinica/' . $clinica->id)->assertOk()->assertDontSee('Helena');
    }

    public function test_clinica_so_lista_especialidade_que_da_para_agendar(): void
    {
        $clinica = User::where('email', 'contato@vidaplena.test')->first()->clinica;
        $antes = $clinica->especialidades()->pluck('nome');
        $this->assertTrue($antes->contains('Cardiologia'));

        // Cardiologia sem preço ativo na Vida Plena: some da lista.
        $cardio = \App\Models\Especialidade::where('slug', 'cardiologia')->first();
        \App\Models\Preco::whereIn('vinculo_id', $clinica->vinculos()->select('vinculos.id'))
            ->where('especialidade_id', $cardio->id)->update(['ativo' => false]);

        $this->assertFalse($clinica->especialidades()->pluck('nome')->contains('Cardiologia'));
    }

    public function test_filtro_de_data_invalido_nas_consultas_do_admin_nao_da_erro_500(): void
    {
        $this->comoAdmin()->from('/admin/consultas')->get('/admin/consultas?de=banana')
            ->assertRedirect('/admin/consultas')
            ->assertSessionHasErrors('de');
    }

    public function test_paginacao_das_consultas_do_paciente_mantem_o_filtro(): void
    {
        $paciente = User::where('email', 'ana@facilmed.test')->first()->paciente;
        $base = Consulta::where('paciente_id', $paciente->id)->first();

        // Cria realizadas antigas suficientes para ter 2 páginas.
        for ($i = 1; $i <= 16; $i++) {
            Consulta::create($base->only(['paciente_id', 'medico_id', 'vinculo_id', 'especialidade_id', 'forma_pagamento', 'valor'])
                + ['data_consulta' => now()->subYears(2)->addDays($i)->toDateString(), 'horario' => '07:00',
                   'duracao_minutos' => 30, 'status' => 'realizada', 'origem' => 'medico']);
        }

        $this->comoPaciente()->get('/paciente/consultas?status=realizada')
            ->assertOk()
            ->assertSee('status=realizada&amp;page=2', false);
    }

    public function test_mensagens_de_erro_tem_acento(): void
    {
        $this->post('/cadastro/paciente', [
            'name' => 'Teste da Silva', 'email' => 'teste.acento@facilmed.test', 'cpf' => '111.111.111-11',
            'password' => 'senha-longa-123', 'password_confirmation' => 'outra-senha-123',
        ])->assertSessionHasErrors([
            'cpf'      => 'Esse CPF não é válido.',
            'password' => 'As duas senhas não são iguais.',
        ]);
    }
}
