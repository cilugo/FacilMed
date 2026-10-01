<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clinica\SalvarDisponibilidadeRequest;
use App\Models\Disponibilidade;

/**
 * Horários dos médicos (01/10/2026, plano novo do grupo).
 *
 * Antes era o próprio médico que cadastrava ("Meus horários"); agora o médico
 * só vê a agenda e a CLÍNICA cadastra quando cada médico atende em cada
 * unidade dela. As regras do bloco continuam no SalvarDisponibilidadeRequest.
 *
 * O ALMOÇO NÃO TEM CAMPO PRÓPRIO: são dois blocos no mesmo dia,
 * 08:00-12:00 e 14:00-18:00, e o buraco entre eles é o almoço.
 */
class HorarioController extends Controller
{
    public function index()
    {
        $vinculos = auth()->user()->clinica->vinculos()
            ->where('vinculos.ativo', true)
            ->with('medico.user', 'local', 'disponibilidades')
            ->get()
            ->sortBy(fn ($v) => $v->medico->user->name . ' ' . $v->local->nome)
            ->values();

        return view('clinica.horarios', [
            'vinculos' => $vinculos,
            'dias'     => Disponibilidade::DIAS,
            'duracoes' => SalvarDisponibilidadeRequest::DURACOES,
        ]);
    }

    public function salvar(SalvarDisponibilidadeRequest $request)
    {
        Disponibilidade::create($request->validated() + ['ativo' => true]);

        return back()->with('sucesso', 'Horário de atendimento adicionado.');
    }

    /**
     * Apagar bloco NÃO cancela consulta já marcada dentro dele: a mensagem
     * diz quantas continuam de pé, para a clínica decidir na agenda.
     */
    public function remover(Disponibilidade $disponibilidade)
    {
        $this->authorize('delete', $disponibilidade);

        $futuras = $disponibilidade->consultasFuturasDentro()->count();
        $disponibilidade->delete();

        $msg = 'Horário removido. Ele não aparece mais para agendamento.';
        if ($futuras > 0) {
            $msg .= " Atenção: {$futuras} " . ($futuras === 1 ? 'consulta já marcada continua' : 'consultas já marcadas continuam')
                . ' na agenda — cancele por lá se o médico não for atender.';
        }

        return back()->with('sucesso', $msg);
    }
}
