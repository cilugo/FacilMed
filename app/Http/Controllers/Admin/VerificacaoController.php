<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Medico;
use Illuminate\Http\Request;

class VerificacaoController extends Controller
{
    /**
     * Fila de verificacao de CRM.
     *
     * A CONFERENCIA E MANUAL: a pessoa abre o portal do CFM, procura
     * o CRM e a UF, confere o nome, e aprova aqui. A API oficial e
     * paga (R$ 772/ano) e exige CNPJ com representante legal no SEI.
     *
     * A TELA NUNCA PODE DIZER "validado junto ao CFM". Diz
     * "verificado pela equipe FacilMed". Prometer validacao automatica
     * e mentir sobre o que o sistema faz (AGENTS.md secao 6).
     *
     * Ajude quem confere: mostre um link direto para a busca do CFM
     * com o CRM e a UF ja preenchidos.
     */
    public function index()
    {
        return view('admin.verificacoes', [
            'pendentes' => Medico::where('status_verificacao', 'pendente')
                ->with('user', 'especialidades')
                ->oldest()
                ->get(),

            'recentes' => Medico::whereIn('status_verificacao', ['verificado', 'rejeitado'])
                ->with('user')
                ->latest('verificado_em')
                ->limit(10)
                ->get(),
        ]);
    }

    public function aprovar(Medico $medico)
    {
        $medico->update([
            'status_verificacao' => 'verificado',
            'verificado_por'     => auth()->id(),
            'verificado_em'      => now(),
            'motivo_rejeicao'    => null,
        ]);

        // TODO: avisar o medico por e-mail - ele agora aparece na busca.

        return back()->with('sucesso', "CRM de {$medico->user->name} verificado.");
    }

    public function rejeitar(Request $request, Medico $medico)
    {
        $dados = $request->validate([
            'motivo' => ['required', 'string', 'min:10', 'max:255'],
        ]);

        $medico->update([
            'status_verificacao' => 'rejeitado',
            'verificado_por'     => auth()->id(),
            'verificado_em'      => now(),
            'motivo_rejeicao'    => $dados['motivo'],
        ]);

        // TODO: avisar por e-mail, COM o motivo - a pessoa precisa
        // saber o que corrigir.

        return back()->with('sucesso', 'Cadastro rejeitado.');
    }
}
