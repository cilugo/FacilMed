<?php

namespace App\Http\Controllers\Medico;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Requests\Medico\SalvarBloqueioRequest;
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

    public function salvar(SalvarBloqueioRequest $request)
    {
        $medico = $request->user()->medico;
        $dados  = $request->validated();

        // 28/09 (3ª revisão): a mensagem lá embaixo manda "registrar de novo
        // marcando cancelar consultas" - e antes isso criava uma SEGUNDA
        // ausência igual à primeira. Agora a mesma ausência (mesmo lugar,
        // mesmo início e fim) é reaproveitada.
        $bloqueio = Bloqueio::firstOrNew([
            'medico_id'  => $medico->id,
            'vinculo_id' => $dados['vinculo_id'] ?? null,
            'inicio'     => Carbon::parse($dados['inicio']),
            'fim'        => Carbon::parse($dados['fim']),
        ]);
        if (! $bloqueio->exists || ! empty($dados['motivo'])) {
            $bloqueio->motivo = $dados['motivo'] ?? null;
        }
        $bloqueio->save();

        $conflitos = $bloqueio->consultasAgendadasNoPeriodo()->get();

        if ($conflitos->isEmpty()) {
            return back()->with('sucesso', 'Ausência registrada. Esses horários não aparecem mais para agendamento.');
        }

        if ($request->boolean('cancelar_consultas')) {
            DB::transaction(fn () => $conflitos->each->cancelar($request->user()->id, 'Ausência do médico' . ($bloqueio->motivo ? ": {$bloqueio->motivo}" : '')));

            return back()->with('sucesso', "Ausência registrada e {$conflitos->count()} " .
                ($conflitos->count() === 1 ? 'consulta cancelada' : 'consultas canceladas') . ' (os pacientes são avisados por e-mail).');
        }

        // withInput: o formulário volta preenchido; para cancelar, basta
        // marcar a caixa e enviar de novo (a ausência não duplica).
        return back()
            ->withInput()
            ->with('sucesso', 'Ausência registrada.')
            ->with('erro', "Há {$conflitos->count()} " . ($conflitos->count() === 1 ? 'consulta marcada' : 'consultas marcadas')
                . ' nesse período. Elas NÃO foram canceladas: cancele pela agenda ou registre a ausência de novo marcando "cancelar consultas".')
            ->with('conflitos', $conflitos->pluck('id')->all());
    }

    public function remover(Bloqueio $bloqueio)
    {
        $this->authorize('delete', $bloqueio);

        $bloqueio->delete();

        return back()->with('sucesso', 'Ausência removida. Os horários voltam a aparecer para agendamento.');
    }
}
