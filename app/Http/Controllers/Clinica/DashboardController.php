<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $clinica = auth()->user()->clinica;

        return view('clinica.dashboard', [
            'clinica'      => $clinica,
            'totalMedicos' => $clinica->vinculos()->where('vinculos.ativo', true)->count(),
            'unidades'     => $clinica->locais()->where('ativo', true)->count(),
            // TODO: consultas de hoje e da semana, somando todos os
            // vinculos das unidades desta clinica.
        ]);
    }
}
