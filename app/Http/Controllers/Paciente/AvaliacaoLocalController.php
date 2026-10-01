<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Models\AvaliacaoLocal;
use App\Models\Local;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

/**
 * Avaliação do LOCAL (01/10/2026, plano novo do grupo: "a clínica é a linha
 * de frente; também deve ser possível avaliar clínicas").
 *
 * Decisões do Sidney (01/10): qualquer paciente logado avalia, uma vez por
 * local; pode editar (mandar de novo troca a nota) ou excluir; o comentário é
 * privado (clínica dona e admin - e o próprio autor, no perfil).
 */
class AvaliacaoLocalController extends Controller
{
    public function salvar(Request $request, Local $local)
    {
        // Só local que aparece para o público (ativo e com o dono no ar).
        abort_unless($local->estaPublico(), 404);

        $dados = $request->validate([
            'estrelas'   => ['required', 'integer', 'between:1,5'],
            'comentario' => ['nullable', 'string', 'max:1000'],
        ], ['estrelas.*' => 'Escolha de 1 a 5 estrelas.']);

        $chave = ['paciente_id' => $request->user()->paciente->id, 'local_id' => $local->id];
        $ja = AvaliacaoLocal::where($chave)->exists();

        try {
            AvaliacaoLocal::updateOrCreate($chave, $dados + ['comentario' => null]);
        } catch (QueryException $e) {
            // Duplo clique: as duas requisições não acharam avaliação e as duas
            // tentaram criar; o UNIQUE (paciente, local) recusou a segunda. A
            // primeira já gravou - então é só atualizar.
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            AvaliacaoLocal::where($chave)->update($dados + ['comentario' => $dados['comentario'] ?? null]);
        }

        $volta = $request->input('voltar') === 'perfil'
            ? route('paciente.perfil') . '#avaliacoes'
            : route('publico.local', $local) . '#avaliar';

        return redirect()->to($volta)->with('sucesso', $ja ? 'Avaliação atualizada.' : 'Obrigado pela avaliação!');
    }

    public function excluir(AvaliacaoLocal $avaliacaoLocal)
    {
        $this->authorize('delete', $avaliacaoLocal);

        $avaliacaoLocal->delete();

        return redirect()->to(route('paciente.perfil') . '#avaliacoes')->with('sucesso', 'Avaliação excluída.');
    }
}
