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
            'medicosDestaque' => Medico::visivel()
                ->with('user', 'especialidades')
                ->orderByDesc('media_avaliacoes')
                ->orderByDesc('total_avaliacoes')
                ->limit(6)
                ->get(),

            'locaisDestaque' => $this->locaisDestaque(),

            'cidades' => Local::where('ativo', true)
                ->select('cidade', 'uf')
                ->distinct()
                ->orderBy('cidade')
                ->get(),
        ]);
    }

    /**
     * 30/09: "Clínicas e hospitais bem avaliados" - o mesmo card dos médicos,
     * com a foto da fachada (locais.foto) e a nota.
     *
     * Entra quem aparece em "Locais perto de você" (Local::agendaveis: ativo,
     * dono no ar, com médico visível) e só clínica ou hospital - consultório de
     * médico autônomo fica de fora. Nota = Local::notas (média das avaliações
     * das consultas feitas ali). Ordem: maior nota, mais avaliações, nome.
     */
    private function locaisDestaque()
    {
        $locais = Local::agendaveis()->whereIn('tipo', ['clinica', 'hospital'])->get();
        $notas = Local::notas($locais->pluck('id'));

        return $locais
            ->map(fn (Local $local) => (object) ['local' => $local, 'nota' => $notas->get($local->id)])
            ->sortBy([
                fn ($a, $b) => (float) ($b->nota->media ?? 0) <=> (float) ($a->nota->media ?? 0),
                fn ($a, $b) => (int) ($b->nota->total ?? 0) <=> (int) ($a->nota->total ?? 0),
                fn ($a, $b) => $a->local->nome <=> $b->local->nome,
            ])
            ->take(6)
            ->values();
    }
}
