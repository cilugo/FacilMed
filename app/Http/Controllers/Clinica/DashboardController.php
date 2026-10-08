<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Models\Avaliacao;
use App\Models\Medico;
use App\Support\Formatador;

class DashboardController extends Controller
{
    /**
     * Dashboard da clínica (refeito em 01/10/2026).
     *
     * Sem agendamento, saíram os números de consultas (gráfico, agenda do
     * dia, consultas por convênio). Ficou o que a clínica administra agora:
     * médicos, unidades, especialidades, a nota que os usuários dão e as
     * unidades sem faixa de preço informada (05/10).
     *
     * Tudo preso à clínica logada (auth()->user()->clinica).
     */
    public function index()
    {
        $usuario = auth()->user();
        $clinica = $usuario->clinica;

        $unidades = $clinica->locais()->where('ativo', true)->orderBy('nome')->get();

        $vinculos = $clinica->vinculos()
            ->where('vinculos.ativo', true)
            ->with('medico.especialidades', 'local')
            ->get();

        $medicos = Medico::whereIn('id', $vinculos->pluck('medico_id')->unique())->with('especialidades')->get();

        // Médicos por especialidade principal (barras).
        $porEspecialidade = $medicos
            ->groupBy(fn ($m) => ($m->especialidades->firstWhere('pivot.principal', true) ?? $m->especialidades->first())?->nome ?? 'Sem especialidade')
            ->map(fn ($grupo, $nome) => ['nome' => $nome, 'total' => $grupo->count()])
            ->sortByDesc('total')->values();
        $maior = max(1, (int) $porEspecialidade->max('total'));
        $soma  = max(1, (int) $porEspecialidade->sum('total'));
        $porEspecialidade = $porEspecialidade->map(fn ($e) => $e + [
            'pct'     => (int) round($e['total'] / $soma * 100),
            'largura' => (int) round($e['total'] / $maior * 100),
        ])->all();

        // Pendência (05/10): unidade sem faixa de preço informada.
        $semFaixa = $unidades->whereNull('faixa_preco')->values();

        $avaliadas = $unidades->where('total_avaliacoes', '>', 0);
        $notaMedia = $avaliadas->sum('total_avaliacoes') > 0
            ? $avaliadas->sum(fn ($u) => (float) $u->media_avaliacoes * $u->total_avaliacoes) / $avaliadas->sum('total_avaliacoes')
            : null;

        $ultimas = Avaliacao::daClinica($clinica->id)
            ->with('usuario.user', 'local', 'medico')
            ->latest('updated_at')->limit(4)->get()
            ->each->makeVisible('comentario');

        return view('clinica.dashboard', [
            'saudacao'    => $clinica->nome_fantasia,
            'dataHoje'    => Formatador::dataExtensa(today()),

            'cartoes' => [
                [
                    'icone'  => 'doctors',
                    'tom'    => 'azul',
                    'rotulo' => 'Médicos',
                    'valor'  => Formatador::numero($medicos->count()),
                    'link'   => 'meus médicos',
                    'url'    => route('clinica.medicos'),
                ],
                [
                    'icone'  => 'building',
                    'tom'    => 'verde',
                    'rotulo' => 'Unidades ativas',
                    'valor'  => Formatador::numero($unidades->count()),
                    'link'   => 'ver unidades',
                    'url'    => route('clinica.unidades'),
                ],
                [
                    'icone'  => 'star',
                    'tom'    => 'roxo',
                    'rotulo' => 'Nota das unidades',
                    'valor'  => $notaMedia !== null ? Formatador::numero($notaMedia, 1) : '—',
                    'link'   => 'ver avaliações',
                    'url'    => route('clinica.avaliacoes'),
                ],
                [
                    'icone'  => 'tag',
                    'tom'    => 'rosa',
                    'rotulo' => 'Especialidades oferecidas',
                    'valor'  => Formatador::numero($clinica->especialidades()->count()),
                    'link'   => 'especialidades',
                    'url'    => route('clinica.especialidades'),
                ],
            ],

            'unidades'         => $unidades,
            'porEspecialidade' => $porEspecialidade,
            'semFaixa'         => $semFaixa,
            'ultimas'          => $ultimas,
        ]);
    }
}
