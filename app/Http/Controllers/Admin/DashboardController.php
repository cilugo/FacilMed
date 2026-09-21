<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consulta;
use App\Models\Medico;
use App\Models\PacientePlano;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * Painel do admin. O mockup de voces para esta tela estava certo -
     * quase tudo aqui sai do banco que ja existe.
     *
     * LACUNA CONHECIDA: o grafico "Acessos ao sistema" precisa de uma
     * tabela de log de acesso que NAO existe nas migrations. Duas
     * saidas: criar `logs_acesso` (id, user_id, ip, criado_em) e
     * gravar num listener do evento Login, ou tirar o grafico da tela.
     * Decidam antes de o front construir o card.
     */
    public function index()
    {
        return view('admin.dashboard', [
            'totalUsuarios'  => User::count(),
            'totalPacientes' => User::where('tipo', User::TIPO_PACIENTE)->count(),
            'totalMedicos'   => User::where('tipo', User::TIPO_MEDICO)->count(),
            'totalClinicas'  => User::where('tipo', User::TIPO_CLINICA)->count(),

            'consultasRealizadas' => Consulta::where('status', 'realizada')->count(),
            'consultasAgendadas'  => Consulta::agendadas()->count(),

            // Alimenta o donut "distribuicao por especialidade".
            'porEspecialidade' => Consulta::query()
                ->join('especialidades', 'especialidades.id', '=', 'consultas.especialidade_id')
                ->selectRaw('especialidades.nome, COUNT(*) as total')
                ->groupBy('especialidades.nome')
                ->orderByDesc('total')
                ->get(),

            'porStatus' => Consulta::selectRaw('status, COUNT(*) as total')
                ->groupBy('status')->pluck('total', 'status'),

            // Filas que exigem acao humana - o mais importante da tela.
            'crmPendentes'         => Medico::where('status_verificacao', 'pendente')->count(),
            'carteirinhasPendentes'=> PacientePlano::where('status', 'pendente')->count(),

            'ultimosCadastros' => User::latest()->limit(8)->get(),
        ]);
    }
}
