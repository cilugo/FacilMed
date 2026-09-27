<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Clinica;

class ClinicaController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.clinicas', [
            'clinicas' => Clinica::with('user', 'locais:id,clinica_id,nome,tipo,cidade,uf,ativo')
                ->withCount('locais')
                ->when($request->busca, fn ($q, $b) => $q->where(fn ($s) => $s
                    ->where('nome_fantasia', 'like', "%{$b}%")->orWhere('razao_social', 'like', "%{$b}%")))
                ->orderBy('nome_fantasia')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }
}
