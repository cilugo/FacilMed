<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinica;

class ClinicaController extends Controller
{
    public function index()
    {
        return view('admin.clinicas', [
            'clinicas' => Clinica::with('user')
                ->withCount('locais')
                ->orderBy('nome_fantasia')
                ->paginate(20),
        ]);
    }
}
