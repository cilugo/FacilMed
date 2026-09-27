<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Http\Requests\CadastroCarteirinhaRequest;
use App\Models\Consulta;
use App\Models\Convenio;
use App\Models\PacientePlano;

class PlanoController extends Controller
{
    /**
     * Carteirinhas do paciente ("Meus planos").
     *
     * 24/09/2026 — CONFERÊNCIA AUTOMÁTICA NA BASE SIMULADA (decisão do
     * grupo). Como os convênios são fictícios, a tabela base_carteirinhas
     * faz o papel da operadora. O CadastroCarteirinhaRequest confere
     * número + plano + CPF do paciente + situação + validade:
     *   - bateu     → grava 'ativa' na hora (conferido_por = NULL, porque
     *                 quem conferiu foi a base, não uma pessoa);
     *   - não bateu → volta para o formulário com o motivo, nada é gravado.
     *
     * Substitui o fluxo antigo "entra pendente e alguém confere pelo
     * COMPROVA da ANS", que não funciona com plano fictício.
     *
     * A tela diz "conferida na base simulada do FacilMed" — nunca
     * "validada pela operadora".
     */
    public function index()
    {
        return view('paciente.planos', [
            'planos'    => auth()->user()->paciente->planos()->with('plano.convenio')->latest()->get(),
            // Só planos ATIVOS de convênios ATIVOS.
            'convenios' => Convenio::with(['planos' => fn ($q) => $q->where('ativo', true)])
                ->where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function salvar(CadastroCarteirinhaRequest $request)
    {
        $base = $request->registroBase; // preenchido pelo after() do request

        $carteirinha = PacientePlano::create([
            'paciente_id'        => $request->user()->paciente->id,
            'plano_id'           => $base->plano_id,
            'numero_carteirinha' => $base->numero_carteirinha,
            // Validade e nome vêm da BASE, não do que foi digitado.
            'validade'           => $base->validade,
            'titular_nome'       => $base->beneficiario_nome,
            'status'             => 'ativa',
            'conferido_por'      => null,
            'conferido_em'       => now(),
        ]);

        $carteirinha->load('plano');

        return back()->with('sucesso',
            "Carteirinha do {$carteirinha->plano->nome} conferida na base simulada do FacilMed e liberada para agendamento.");
    }

    /**
     * Remover carteirinha.
     *
     * Se ela já foi usada em alguma consulta, NÃO remove: a FK de
     * consultas é nullOnDelete, então o histórico perderia a informação
     * de qual plano foi usado.
     */
    public function remover(PacientePlano $pacientePlano)
    {
        $this->authorize('delete', $pacientePlano);

        if (Consulta::where('paciente_plano_id', $pacientePlano->id)->exists()) {
            return back()->with('erro', 'Essa carteirinha já foi usada em consultas e fica guardada no seu histórico.');
        }

        $pacientePlano->delete();

        return back()->with('sucesso', 'Carteirinha removida.');
    }
}
