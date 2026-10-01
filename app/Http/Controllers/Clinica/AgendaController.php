<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Models\Consulta;
use App\Services\EstatisticasDeConsultas;
use App\Models\Vinculo;
use Illuminate\Http\Request;

class AgendaController extends Controller
{
    /**
     * Agenda consolidada: todas as unidades e todos os medicos da
     * clinica, com filtro por unidade, medico e especialidade.
     *
     * 01/10/2026 (plano novo do grupo): deixou de ser so leitura - a clinica
     * marca realizada/falta e cancela (o medico so ve a agenda dele).
     */
    public function index(Request $request)
    {
        $clinica = auth()->user()->clinica;
        $data = $request->date('data') ?? today();

        $consultas = EstatisticasDeConsultas::daClinica($clinica)->todas()
            ->whereDate('data_consulta', $data)
            ->when($request->integer('local'), fn ($q, $id) => $q->whereIn('vinculo_id', Vinculo::where('local_id', $id)->select('id')))
            ->when($request->integer('medico'), fn ($q, $id) => $q->where('medico_id', $id))
            ->when($request->integer('especialidade'), fn ($q, $id) => $q->where('especialidade_id', $id))
            // Sem 'paciente.acessibilidade': a clínica não lê esse dado (ConsultaPolicy::verAcessibilidade).
            ->with('paciente.user', 'medico.user', 'especialidade', 'vinculo.local', 'pacientePlano.plano.convenio')
            ->orderBy('horario')->get();

        $vinculos = $clinica->vinculos()->where('vinculos.ativo', true)->with('medico.user', 'medico.especialidades')->get();

        return view('clinica.agenda', [
            'data'      => $data,
            'anterior'  => $data->copy()->subDay()->toDateString(),
            'seguinte'  => $data->copy()->addDay()->toDateString(),
            'consultas' => $consultas,
            'filtros'   => $request->only(['local', 'medico', 'especialidade']),
            'unidades'  => $clinica->locais()->where('ativo', true)->orderBy('nome')->get(),
            'medicos'   => $vinculos->pluck('medico')->unique('id')->sortBy(fn ($m) => $m->user->name)->values(),
            'especialidades' => $vinculos->flatMap(fn ($v) => $v->medico->especialidades)->unique('id')->sortBy('nome')->values(),
            'resumo'    => [
                'agendadas'  => $consultas->where('status', 'agendada')->count(),
                'realizadas' => $consultas->where('status', 'realizada')->count(),
                'canceladas' => $consultas->where('status', 'cancelada')->count(),
                'faltas'     => $consultas->where('status', 'nao_compareceu')->count(),
            ],
        ]);
    }

    /**
     * A consulta aconteceu. A ConsultaPolicy::atender confere que ela e numa
     * unidade DESTA clinica, que esta agendada e que o horario ja chegou.
     */
    public function marcarRealizada(Consulta $consulta)
    {
        $this->authorize('atender', $consulta);

        $consulta->update(['status' => 'realizada']);

        return back()->with('sucesso', 'Consulta marcada como realizada. O paciente já pode avaliar o médico.');
    }

    /**
     * Sem este status, quem faltou continua podendo avaliar o medico
     * e as metricas do painel ficam erradas.
     */
    public function marcarFalta(Consulta $consulta)
    {
        $this->authorize('atender', $consulta);

        $consulta->update(['status' => 'nao_compareceu']);

        return back()->with('sucesso', 'Falta registrada: o paciente não compareceu.');
    }

    /**
     * A clinica cancela (consulta ainda nao aconteceu). Motivo obrigatorio:
     * vai no aviso ao paciente, que pode ja estar a caminho. Cancelar SO por
     * Consulta::cancelar() - e ele que dispara o e-mail certo.
     */
    public function cancelar(Request $request, Consulta $consulta)
    {
        $this->authorize('cancelar', $consulta);

        $request->validate(['motivo' => ['required', 'string', 'max:255']], ['motivo.required' => 'Informe o motivo: ele vai no aviso ao paciente.']);

        $consulta->cancelar(auth()->id(), $request->input('motivo'));

        return back()->with('sucesso', 'Consulta cancelada. O paciente é avisado por e-mail.');
    }
}
