<?php

namespace App\Http\Controllers\Medico;

use App\Http\Controllers\Controller;
use App\Models\Consulta;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    public function index(Request $request)
    {
        $medico = auth()->user()->medico;
        $data = $request->date('data') ?? today();

        return view('medico.agenda', [
            'data' => $data,
            'consultas' => $medico->consultas()
                ->whereDate('data_consulta', $data)
                ->with('paciente.user', 'especialidade', 'vinculo.local')
                ->orderBy('horario')->get(),
        ]);
    }

    public function marcarRealizada(Consulta $consulta)
    {
        $this->authorize('atender', $consulta);

        $consulta->update(['status' => 'realizada']);

        return back()->with('sucesso', 'Consulta marcada como realizada.');
    }

    /**
     * Sem este status, quem faltou continua podendo avaliar o medico
     * e as metricas do painel ficam erradas.
     */
    public function marcarFalta(Consulta $consulta)
    {
        $this->authorize('atender', $consulta);

        $consulta->update(['status' => 'nao_compareceu']);

        return back()->with('sucesso', 'Ausencia registrada.');
    }

    public function cancelar(Request $request, Consulta $consulta)
    {
        $this->authorize('atender', $consulta);

        $consulta->update([
            'status'              => 'cancelada',
            'cancelada_por'       => auth()->id(),
            'cancelada_em'        => now(),
            'motivo_cancelamento' => $request->input('motivo'),
            'cancelamento_tardio' => $consulta->ehCancelamentoTardio(),
        ]);

        // TODO: avisar o paciente por e-mail e gravar em
        // notificacoes_enviadas. Cancelamento pelo medico SEMPRE
        // notifica - a pessoa pode ja estar a caminho.

        return back()->with('sucesso', 'Consulta cancelada e paciente avisado.');
    }
}
