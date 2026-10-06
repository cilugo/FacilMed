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
            // 24/09: com a contagem de medicos visiveis, para o card mostrar
            // "3 medicos" e a home nao oferecer especialidade vazia como destaque.
            'especialidades' => Especialidade::where('ativo', true)
                ->withCount(['medicos' => fn ($q) => $q->visivel()])
                ->orderByDesc('destaque')
                ->orderByDesc('medicos_count')
                ->orderBy('nome')
                ->get(),

            'totais' => [
                'medicos'  => Medico::visivel()->count(),
                'unidades' => Local::where('ativo', true)->count(),
                'cidades'  => Local::where('ativo', true)->distinct()->count('cidade'),
            ],

            // So medico verificado. O scope ja aplica a regra.
            // 05/10: com o comentário mais recente de cada um (comentário é público).
            'medicosDestaque' => Medico::visivel()
                ->with([
                    'especialidades',
                    'avaliacoes' => fn ($a) => $a->whereNotNull('comentario')->latest('updated_at')->limit(1),
                    'avaliacoes.usuario.user:id,name',
                ])
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
