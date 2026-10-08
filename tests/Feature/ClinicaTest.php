<?php

namespace Tests\Feature;

use App\Models\Local;
use App\Models\Medico;
use App\Models\User;
use App\Models\Vinculo;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Back-end da clínica. Vida Plena = unidade (local) 2, onde a Dra. Helena
 * atende (vínculo 1). SpSaúde = local 1, Dr. Rafael (vínculo 3).
 * 01/10/2026: médico é perfil sem conta, mantido pela clínica.
 */
class ClinicaTest extends TestCase
{
    private function editarHelena(array $extra = [])
    {
        return $this->comoClinica()->put('/clinica/medicos/1', array_merge([
            'bio' => 'Nova apresentação.', 'anos_atuacao' => 15, 'telefone_profissional' => '(12) 3900-1000',
            'especialidades' => [2, 1], 'principal' => 2, 'convenios' => [1],
        ], $extra));
    }

    public function test_cadastra_medico_novo_sem_conta_de_acesso(): void
    {
        $antes = User::count();

        $this->novoMedico()->assertSessionHasNoErrors()->assertSessionMissing('senha_temporaria');

        $medico = Medico::where('crm', '445566')->firstOrFail();
        $this->assertSame('Dr. Paulo Yamada', $medico->nome);
        $this->assertSame('verificado', $medico->status_verificacao);
        $this->assertSame($antes, User::count(), 'Cadastrar médico não pode criar conta de acesso.');
        $this->assertTrue(Vinculo::where('medico_id', $medico->id)->where('local_id', 2)->value('aceita_convenio'));
    }

    public function test_desvincular_desativa_so_o_vinculo_da_propria_clinica(): void
    {
        $this->comoClinica()->delete('/clinica/medicos/vinculo/1')->assertSessionHas('sucesso');
        $this->assertFalse(Vinculo::find(1)->ativo);
        $this->assertNotNull(Medico::find(1), 'O perfil do médico continua existindo.');

        // Vínculo de outra clínica: proibido.
        $this->comoClinica()->delete('/clinica/medicos/vinculo/3')->assertForbidden();
    }

    public function test_clinica_edita_o_perfil_do_medico_que_atende_nela(): void
    {
        $this->editarHelena()->assertRedirect(route('clinica.medicos'));

        $helena = Medico::find(1)->load('especialidades', 'convenios');
        $this->assertSame('Nova apresentação.', $helena->bio);
        $this->assertSame(15, $helena->anos_atuacao);
        $this->assertSame('1239001000', $helena->telefone_profissional);
        $this->assertSame([1, 2], $helena->especialidades->pluck('id')->sort()->values()->all());
        $this->assertSame(2, $helena->especialidades->firstWhere('pivot.principal', true)->id);
        $this->assertSame([1], $helena->convenios->pluck('id')->all());
    }

    public function test_clinica_nao_edita_medico_que_nao_atende_nela(): void
    {
        // Dr. Rafael (id 2) atende na SpSaúde e no São Lucas, não na Vida Plena.
        $this->comoClinica()->get('/clinica/medicos/2/editar')->assertForbidden();
        $this->comoClinica()->put('/clinica/medicos/2', ['especialidades' => [4], 'bio' => 'invadido'])->assertForbidden();
        $this->assertNotSame('invadido', Medico::find(2)->bio);

        // Desvinculou: perde o direito de editar.
        $this->comoClinica()->delete('/clinica/medicos/vinculo/1');
        $this->comoClinica()->get('/clinica/medicos/1/editar')->assertForbidden();
    }

    public function test_foto_do_medico_so_aceita_imagem(): void
    {
        $this->editarHelena(['foto' => UploadedFile::fake()->create('virus.php', 10, 'text/x-php')])
            ->assertSessionHasErrors('foto');

        $this->editarHelena(['foto' => UploadedFile::fake()->image('helena.jpg', 200, 200)])->assertSessionHasNoErrors();
        // 07/10: a foto fica no banco, com o tipo real do arquivo.
        $chave = \App\Support\FotoDePerfil::chave(Medico::find(1)->foto);
        $this->assertNotNull($chave);
        $this->assertDatabaseHas('fotos', ['chave' => $chave, 'mime' => 'image/jpeg']);

    }

