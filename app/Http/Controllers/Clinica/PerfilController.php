<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PerfilController extends Controller
{
    public function edit()
    {
        return view('clinica.perfil', ['clinica' => auth()->user()->clinica]);
    }

    public function update(Request $request)
    {
        // TODO: AtualizarPerfilClinicaRequest. CNPJ nao se edita
        // livremente depois de cadastrado.
    }
}
