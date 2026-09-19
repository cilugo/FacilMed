<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Models\Avaliacao;
use App\Models\Consulta;
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

        Avaliacao::create([
            'consulta_id' => $consulta->id,
            'paciente_id' => $consulta->paciente_id,
            'medico_id'   => $consulta->medico_id,
            ...$dados,
        ]);

        // A media do medico se recalcula sozinha (evento no model).

        return redirect()->route('paciente.consultas')
            ->with('sucesso', 'Obrigado pela avaliacao.');
    }
}
