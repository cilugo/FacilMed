<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Models\Local;
use Illuminate\Http\Request;

class UnidadeController extends Controller
{
    public function index()
    {
        return view('clinica.unidades', [
            'locais' => auth()->user()->clinica->locais()->with('horarios')->get(),
        ]);
    }

    public function salvar(Request $request)
    {
        // TODO: SalvarUnidadeRequest. O local nasce com clinica_id
        // preenchido e medico_id nulo.
    }

    /**
     * Horario de FUNCIONAMENTO do lugar - nao confundir com a
     * disponibilidade do medico. O bloco de agenda de um medico nao
     * pode cair fora do funcionamento da unidade.
     */
    public function salvarHorarios(Request $request, Local $local)
    {
        $this->authorize('update', $local);
        // TODO
    }
}