    public function test_clinica_cria_especialidade_e_nao_repete(): void
    {
        $this->comoClinica()->post('/clinica/especialidades', ['nome' => 'Reumatologia'])->assertSessionHas('sucesso');
        $this->assertDatabaseHas('especialidades', ['nome' => 'Reumatologia', 'slug' => 'reumatologia', 'ativo' => true]);

        // Mesmo slug com outra grafia: recusado com mensagem, sem erro 500.
        $this->comoClinica()->post('/clinica/especialidades', ['nome' => 'reumatologia '])->assertSessionHasErrors('nome');
        $this->comoClinica()->post('/clinica/especialidades', ['nome' => 'Cardiologia'])->assertSessionHasErrors('nome');

        // Usuário não cria.
        $this->comoUsuarioFinal()->post('/clinica/especialidades', ['nome' => 'Hepatologia'])->assertForbidden();
    }

    private function novoMedico(array $extra = [])
    {
        return $this->comoClinica()->post('/clinica/medicos', array_merge([
            'crm' => '445566', 'uf' => 'SP', 'local_id' => 2,
            'name' => 'Dr. Paulo Yamada', 'cpf' => '529.982.247-25',
            'especialidades' => [1], 'aceita_convenio' => 1,
        ], $extra));
    }

    public function test_crm_recusado_pela_base_nao_cadastra(): void
    {
        $this->novoMedico(['crm' => '998877'])->assertSessionHasErrors('crm');
        $this->assertNull(User::where('email', 'novo@teste.test')->first());
    }

    public function test_medico_que_ja_existe_so_ganha_vinculo(): void
    {
        // Dr. Rafael (223344/SP) passa a atender também na Vida Plena.
        $antes = Medico::count();
        $this->novoMedico(['crm' => '223344', 'name' => '', 'email' => '', 'cpf' => ''])->assertSessionHasNoErrors();

        $this->assertSame($antes, Medico::count());
        $this->assertTrue(Vinculo::where('medico_id', 2)->where('local_id', 2)->where('ativo', true)->exists());

        // Vincular de novo na mesma unidade é recusado.
        $this->novoMedico(['crm' => '223344', 'name' => '', 'email' => '', 'cpf' => ''])->assertSessionHasErrors('local_id');
    }

    public function test_nao_vincula_em_unidade_de_outra_clinica(): void
    {
        $this->novoMedico(['local_id' => 1])->assertSessionHasErrors('local_id');
    }

    public function test_nova_unidade_com_horarios(): void
    {
        $this->comoClinica()->post('/clinica/unidades', [
            'nome' => 'Vida Plena - Sul', 'tipo' => 'clinica', 'cep' => '12230-000', 'endereco' => 'Rua B', 'numero' => '5',
            'bairro' => 'Sul', 'cidade' => 'São José dos Campos', 'uf' => 'SP',
            'horarios' => ['segunda' => ['abre' => '07:00', 'fecha' => '19:00'], 'sabado' => ['abre' => '08:00', 'fecha' => '12:00']],
        ])->assertSessionHasNoErrors();

        $local = Local::where('nome', 'Vida Plena - Sul')->firstOrFail();
        $this->assertSame(User::where('email', 'contato@vidaplena.test')->first()->clinica->id, $local->clinica_id);
        $this->assertSame(2, $local->horarios()->count());
    }

    public function test_perfil_nao_muda_cnpj(): void
    {
        $clinica = User::where('email', 'contato@vidaplena.test')->first()->clinica;
        $cnpj = $clinica->cnpj;

        $this->comoClinica()->put('/clinica/perfil', ['name' => 'Nova Responsável', 'nome_fantasia' => 'Vida Plena Saúde', 'cnpj' => '00000000000000', 'telefone' => '(12) 3333-4444'])
            ->assertSessionHasNoErrors();

        $clinica->refresh();
        $this->assertSame('Vida Plena Saúde', $clinica->nome_fantasia);
        $this->assertSame($cnpj, $clinica->cnpj);
        $this->assertSame('1233334444', $clinica->telefone);
    }
}
