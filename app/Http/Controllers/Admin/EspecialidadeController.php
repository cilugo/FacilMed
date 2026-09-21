<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Especialidade;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class EspecialidadeController extends Controller
{
    public function index()
    {
        return view('admin.especialidades', [
            'especialidades' => Especialidade::withCount('medicos')->orderBy('nome')->get(),
        ]);
    }

    public function salvar(Request $request)
    {
        $dados = $request->validate([
            'nome'     => ['required', 'string', 'max:100', 'unique:especialidades,nome'],
            'icone'    => ['nullable', 'string', 'max:60'],
            'destaque' => ['boolean'],
        ]);

        Especialidade::create([...$dados, 'slug' => Str::slug($dados['nome']), 'ativo' => true]);

        return back()->with('sucesso', 'Especialidade criada.');
    }

    /**
     * `destaque` controla os cards da home. Marcar aqui faz o card
     * aparecer na home na hora - sem mexer em codigo.
     */
    public function atualizar(Request $request, Especialidade $especialidade)
    {
        // TODO
        // CUIDADO ao desativar: especialidade com medico vinculado e
        // consulta futura nao pode sumir da tela do paciente.
    }
}
