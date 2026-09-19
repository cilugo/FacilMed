<?php

namespace App\Http\Controllers;

use App\Models\Especialidade;
use App\Models\Local;
use App\Models\Medico;

class HomeController extends Controller
{
    /**
     * Home publica.
     *
     * Os cards de especialidade vem do banco (campo `destaque`), nunca
     * escritos no Blade: se a banca pedir para adicionar uma
     * especialidade ao vivo, ela precisa aparecer aqui sozinha.
     */
    public function index()
    {
        return view('home', [
            'especialidades' => Especialidade::emDestaque()->get(),

            // So medico verificado. O scope ja aplica a regra.
            'medicosDestaque' => Medico::visivel()
                ->with('user', 'especialidades')
                ->orderByDesc('media_avaliacoes')
                ->orderByDesc('total_avaliacoes')
                ->limit(6)
                ->get(),

            'cidades' => Local::where('ativo', true)
                ->select('cidade', 'uf')
                ->distinct()
                ->orderBy('cidade')
                ->get(),
        ]);
    }
}
