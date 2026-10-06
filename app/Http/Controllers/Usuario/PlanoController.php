<?php

namespace App\Http\Controllers\Usuario;

use App\Http\Controllers\Controller;
use App\Http\Requests\CadastroCarteirinhaRequest;
use App\Models\Convenio;
use App\Models\UsuarioPlano;

class PlanoController extends Controller
{
    /**
     * Carteirinhas do usuário ("Meus planos").
     *
     * 24/09/2026 — CONFERÊNCIA AUTOMÁTICA NA BASE SIMULADA (decisão do
     * grupo). Como os convênios são fictícios, a tabela base_carteirinhas
     * faz o papel da operadora. O CadastroCarteirinhaRequest confere
     * número + plano + CPF do usuário + situação + validade:
     *   - bateu     → grava 'ativa' na hora (conferido_por = NULL, porque
     *                 quem conferiu foi a base, não uma pessoa);
     *   - não bateu → volta para o formulário com o motivo, nada é gravado.
     *
     * Substitui o fluxo antigo "entra pendente e alguém confere pelo
     * COMPROVA da ANS", que não funciona com plano fictício.
     *
     * A tela diz "conferida na base simulada do PointMed" — nunca
     * "validada pela operadora".
     */
    public function index()
    {
        return view('usuario.planos', [
            'planos'    => auth()->user()->usuario->planos()->with('plano.convenio')->latest()->get(),
            // Só planos ATIVOS de convênios ATIVOS.
            'convenios' => Convenio::with(['planos' => fn ($q) => $q->where('ativo', true)])
                ->where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function salvar(CadastroCarteirinhaRequest $request)
    {
        $base = $request->registroBase; // preenchido pelo after() do request

        $carteirinha = UsuarioPlano::create([
            'usuario_id'        => $request->user()->usuario->id,
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
            "Carteirinha do {$carteirinha->plano->nome} conferida na base simulada do PointMed.");
    }

    /** Remover carteirinha. (01/10: sem consultas, nada mais depende dela.) */
    public function remover(UsuarioPlano $usuarioPlano)
    {
        $this->authorize('delete', $usuarioPlano);

        $usuarioPlano->delete();

        return back()->with('sucesso', 'Carteirinha removida.');
    }
}
