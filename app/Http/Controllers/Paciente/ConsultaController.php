<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Models\Consulta;
use Illuminate\Http\Request;

class ConsultaController extends Controller
{
    public function index(Request $request)
    {
        $paciente = auth()->user()->paciente;

        return view('paciente.consultas.index', [
            'consultas' => $paciente->consultas()
                ->with('medico.user', 'especialidade', 'vinculo.local', 'avaliacao')
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->orderByDesc('data_consulta')->orderByDesc('horario')
                ->paginate(15),
        ]);
    }

    public function show(Consulta $consulta)
    {
        // "Esta logado" nao basta: precisa ser DONO desta consulta.
        // Sem isto, o paciente A abre /paciente/consultas/{id} do B.
        $this->authorize('view', $consulta);

        return view('paciente.consultas.show', compact('consulta'));
    }

    /**
     * Cancelar e SEMPRE permitido enquanto a consulta nao aconteceu.
     * Abaixo de 24h vira cancelamento tardio e fica registrado.
     *
     * Bloquear nao faz a pessoa comparecer - faz ela faltar. E falta
     * perde o horario, enquanto cancelamento devolve para outro paciente.
     */
    public function cancelar(Request $request, Consulta $consulta)
    {
        $this->authorize('cancelar', $consulta);

        abort_unless($consulta->podeSerCancelada(), 422);

        $consulta->update([
            'status'              => 'cancelada',
            'cancelada_por'       => auth()->id(),
            'cancelada_em'        => now(),
            'motivo_cancelamento' => $request->input('motivo'),
            'cancelamento_tardio' => $consulta->ehCancelamentoTardio(),
        ]);

        // TODO: avisar o medico por e-mail e gravar em
        // notificacoes_enviadas (tipo 'cancelamento').

        return redirect()->route('paciente.consultas')
            ->with('sucesso', 'Consulta cancelada.');
    }

    public function formRemarcar(Consulta $consulta)
    {
        $this->authorize('cancelar', $consulta);
        // TODO: reaproveitar a tela de escolha de horario do mesmo vinculo.
    }

    /**
     * Remarcar = cancelar a antiga e criar a nova, em UMA transacao.
     * Nunca edite a data da consulta existente: o indice unico calcula
     * em cima do horario, e o historico se perde.
     */
    public function remarcar(Request $request, Consulta $consulta)
    {
        $this->authorize('cancelar', $consulta);
        // TODO
    }
}
