<?php

namespace App\Http\Controllers\Medico;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $medico = auth()->user()->medico;

        return view('medico.dashboard', [
            'medico' => $medico,

            'hoje' => $medico->consultas()
                ->whereDate('data_consulta', today())
                ->whereIn('status', ['agendada', 'realizada'])
                ->with('paciente.user', 'especialidade', 'vinculo.local')
                ->orderBy('horario')->get(),

            'consultasHoje'   => $medico->consultas()->whereDate('data_consulta', today())->count(),
            'pacientesTotal'  => $medico->consultas()->distinct('paciente_id')->count('paciente_id'),
            'proximosDias'    => $medico->consultas()->agendadas()
                                    ->whereBetween('data_consulta', [today()->addDay(), today()->addDays(7)])
                                    ->count(),
            'mediaAvaliacoes' => $medico->media_avaliacoes,
        ]);
    }
}
