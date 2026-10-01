<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clinica\SalvarAusenciaRequest;
use App\Models\Bloqueio;
use App\Models\Vinculo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ausências dos médicos (01/10/2026, plano novo do grupo): férias, feriado,
 * congresso, imprevisto. Antes o próprio médico registrava; agora é a clínica,
 * sempre para um médico numa unidade dela.
 *
 * Horário dentro de uma ausência nunca é oferecido ao paciente.
 */
class AusenciaController extends Controller
{
    public function index()
    {
        $clinica  = auth()->user()->clinica;
        $vinculos = $clinica->vinculos()->where('vinculos.ativo', true)->with('medico.user', 'local')->get()
            ->sortBy(fn ($v) => $v->medico->user->name . ' ' . $v->local->nome)->values();

        return view('clinica.ausencias', [
            'bloqueios' => Bloqueio::whereIn('vinculo_id', Vinculo::whereIn('local_id', $clinica->locais()->select('id'))->select('id'))
                ->with('medico.user', 'vinculo.local')
                ->orderByDesc('inicio')->get(),
            'vinculos'  => $vinculos,
        ]);
    }

    public function salvar(SalvarAusenciaRequest $request)
    {
        $dados   = $request->validated();
        $vinculo = Vinculo::findOrFail($dados['vinculo_id']);

        // 28/09 (3ª revisão): a mensagem lá embaixo manda "registrar de novo
        // marcando cancelar consultas" - e antes isso criava uma SEGUNDA
        // ausência igual à primeira. Agora a mesma ausência (mesmo lugar,
        // mesmo início e fim) é reaproveitada.
        $bloqueio = Bloqueio::firstOrNew([
            'medico_id'  => $vinculo->medico_id,
            'vinculo_id' => $vinculo->id,
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
