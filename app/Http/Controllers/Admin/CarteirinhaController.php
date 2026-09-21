<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PacientePlano;
use Illuminate\Http\Request;

class CarteirinhaController extends Controller
{
    /**
     * Conferencia de carteirinha.
     *
     * Tambem MANUAL. O paciente emite o comprovante COMPROVA no portal
     * da ANS (com login gov.br proprio) e informa o codigo de controle.
     * Quem confere valida esse codigo no site da ANS: 8 primeiros
     * digitos do codigo + data de emissao.
     *
     * Nao existe API. A ANS nao compartilha dados de beneficiario com
     * terceiros - so o proprio titular consulta.
     *
     * O comprovante prova que a pessoa e beneficiaria daquela
     * operadora. NAO confirma o numero da carteirinha que ela digitou.
     * A tela precisa deixar isso claro para quem confere.
     */
    public function index()
    {
        return view('admin.carteirinhas', [
            'pendentes' => PacientePlano::where('status', 'pendente')
                ->with('paciente.user', 'plano.convenio.operadora')
                ->oldest()
                ->get(),
        ]);
    }

    public function aprovar(PacientePlano $pacientePlano)
    {
        $pacientePlano->update([
            'status'        => 'ativa',
            'conferido_por' => auth()->id(),
            'conferido_em'  => now(),
            'motivo_recusa' => null,
        ]);

        return back()->with('sucesso', 'Carteirinha conferida.');
    }

    public function recusar(Request $request, PacientePlano $pacientePlano)
    {
        $dados = $request->validate([
            'motivo' => ['required', 'string', 'min:10', 'max:255'],
        ]);

        $pacientePlano->update([
            'status'        => 'recusada',
            'conferido_por' => auth()->id(),
            'conferido_em'  => now(),
            'motivo_recusa' => $dados['motivo'],
        ]);

        return back()->with('sucesso', 'Carteirinha recusada.');
    }
}
