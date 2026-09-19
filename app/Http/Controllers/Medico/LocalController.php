<?php

namespace App\Http\Controllers\Medico;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LocalController extends Controller
{
    /**
     * Onde o medico atende.
     *
     * Dois casos:
     *  - consultorio proprio (locais.medico_id preenchido): ele cria
     *    e edita aqui;
     *  - unidade de clinica: quem vincula e a clinica. O medico so ve.
     */
    public function index()
    {
        return view('medico.locais', [
            'vinculos' => auth()->user()->medico
                ->vinculos()->with('local.clinica', 'precos')->get(),
        ]);
    }

    public function salvar(Request $request)
    {
        // TODO: cria consultorio proprio + vinculo, numa transacao.
        // O local nasce com medico_id preenchido e clinica_id nulo -
        // o CHECK do banco garante que so um dos dois existe.
    }
}
