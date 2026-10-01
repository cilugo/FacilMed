<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Models\Vinculo;
use App\Models\Avaliacao;
use App\Models\AvaliacaoLocal;

class AvaliacaoController extends Controller
{
    /**
     * Avaliacoes dos medicos da clinica, COM comentario - e uma das
     * tres telas autorizadas a ver.
     *
     * 01/10/2026 (plano novo do grupo): tambem as avaliacoes das UNIDADES
     * (qualquer paciente logado avalia o local). A "Nota da clinica" passa a
     * ser a media delas - a mesma conta da busca e da pagina do local.
     */
    public function index()
    {
        $clinica = auth()->user()->clinica;
        $vinculoIds = Vinculo::whereIn('local_id', $clinica->locais()->select('id'))->select('id');

        $base = Avaliacao::whereHas('consulta', fn ($q) => $q->whereIn('vinculo_id', $vinculoIds));

        $doLocal = AvaliacaoLocal::whereIn('local_id', $clinica->locais()->select('id'));

        return view('clinica.avaliacoes', [
            'media'      => round((float) (clone $doLocal)->avg('estrelas'), 1),
            'total'      => (clone $doLocal)->count(),
            'avaliacoesLocais' => (clone $doLocal)
                ->with('paciente.user', 'local')
                ->latest('updated_at')->paginate(20, ['*'], 'pagina_locais')
                ->through(fn ($a) => $a->makeVisible('comentario')),
            'porMedico'  => (clone $base)->selectRaw('medico_id, AVG(estrelas) as media, COUNT(*) as total')
                ->groupBy('medico_id')->with('medico.user')->get(),
            'avaliacoes' => (clone $base)
                ->with('paciente.user', 'medico.user', 'consulta.especialidade', 'consulta.vinculo.local')
                ->latest()->paginate(20)
                ->through(fn ($a) => $a->makeVisible('comentario')),
        ]);
    }
}
