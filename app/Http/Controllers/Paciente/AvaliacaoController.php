<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Models\Avaliacao;
use App\Models\Consulta;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class AvaliacaoController extends Controller
{
    /**
     * Avaliacao: 1 a 5 estrelas + comentario opcional.
     *
     * Duas regras que a Policy precisa garantir:
     *  - so o paciente DAQUELA consulta avalia;
     *  - so consulta com status 'realizada'. Quem levou
     *    'nao_compareceu' nao avalia.
     *
     * O comentario e PRIVADO: vai para o medico avaliado, a clinica
     * dele e o admin. O paciente e o publico veem so a nota.
     *
     * O unique em consulta_id garante uma avaliacao so, no banco.
     */
    public function form(Consulta $consulta)
    {
        $this->authorize('avaliar', $consulta);

        return view('paciente.avaliacao', compact('consulta'));
    }

    public function salvar(Request $request, Consulta $consulta)
    {
        $this->authorize('avaliar', $consulta);

        $dados = $request->validate([
            'estrelas'   => ['required', 'integer', 'between:1,5'],
            'comentario' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            Avaliacao::create([
                'consulta_id' => $consulta->id,
                'paciente_id' => $consulta->paciente_id,
                'medico_id'   => $consulta->medico_id,
                ...$dados,
            ]);
        } catch (QueryException $e) {
            // 28/09 (2ª revisão): duplo clique em "Enviar avaliação". As duas
            // requisições passam pela Policy antes de qualquer uma gravar, e o
            // UNIQUE em avaliacoes.consulta_id recusa a segunda (erro 23000) -
            // a pessoa via erro 500 com a avaliação já salva. Se a avaliação
            // desta consulta existe, é esse o caso: responde como sucesso.
            // Qualquer outro erro de banco continua subindo.
            if ($e->getCode() === '23000' && Avaliacao::where('consulta_id', $consulta->id)->exists()) {
                return redirect()->route('paciente.consultas')
                    ->with('sucesso', 'Sua avaliação já estava registrada. Obrigado!');
            }

            throw $e;
        }

        // A media do medico se recalcula sozinha (evento no model).

        return redirect()->route('paciente.consultas')
            ->with('sucesso', 'Obrigado pela avaliação!');
    }

    /**
     * 01/10/2026: editar a avaliação pelo histórico do perfil. Só o autor
     * (AvaliacaoPolicy). A média do médico se recalcula sozinha (evento saved).
     */
    public function atualizar(Request $request, Avaliacao $avaliacao)
    {
        $this->authorize('update', $avaliacao);

        $dados = $request->validate([
            'estrelas'   => ['required', 'integer', 'between:1,5'],
            'comentario' => ['nullable', 'string', 'max:1000'],
        ], ['estrelas.*' => 'Escolha de 1 a 5 estrelas.']);

        $avaliacao->update($dados);

        return redirect()->to(route('paciente.perfil') . '#avaliacoes')->with('sucesso', 'Avaliação atualizada.');
    }

    /** Excluir a avaliação. A média do médico se recalcula (evento deleted). */
    public function excluir(Avaliacao $avaliacao)
    {
        $this->authorize('delete', $avaliacao);

        $avaliacao->delete();

        return redirect()->to(route('paciente.perfil') . '#avaliacoes')->with('sucesso', 'Avaliação excluída.');
    }
}
