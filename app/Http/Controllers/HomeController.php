<?php

namespace App\Http\Controllers;

use App\Models\Especialidade;
use App\Models\Local;
use App\Models\Medico;

class HomeController extends Controller
{
    /**
     * 07/10/2026: imagem de cada local na vitrine "Hospitais e Clínicas" da
     * home, pelo NOME do local (o mesmo de DadosFicticios::ESTABELECIMENTOS).
     * Arquivos em public/imgs/inst/. Local sem imagem aqui (ex.: uma unidade
     * nova cadastrada pela clínica) aparece com o ícone de prédio/hospital.
     * Para pôr imagem num local novo: salve o arquivo em imgs/inst e acrescente
     * a linha aqui.
     */
    public const IMAGENS_DA_VITRINE = [
        'Santa Clara - Taubaté'     => 'imgs/inst/santaclara.jpg',
        'Vida Plena - Centro'       => 'imgs/inst/vidaplena.jpg',
        'SpSaúde - Jardim Satélite' => 'imgs/inst/spsaude.jpg',
        'Aurora - Vila Ema'         => 'imgs/inst/aurora.jpg',
        'São Lucas - Jacareí'       => 'imgs/inst/saolucas.jpg',
        'Esperança - Caçapava'      => 'imgs/inst/esperanca.jpg',
    ];

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

            // 07/10/2026: "Hospitais e Clínicas" vem do BANCO (antes era uma lista
            // fixa no Blade, com o "Hospital Vale Sereno", que não existe, e
            // endereços diferentes dos cadastrados - README §6).
            'locaisDestaque' => $this->locaisDestaque(),
        ]);
    }

    /**
     * Até 8 locais públicos (Local::publicos: ativos e com médico visível), os
     * mais bem avaliados primeiro, cada um com a imagem da vitrine (ou null).
     */
    private function locaisDestaque()
    {
        return Local::publicos()
            ->orderByDesc('media_avaliacoes')
            ->orderByDesc('total_avaliacoes')
            ->orderBy('nome')
            ->limit(8)
            ->get()
            ->each(fn (Local $l) => $l->imagem_vitrine = isset(self::IMAGENS_DA_VITRINE[$l->nome])
                ? asset(self::IMAGENS_DA_VITRINE[$l->nome])
                : null);
    }
}
