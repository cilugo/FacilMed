<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Medico;
use Illuminate\Http\Request;

class VerificacaoController extends Controller
{
    /**
     * Verificação de CRM (28/09: texto atualizado).
     *
     * Desde 24/09 o CRM é conferido AUTOMATICAMENTE no cadastro, na base
     * simulada (base_crms, App\Services\BaseSimulada). A fila de pendentes
     * costuma ficar vazia; a tela serve de histórico e para REJEITAR (tirar
     * da plataforma) um médico — o que cancela as consultas futuras dele.
     *
     * A TELA NUNCA PODE DIZER "validado junto ao CFM". Diz "conferido na
     * base simulada do FacilMed" (AGENTS.md §3). A API real do CFM é paga
     * (R$ 772/ano) e exige CNPJ — por isso a base simulada.
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

        // Sem e-mail aqui: notificacoes_enviadas é por CONSULTA (UNIQUE consulta_id+tipo),
        // e desde 24/09 a verificação é automática no cadastro. Se voltar a ser manual,
        // criar uma tabela de avisos de conta antes de mandar e-mail.

        return back()->with('sucesso', "CRM de {$medico->user->name} verificado.");
    }

    public function rejeitar(Request $request, Medico $medico)
    {
        $dados = $request->validate([
            'motivo' => ['required', 'string', 'min:10', 'max:255'],
        ]);

        // Médico rejeitado some da busca; as consultas futuras dele não podem
        // ficar "de pé" como se ele fosse atender.
        DB::transaction(function () use ($medico, $dados) {
            $medico->update([
                'status_verificacao' => 'rejeitado',
                'verificado_por'     => auth()->id(),
                'verificado_em'      => now(),
                'motivo_rejeicao'    => $dados['motivo'],
            ]);

            $medico->user->consultasFuturasAfetadas()->get()
                ->each->cancelar(auth()->id(), 'O profissional não está mais disponível no FacilMed');
        });

        return back()->with('sucesso', 'Cadastro rejeitado. Ele não aparece mais na busca.');
    }
}
