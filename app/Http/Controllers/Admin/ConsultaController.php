<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consulta;
use Illuminate\Http\Request;

class ConsultaController extends Controller
{
    /**
     * Visao geral das consultas, com filtros.
     *
     * NAO exiba observacoes clinicas nem dado de acessibilidade aqui.
     * Admin ve o agendamento - quem, quando, onde, quanto - nao a
     * vida do paciente.
     */
    public function index(Request $request)
    {
        return view('admin.consultas', [
            'consultas' => Consulta::query()
                ->with('paciente.user', 'medico.user', 'especialidade', 'vinculo.local')
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->when($request->de, fn ($q, $d) => $q->whereDate('data_consulta', '>=', $d))
                ->when($request->ate, fn ($q, $d) => $q->whereDate('data_consulta', '<=', $d))
                ->orderByDesc('data_consulta')->orderByDesc('horario')
                ->paginate(30)
                ->withQueryString(),
        ]);
    }
}
