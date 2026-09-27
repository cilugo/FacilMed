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
        $filtrada = Consulta::query()
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->medico, fn ($q, $m) => $q->where('medico_id', $m))
            ->when($request->de, fn ($q, $d) => $q->whereDate('data_consulta', '>=', $d))
            ->when($request->ate, fn ($q, $d) => $q->whereDate('data_consulta', '<=', $d));

        return view('admin.consultas', [
            'consultas' => (clone $filtrada)
                ->with('paciente.user', 'medico.user', 'especialidade', 'vinculo.local')
                ->orderByDesc('data_consulta')->orderByDesc('horario')
                ->paginate(30)
                ->withQueryString(),
            'porStatus' => (clone $filtrada)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'filtros'   => $request->only(['status', 'medico', 'de', 'ate']),
        ]);
    }
}
