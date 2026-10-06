<?php

namespace App\Http\Controllers\Usuario;

use App\Http\Controllers\Controller;
use App\Http\Requests\Usuario\AvaliarRequest;
use App\Models\Avaliacao;
use App\Models\Local;
use App\Models\Medico;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

/**
 * Avaliações do usuário (01/10/2026).
 *
 * Sem agendamento, a avaliação não depende mais de consulta: o usuário
 * logado avalia o LOCAL (na página do local) ou o MÉDICO (no perfil dele).
 * Uma por usuário em cada um — avaliar de novo EDITA a anterior. Quem
 * garante isso é o UNIQUE do banco; o updateOrCreate só evita o erro.
 *
 * O comentário é privado: o autor, a clínica avaliada e o admin leem.
 * Em tela pública aparece só a nota (AGENTS.md §3).
 */
class AvaliacaoController extends Controller
{
    public function index(Request $request)
    {
        return view('usuario.avaliacoes', [
            'avaliacoes' => $request->user()->usuario->avaliacoes()
                ->with('local', 'medico')
                ->latest('updated_at')
                ->paginate(15),
        ]);
    }

    public function avaliarLocal(AvaliarRequest $request, Local $local)
    {
        abort_unless($local->estaPublico(), 404);

        $nova = $this->gravar($request, ['local_id' => $local->id]);

        return back()->with('sucesso', $nova ? 'Obrigado! Sua avaliação do local foi publicada.' : 'Sua avaliação do local foi atualizada.');
    }

    public function avaliarMedico(AvaliarRequest $request, Medico $medico)
    {
        abort_unless(Medico::visivel()->whereKey($medico->id)->exists(), 404);

        $nova = $this->gravar($request, ['medico_id' => $medico->id]);

        return back()->with('sucesso', $nova ? 'Obrigado! Sua avaliação do médico foi publicada.' : 'Sua avaliação do médico foi atualizada.');
    }

    public function excluir(Request $request, Avaliacao $avaliacao)
    {
        $this->authorize('delete', $avaliacao);

        $avaliacao->delete();   // pelo model: recalcula a média do local/médico

        return back()->with('sucesso', 'Avaliação excluída.');
    }

    /**
     * Cria ou edita a avaliação deste usuário para o alvo. Devolve true se
     * criou. Se dois envios chegarem juntos, o segundo bate no UNIQUE e vira
     * edição — nunca duas avaliações.
     */
    private function gravar(AvaliarRequest $request, array $alvo): bool
    {
        $chave = ['usuario_id' => $request->user()->usuario->id] + $alvo;
        $dados = $request->safe()->only(['estrelas', 'comentario']);

        try {
            return Avaliacao::updateOrCreate($chave, $dados)->wasRecentlyCreated;
        } catch (UniqueConstraintViolationException) {
            Avaliacao::where($chave)->firstOrFail()->update($dados);

            return false;
        }
    }
}
