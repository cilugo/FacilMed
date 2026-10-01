<?php

namespace Tests\Feature;

use App\Models\Avaliacao;
use App\Models\Consulta;
use App\Models\Foto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * 01/10/2026 (plano novo do grupo): perfil do paciente com foto (guardada no
 * banco, só ele vê) e "Minhas avaliações" (editar e excluir as que ele fez).
 */
class PerfilPacienteTest extends TestCase
{
    /** PNG de 1x1 pixel (não precisa da extensão GD do PHP para os testes). */
    public static function png(string $nome = 'eu.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nome, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));
    }

    private function ana(): User
    {
        return User::where('email', 'ana@facilmed.test')->first();
    }

    // ---------------- Foto
    public function test_paciente_envia_troca_e_remove_a_foto(): void
    {
        $this->comoPaciente()->post('/paciente/perfil/foto', ['foto' => self::png()])->assertSessionHasNoErrors();
        $foto = Foto::where('paciente_id', $this->ana()->paciente->id)->firstOrFail();
        $this->assertSame('image/png', $foto->mime);

        // Mandar outra troca (continua uma só).
        $this->comoPaciente()->post('/paciente/perfil/foto', ['foto' => self::png('outra.png')])->assertSessionHasNoErrors();
        $this->assertSame(1, Foto::where('paciente_id', $this->ana()->paciente->id)->count());

        $this->comoPaciente()->get('/paciente/perfil')->assertOk()->assertSee('/foto/' . $foto->id, false)->assertSee('Remover foto');

        $this->comoPaciente()->delete('/paciente/perfil/foto')->assertSessionHas('sucesso');
        $this->assertSame(0, Foto::where('paciente_id', $this->ana()->paciente->id)->count());
    }

    public function test_foto_do_paciente_so_ele_ve(): void
    {
        $this->comoPaciente()->post('/paciente/perfil/foto', ['foto' => self::png()]);
        $foto = Foto::where('paciente_id', $this->ana()->paciente->id)->firstOrFail();

        $this->comoPaciente()->get("/foto/{$foto->id}")->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->comoPaciente('marcos@facilmed.test')->get("/foto/{$foto->id}")->assertNotFound();
        $this->comoClinica()->get("/foto/{$foto->id}")->assertNotFound();
        auth()->logout();
        $this->get("/foto/{$foto->id}")->assertNotFound();
    }

    public function test_foto_recusa_arquivo_que_nao_e_imagem_e_grande_demais(): void
    {
        // Arquivo de verdade (não o "fake" do Laravel, que acredita na extensão):
        // texto com nome de .jpg. O PHP lê o conteúdo e vê que não é imagem.
        $caminho = tempnam(sys_get_temp_dir(), 'fm');
        file_put_contents($caminho, 'não sou imagem, sou um texto qualquer');
        $falso = new UploadedFile($caminho, 'virus.jpg', null, null, true);
        $this->comoPaciente()->post('/paciente/perfil/foto', ['foto' => $falso])->assertSessionHasErrors('foto');
        $this->comoPaciente()->post('/paciente/perfil/foto', ['foto' => UploadedFile::fake()->create('grande.png', 3000, 'image/png')])
            ->assertSessionHasErrors('foto');
        $this->assertSame(0, Foto::whereNotNull('paciente_id')->count());
    }

    public function test_banco_garante_um_dono_por_foto(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        Foto::create(['mime' => 'image/png', 'conteudo' => 'x']);   // sem dono nenhum
    }

    // ---------------- Minhas avaliações
    private function avaliacaoDaAna(): Avaliacao
    {
        // O ConsultaSeeder sorteia o paciente: pega uma consulta realizada e passa para a Ana.
        $consulta = Consulta::where('status', 'realizada')->firstOrFail();
        $consulta->update(['paciente_id' => $this->ana()->paciente->id]);
        $consulta->avaliacao()?->delete();

        return Avaliacao::create([
            'consulta_id' => $consulta->id, 'paciente_id' => $consulta->paciente_id, 'medico_id' => $consulta->medico_id,
            'estrelas' => 2, 'comentario' => 'Esperei bastante na recepção.',
        ]);
    }

    public function test_historico_mostra_as_avaliacoes_com_o_comentario_do_autor(): void
    {
        $a = $this->avaliacaoDaAna();

        $this->comoPaciente()->get('/paciente/perfil')->assertOk()
            ->assertSee('Minhas avaliações')
            ->assertSee($a->medico->user->name)
            ->assertSee('Esperei bastante na recepção.');

        // Outro paciente não vê o comentário da Ana.
        $this->comoPaciente('marcos@facilmed.test')->get('/paciente/perfil')->assertOk()
            ->assertDontSee('Esperei bastante na recepção.');
    }

    public function test_paciente_edita_e_exclui_a_propria_avaliacao_e_a_media_acompanha(): void
    {
        $a = $this->avaliacaoDaAna();

        $this->comoPaciente()->put("/paciente/avaliacoes/{$a->id}", ['estrelas' => 5, 'comentario' => 'Resolveram rápido.'])
            ->assertSessionHas('sucesso');
        $this->assertSame(5, $a->fresh()->estrelas);
        $this->assertSame('Resolveram rápido.', $a->fresh()->comentario);
        $this->assertEquals($a->medico->avaliacoes()->avg('estrelas'), (float) $a->medico->fresh()->media_avaliacoes);

        $this->comoPaciente()->put("/paciente/avaliacoes/{$a->id}", ['estrelas' => 9])->assertSessionHasErrors('estrelas');

        $total = $a->medico->fresh()->total_avaliacoes;
        $this->comoPaciente()->delete("/paciente/avaliacoes/{$a->id}")->assertSessionHas('sucesso');
        $this->assertNull($a->fresh());
        $this->assertSame($total - 1, (int) $a->medico->fresh()->total_avaliacoes);
    }

    public function test_ninguem_mexe_na_avaliacao_de_outro(): void
    {
        $a = $this->avaliacaoDaAna();

        $this->comoPaciente('marcos@facilmed.test')->put("/paciente/avaliacoes/{$a->id}", ['estrelas' => 1])->assertForbidden();
        $this->comoPaciente('marcos@facilmed.test')->delete("/paciente/avaliacoes/{$a->id}")->assertForbidden();
        $this->comoMedico()->delete("/paciente/avaliacoes/{$a->id}")->assertForbidden();
        $this->assertSame(2, $a->fresh()->estrelas);
    }

    public function test_excluir_a_conta_apaga_a_foto(): void
    {
        $this->comoPaciente()->post('/paciente/perfil/foto', ['foto' => self::png()]);
        $this->assertSame(1, Foto::whereNotNull('paciente_id')->count());

        $this->comoPaciente()->delete('/paciente/perfil', ['current_password' => 'facilmed2026', 'confirmacao' => '1'])
            ->assertRedirect(route('login'));

        $this->assertSame(0, Foto::whereNotNull('paciente_id')->count());
    }
}
