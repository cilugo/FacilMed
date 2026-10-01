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

            // 01/10/2026: conta só clínica/hospital que aparece na busca (a
            // sugestão do README §6: antes contava todo local ativo, até de
            // clínica bloqueada).
            'totais' => [
                'medicos'  => Medico::visivel()->count(),
                'unidades' => Local::agendaveis()->count(),
                'cidades'  => Local::agendaveis()->distinct()->count('cidade'),
            ],

            // 01/10/2026: "Hospitais e Clínicas" vem do BANCO (antes era uma
            // lista fixa no Blade, com um hospital que não existia e endereços
            // diferentes dos cadastrados - README §6, "Para o grupo olhar").
            'locaisDestaque' => $this->locaisDestaque(),

            // So medico verificado. O scope ja aplica a regra.
            'medicosDestaque' => Medico::visivel()
                ->with('user', 'especialidades', 'fotoEnviada')
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

    /** Até 8 locais que recebem agendamento, os mais bem avaliados primeiro, com foto e nota. */
    private function locaisDestaque()
    {
        $locais = Local::agendaveis()->with('fotos', 'clinica')->get();
        $notas = Local::notas($locais->pluck('id'));

        return $locais
            ->map(fn (Local $l) => (object) ['local' => $l, 'nota' => $notas->get($l->id)])
            ->sortByDesc(fn ($x) => [(float) ($x->nota->media ?? 0), (int) ($x->nota->total ?? 0)])
            ->take(8)
            ->values();
    }
}
