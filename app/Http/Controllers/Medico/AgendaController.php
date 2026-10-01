<?php

namespace App\Http\Controllers\Medico;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    /**
     * Agenda do dia (?data=AAAA-MM-DD, ?vinculo=id). Traz também a
     * acessibilidade do paciente: aqui PODE, porque é o médico daquela
     * consulta (AGENTS.md §3) — mas a tela só mostra nas consultas ainda
     * AGENDADAS (ConsultaPolicy::verAcessibilidade).
     *
     * 01/10/2026 (plano novo do grupo): só leitura. Realizada, falta e
     * cancelar passaram para a agenda da clínica (Clinica\AgendaController).
     */
    public function index(Request $request)
    {
        $medico = auth()->user()->medico;
        $data = $request->date('data') ?? today();

        $consultas = $medico->consultas()
            ->whereDate('data_consulta', $data)
            ->when($request->integer('vinculo'), fn ($q, $v) => $q->where('vinculo_id', $v))
            ->with('paciente.user', 'paciente.acessibilidade', 'especialidade', 'vinculo.local', 'pacientePlano.plano.convenio')
            ->orderBy('horario')->get();

        return view('medico.agenda', [
            'data'      => $data,
            'anterior'  => $data->copy()->subDay()->toDateString(),
            'seguinte'  => $data->copy()->addDay()->toDateString(),
            'vinculos'  => $medico->vinculos()->with('local')->where('ativo', true)->get(),
            'consultas' => $consultas,
            'resumo'    => [
                'agendadas'  => $consultas->where('status', 'agendada')->count(),
                'realizadas' => $consultas->where('status', 'realizada')->count(),
                'canceladas' => $consultas->where('status', 'cancelada')->count(),
                'faltas'     => $consultas->where('status', 'nao_compareceu')->count(),
            ],
        ]);
    }
}
