<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PrecoController extends Controller
{
    /**
     * A tabela de precos da clinica: uma linha por
     * medico x unidade x especialidade.
     *
     * E a clinica quem define, porque o local pertence a ela.
     */
    public function index()
    {
        return view('clinica.precos', [
            'vinculos' => auth()->user()->clinica
                ->vinculos()
                ->with('medico.user', 'medico.especialidades', 'local', 'precos.especialidade')
                ->get(),
        ]);
    }

    public function salvar(Request $request)
    {
        // TODO: aceitar varios precos de uma vez (a tela e uma grade).
        // A Policy precisa checar que o vinculo pertence a uma unidade
        // DESTA clinica.
    }
}
