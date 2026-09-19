<?php

namespace App\Http\Controllers\Medico;

use App\Http\Controllers\Controller;
use App\Models\Bloqueio;
use Illuminate\Http\Request;

class BloqueioController extends Controller
{
    /**
     * Ferias, feriado, congresso, imprevisto.
     * Horario dentro de um bloqueio nunca e oferecido ao paciente.
     */
    public function index()
    {
        return view('medico.bloqueios', [
            'bloqueios' => auth()->user()->medico
                ->bloqueios()->with('vinculo.local')
                ->orderByDesc('inicio')->get(),
            'vinculos' => auth()->user()->medico->vinculos()->with('local')->get(),
        ]);
    }

    public function salvar(Request $request)
    {
        // TODO: SalvarBloqueioRequest. Validar fim > inicio.
        //
        // IMPORTANTE: se ja existe consulta agendada no periodo, NAO
        // cancele automaticamente. Liste as consultas e peca a decisao
        // ao medico - cancelamento silencioso deixa o paciente indo
        // ate a clinica a toa.
    }

    public function remover(Bloqueio $bloqueio)
    {
        $this->authorize('delete', $bloqueio);
        // TODO
    }
}
