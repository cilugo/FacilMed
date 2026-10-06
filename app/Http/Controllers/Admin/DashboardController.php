<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Avaliacao;
use App\Models\BaseCnpj;
use App\Models\Clinica;
use App\Models\Local;
use App\Models\Medico;
use App\Models\User;
use App\Support\Formatador;

class DashboardController extends Controller
{
    /**
     * Dashboard do admin (refeito em 01/10/2026, documento de modificações).
     *
     * Saiu tudo de consultas (gráfico, consultas por unidade e por situação,
     * agendamentos, atividade recente). Ficou o retrato da plataforma:
     * clínicas, médicos, usuários, avaliações, CNPJs com problema, as
     * clínicas e médicos mais bem avaliados e os últimos cadastros.
     */
    public function index()
    {
        $usuario = auth()->user();
        $hoje = today();
        $inicioMes = $hoje->copy()->startOfMonth();
        $inicioMesAnt = $inicioMes->copy()->subMonth();
        $mesPassadoFim = $inicioMesAnt->copy()->addDays($hoje->day - 1)->min($inicioMesAnt->copy()->endOfMonth());

        $novos = fn (string $tipo, $de, $ate) => User::where('tipo', $tipo)
            ->whereBetween('created_at', [$de->copy()->startOfDay(), $ate->copy()->endOfDay()])->count();

        $clinicas = Clinica::with('user')->get();
        $cnpjsAtivos = BaseCnpj::whereIn('cnpj', $clinicas->pluck('cnpj'))->where('situacao', 'ativa')->count();

        return view('admin.dashboard', [
            'saudacao' => Formatador::saudacao($usuario->name),
            'dataHoje' => Formatador::dataExtensa($hoje),

            'cartoes' => [
                [
                    'icone' => 'building', 'tom' => 'azul', 'rotulo' => 'Clínicas ativas',
                    'valor' => Formatador::numero($clinicas->filter(fn ($c) => $c->user?->estaAtivo())->count()),
                    'nota' => Local::where('ativo', true)->count() . ' unidades de atendimento',
                    'url' => route('admin.clinicas'),
                ],
                [
                    'icone' => 'doctors', 'tom' => 'verde', 'rotulo' => 'Médicos',
                    'valor' => Formatador::numero(Medico::visivel()->count()),
                    'nota' => 'cadastrados pelas clínicas',
                    'url' => route('admin.clinicas'),
                ],
                [
                    'icone' => 'users', 'tom' => 'roxo', 'rotulo' => 'Usuários cadastrados',
                    'valor' => Formatador::numero(User::where('tipo', 'usuario')->whereNull('excluida_em')->count()),
                    'variacao' => Formatador::variacao($novos('usuario', $inicioMes, $hoje), $novos('usuario', $inicioMesAnt, $mesPassadoFim)),
                    'nota' => 'cadastros no mês x mês anterior',
                    'url' => route('admin.usuarios', ['tipo' => 'usuario']),
                ],
                [
                    'icone' => 'star', 'tom' => 'ambar', 'rotulo' => 'Avaliações',
                    'valor' => Formatador::numero(Avaliacao::count()),
                    'nota' => 'de locais e médicos',
                ],
                [
                    'icone' => 'badge', 'tom' => $clinicas->count() - $cnpjsAtivos > 0 ? 'rosa' : 'ciano', 'rotulo' => 'CNPJ com problema',
                    'valor' => Formatador::numero($clinicas->count() - $cnpjsAtivos),
                    'nota' => 'na base simulada do PointMed',
                    'url' => route('admin.cnpjs', ['situacao' => 'problema']),
                ],
            ],

            'locais' => Local::where('ativo', true)->where('total_avaliacoes', '>', 0)
                ->orderByDesc('media_avaliacoes')->orderByDesc('total_avaliacoes')->limit(5)->get(),

            'medicos' => Medico::visivel()->where('total_avaliacoes', '>', 0)
                ->orderByDesc('media_avaliacoes')->orderByDesc('total_avaliacoes')->limit(5)->get(),

            'ultimosCadastros' => User::latest()->limit(6)->get(),

            // O admin é uma das telas autorizadas a ler o comentário (AGENTS.md §3).
            'ultimasAvaliacoes' => Avaliacao::with('usuario.user', 'local', 'medico')
                ->latest('updated_at')->limit(5)->get()->each->makeVisible('comentario'),
        ]);
    }
}
