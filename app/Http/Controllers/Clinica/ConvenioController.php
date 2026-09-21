<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConvenioController extends Controller
{
    /**
     * Convenios atendidos nas unidades da clinica.
     *
     * Lembrete: o vinculo real do convenio e com o MEDICO. Esta tela
     * mostra o conjunto dos convenios dos medicos vinculados, e serve
     * para a clinica ver a cobertura - nao cria vinculo proprio.
     */
    public function index()
    {
        // TODO
        return view('clinica.convenios');
    }

    public function salvar(Request $request)
    {
        // TODO
    }
}
