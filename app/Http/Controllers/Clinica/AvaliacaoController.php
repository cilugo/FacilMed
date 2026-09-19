<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;

class AvaliacaoController extends Controller
{
    /**
     * Avaliacoes dos medicos da clinica, COM comentario - e uma das
     * tres telas autorizadas a ver.
     */
    public function index()
    {
        // TODO: buscar avaliacoes dos medicos vinculados a esta
        // clinica, com makeVisible('comentario').
        return view('clinica.avaliacoes');
    }
}
