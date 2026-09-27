<?php

namespace App\Http\Controllers\Medico;

use App\Http\Controllers\Controller;
use App\Models\Consulta;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    /**
     * Agenda do dia (?data=AAAA-MM-DD, ?vinculo=id). Traz também a
     * acessibilidade do paciente: aqui PODE, porque é o médico daquela
     * consulta (AGENTS.md §6).
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

    /**
     * Médico cancela (consulta ainda não aconteceu). Motivo obrigatório: vai
     * no aviso ao paciente, que pode já estar a caminho.
     */
    public function cancelar(Request $request, Consulta $consulta)
    {
        $this->authorize('cancelar', $consulta);
        abort_unless($consulta->podeSerCancelada(), 422, 'Essa consulta não pode mais ser cancelada.');

        $request->validate(['motivo' => ['required', 'string', 'max:255']], ['motivo.required' => 'Informe o motivo: ele vai no aviso ao paciente.']);

        $consulta->cancelar(auth()->id(), $request->input('motivo'));

        return back()->with('sucesso', 'Consulta cancelada. O paciente é avisado por e-mail.');
    }
}
