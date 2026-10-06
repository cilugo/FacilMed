<?php

namespace App\Http\Controllers\Usuario;

use App\Http\Controllers\Controller;
use App\Models\Especialidade;
use App\Models\Local;
use App\Support\Formatador;

class DashboardController extends Controller
{
    /**
     * Dashboard do usuário (refeito em 01/10/2026).
     *
     * Saíram "Próximos atendimentos" e "Consultas realizadas" (documento de
     * modificações): o PointMed não agenda mais. Ficou o que o usuário faz
     * agora: buscar locais perto dele, ver as avaliações que deu e o plano
     * de saúde (que serve de filtro na busca).
     *
     * O QUE NÃO ENTRA AQUI, por mais que apareça no mockup: "Resumo da sua
     * saúde", exames, medicamentos, receitas, atestados e documentos. Não há
     * dado para isso e não pode haver leitura clínica (AGENTS.md §1 e §3).
     */
    public function index()
    {
        $usuario  = auth()->user();
        $perfil = $usuario->usuario;

        $avaliacoes = $perfil->avaliacoes()->with('local', 'medico')->latest('updated_at')->get();

        $carteirinhas = $perfil->planos()->with('plano.convenio:id,nome')->latest()->get();
        $ativas = $carteirinhas->where('status', 'ativa');

        return view('usuario.dashboard', [
            'saudacao' => Formatador::saudacao($usuario->name),
            'dataHoje' => Formatador::dataExtensa(today()),

            'especialidades' => Especialidade::where('ativo', true)->orderBy('nome')->get(['nome', 'slug']),
            'cidades' => Local::where('ativo', true)->select('cidade', 'uf')->distinct()->orderBy('cidade')->get(),
            // O convênio da carteirinha ativa já vem marcado na busca rápida.
            'convenios' => \App\Models\Convenio::ativos()->orderBy('nome')->get(['id', 'nome']),
            'convenioDoPlano' => $ativas->first()?->plano?->convenio_id,

            'cartoes' => [
                // 05/10 (notas do grupo): terceiro cartão, para não sobrar espaço vazio.
                [
                    'icone'  => 'pin',
                    'tom'    => 'azul',
                    'rotulo' => 'Clínicas e hospitais',
                    'valor'  => Formatador::numero(Local::publicos()->count()),
                    'link'   => 'ver perto de você',
                    'url'    => route('busca.locais'),
                ],
                [
                    'icone'  => 'star',
                    'tom'    => 'roxo',
                    'rotulo' => 'Minhas avaliações',
                    'valor'  => Formatador::numero($avaliacoes->count()),
                    'link'   => 'ver todas',
                    'url'    => route('usuario.avaliacoes'),
                ],
                [
                    'icone'  => 'card',
                    'tom'    => 'rosa',
                    'rotulo' => 'Planos de saúde',
                    'valor'  => Formatador::numero($carteirinhas->count()),
                    'link'   => $carteirinhas->isEmpty() ? 'cadastrar plano' : $ativas->count() . ' ativo' . ($ativas->count() === 1 ? '' : 's'),
                    'url'    => route('usuario.planos'),
                ],
            ],

            'ultimas' => $avaliacoes->take(3),

            'carteirinhas' => $carteirinhas->map(function ($cp) {
                $status = Formatador::statusCarteirinha($cp->status);

                return [
                    'nome'   => ($cp->plano->convenio->nome ?? 'Convênio') . ' — ' . ($cp->plano->nome ?? ''),
                    'status' => $status['rotulo'],
                    'tom'    => $status['tom'],
                ];
            })->all(),

        ]);
    }
}
