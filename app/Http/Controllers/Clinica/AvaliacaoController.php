<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Models\Vinculo;
use App\Models\Avaliacao;

class AvaliacaoController extends Controller
{
    /**
     * Avaliacoes dos medicos da clinica, COM comentario - e uma das
     * tres telas autorizadas a ver.
     */
    public function index()
    {
        $clinica = auth()->user()->clinica;
        $vinculoIds = Vinculo::whereIn('local_id', $clinica->locais()->select('id'))->select('id');

        $base = Avaliacao::whereHas('consulta', fn ($q) => $q->whereIn('vinculo_id', $vinculoIds));

        return view('clinica.avaliacoes', [
            'media'      => round((float) (clone $base)->avg('estrelas'), 1),
            'total'      => (clone $base)->count(),
            'porMedico'  => (clone $base)->selectRaw('medico_id, AVG(estrelas) as media, COUNT(*) as total')
                ->groupBy('medico_id')->with('medico.user')->get(),
            'avaliacoes' => (clone $base)
                ->with('paciente.user', 'medico.user', 'consulta.especialidade', 'consulta.vinculo.local')
                ->latest()->paginate(20)
                ->through(fn ($a) => $a->makeVisible('comentario')),
        ]);
    }
}
