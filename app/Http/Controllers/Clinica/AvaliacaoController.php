<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Models\Avaliacao;
use App\Models\Medico;
use App\Models\Vinculo;

class AvaliacaoController extends Controller
{
    /**
     * Avaliações das unidades da clínica e dos médicos que atendem nelas,
     * COM comentário — uma das telas autorizadas a ver (clínica e admin;
     * o usuário vê só os dele). 01/10/2026: sem consulta, a avaliação é
     * direto no local ou no médico (Avaliacao::daClinica).
     */
    public function index()
    {
        $clinica = auth()->user()->clinica;
        $base = fn () => Avaliacao::daClinica($clinica->id);

        return view('clinica.avaliacoes', [
            'unidades' => $clinica->locais()->orderBy('nome')->get(['id', 'nome', 'media_avaliacoes', 'total_avaliacoes']),
            'medicos'  => Medico::whereIn('id', Vinculo::where('ativo', true)
                    ->whereIn('local_id', $clinica->locais()->select('id'))->select('medico_id'))
                ->where('total_avaliacoes', '>', 0)
                ->orderByDesc('media_avaliacoes')->get(['id', 'nome', 'media_avaliacoes', 'total_avaliacoes']),
            'total'      => $base()->count(),
            'avaliacoes' => $base()
                ->with('usuario.user', 'local', 'medico')
                ->latest('updated_at')->paginate(20)
                ->through(fn ($a) => $a->makeVisible('comentario')),
        ]);
    }
}
