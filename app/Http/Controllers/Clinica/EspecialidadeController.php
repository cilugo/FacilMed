<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Models\Especialidade;
use Illuminate\Http\Request;

/**
 * Clínica → Especialidades (01/10/2026, documento de modificações).
 *
 * A clínica cadastra os médicos dela; se a especialidade de um médico não
 * existe na plataforma, ela mesma cria, sem depender do admin. A clínica
 * só CRIA: renomear, destacar na home e desativar continuam com o admin,
 * porque a especialidade é de todas as clínicas.
 */
class EspecialidadeController extends Controller
{
    public function index()
    {
        $clinica = auth()->user()->clinica;

        return view('clinica.especialidades', [
            'especialidades' => Especialidade::where('ativo', true)->orderBy('nome')->get(),
            // As que esta clínica oferece hoje (para marcar na lista).
            'daClinica' => $clinica->especialidades()->pluck('especialidades.id')->all(),
        ]);
    }

    public function salvar(Request $request)
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'min:3', 'max:100'],
        ], [
            'nome.required' => 'Digite o nome da especialidade.',
            'nome.min'      => 'O nome precisa ter pelo menos 3 letras.',
        ]);

        $especialidade = Especialidade::criar($dados['nome']);

        return back()->with('sucesso', "Especialidade {$especialidade->nome} criada. Agora ela pode ser marcada no perfil dos médicos.");
    }
}
