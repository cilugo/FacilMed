<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    /**
     * Agenda consolidada: todas as unidades e todos os medicos da
     * clinica, com filtro por unidade, medico e especialidade.
     */
    public function index(Request $request)
    {
        // TODO
        return view('clinica.agenda');
    }
}
