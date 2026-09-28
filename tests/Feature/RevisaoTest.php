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

    public function test_bloqueio_grava_motivo_autor_e_data_e_desbloqueio_limpa(): void
    {
        $admin  = User::where('email', 'admin@facilmed.test')->first();
        $marcos = User::where('email', 'marcos@facilmed.test')->first();

        $this->comoAdmin()->post("/admin/usuarios/{$marcos->id}/bloquear", [
            'motivo' => 'Conta usada para marcar consultas falsas', 'cancelar_consultas' => 1,
        ])->assertSessionHas('sucesso');

        $marcos->refresh();
        $this->assertSame('bloqueado', $marcos->status);
        $this->assertSame('Conta usada para marcar consultas falsas', $marcos->motivo_bloqueio);
        $this->assertSame($admin->id, (int) $marcos->bloqueado_por);
        $this->assertNotNull($marcos->bloqueado_em);

        $this->comoAdmin()->post("/admin/usuarios/{$marcos->id}/desbloquear")->assertSessionHas('sucesso');
        $marcos->refresh();
        $this->assertSame('ativo', $marcos->status);
        $this->assertNull($marcos->motivo_bloqueio);
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

    // -----------------------------------------------------------------
    // 2ª rodada da revisão de 28/09 (README §11, itens 27 a 29)
    // -----------------------------------------------------------------

    public function test_agendar_pela_clinica_nao_oferece_especialidade_desativada(): void
    {
        $clinica = User::where('email', 'contato@vidaplena.test')->first()->clinica;
        $cardio  = \App\Models\Especialidade::where('slug', 'cardiologia')->first();

        // Com a especialidade ativa, a clínica encaixa um médico e mostra horários.
        $this->comoPaciente()->get('/agendar/clinica/' . $clinica->id . '/cardiologia')
            ->assertOk()
            ->assertViewIs('agendamento.horario');

        // O admin desativa a especialidade.
        $cardio->update(['ativo' => false]);

        // Antes o alocador ainda achava a Helena e mostrava horários; o paciente
        // só era barrado na confirmação. Agora não há candidato: tela "sem vaga".
        $this->assertTrue(app(\App\Services\AlocadorDeMedico::class)->candidatos($clinica, $cardio->fresh())->isEmpty());

        $this->comoPaciente()->get('/agendar/clinica/' . $clinica->id . '/cardiologia')
            ->assertOk()
            ->assertViewIs('agendamento.sem-vaga');
    }

    public function test_avaliacao_enviada_duas_vezes_nao_da_erro_500(): void
    {
        $paciente = User::where('email', 'ana@facilmed.test')->first()->paciente;
        $base = Consulta::where('paciente_id', $paciente->id)->first();

        $consulta = Consulta::create($base->only(['paciente_id', 'medico_id', 'vinculo_id', 'especialidade_id', 'forma_pagamento', 'valor'])
            + ['data_consulta' => now()->subYear()->toDateString(), 'horario' => '07:00',
               'duracao_minutos' => 30, 'status' => 'realizada', 'origem' => 'medico']);

        // Duplo clique: a PRIMEIRA requisição grava a avaliação enquanto esta
        // (a segunda) já passou pela Policy e está a caminho do INSERT. O evento
        // "creating" roda bem nesse intervalo, então simula a outra requisição.
        \App\Models\Avaliacao::creating(function (\App\Models\Avaliacao $avaliacao) {
            \Illuminate\Support\Facades\DB::table('avaliacoes')->insert([
                'consulta_id' => $avaliacao->consulta_id,
                'paciente_id' => $avaliacao->paciente_id,
                'medico_id'   => $avaliacao->medico_id,
                'estrelas'    => $avaliacao->estrelas,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        });

        // Antes: o UNIQUE recusava o segundo INSERT e a pessoa via erro 500.
        $this->comoPaciente()->post('/paciente/consultas/' . $consulta->id . '/avaliar', ['estrelas' => 5])
            ->assertRedirect('/paciente/consultas')
            ->assertSessionHas('sucesso', 'Sua avaliação já estava registrada. Obrigado!');

        $this->assertSame(1, \App\Models\Avaliacao::where('consulta_id', $consulta->id)->count());
    }

    public function test_detalhe_da_consulta_por_convenio_mostra_o_aviso_da_recepcao(): void
    {
        $aviso = 'Confirme na recepção se o seu plano é aceito neste endereço.';

        $ana = User::where('email', 'ana@facilmed.test')->first();
        $carteirinha = PacientePlano::where('paciente_id', $ana->paciente->id)->where('status', 'ativa')->with('plano')->first();

        $vinculo = Vinculo::where('ativo', true)->where('aceita_convenio', true)->with('medico.convenios', 'precos')->get()
            ->first(fn ($v) => $v->medico->convenios->contains('id', $carteirinha->plano->convenio_id));
        $this->assertNotNull($vinculo, 'Deveria haver médico que aceita o convênio da Ana.');

        $esp = $vinculo->precos->where('ativo', true)->first()->especialidade_id;
        [$data, $hora] = $this->primeiraVaga($vinculo);

        $this->comoPaciente()->post('/agendar', [
            'vinculo_id' => $vinculo->id, 'especialidade_id' => $esp,
            'data_consulta' => $data, 'horario' => $hora,
            'forma_pagamento' => 'convenio', 'paciente_plano_id' => $carteirinha->id,
        ])->assertSessionHasNoErrors();

        $consulta = Consulta::where('paciente_id', $ana->paciente->id)->where('vinculo_id', $vinculo->id)
            ->whereDate('data_consulta', $data)->where('horario', $hora . ':00')->firstOrFail();

        // A tela que abre logo depois de agendar precisa repetir o aviso (AGENTS.md §3).
        $this->comoPaciente()->get('/paciente/consultas/' . $consulta->id)
            ->assertOk()
            ->assertSee($aviso);

        // Consulta particular não recebe o aviso.
        $consulta->update(['forma_pagamento' => 'particular', 'paciente_plano_id' => null, 'valor' => 250]);

        $this->comoPaciente()->get('/paciente/consultas/' . $consulta->id)
            ->assertOk()
            ->assertDontSee($aviso);
    }
}
