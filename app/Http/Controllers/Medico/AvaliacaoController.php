<?php

namespace App\Http\Controllers\Medico;

use App\Http\Controllers\Controller;

class AvaliacaoController extends Controller
{
    /**
     * Uma das TRES telas onde o comentario aparece (as outras sao a da
     * clinica e a do admin). Em qualquer outro lugar, so a nota.
     *
     * O campo `comentario` esta em $hidden no model - aqui e preciso
     * trazer explicitamente com makeVisible().
     */
    public function index()
    {
        $medico = auth()->user()->medico;

        return view('medico.avaliacoes', [
            'medico' => $medico,
            'avaliacoes' => $medico->avaliacoes()
                ->with('paciente.user', 'consulta.especialidade')
                ->latest()
                ->paginate(20)
                ->through(fn ($a) => $a->makeVisible('comentario')),
        ]);
    }
}
