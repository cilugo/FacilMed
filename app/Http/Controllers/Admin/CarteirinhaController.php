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
    /**
     * Desde 24/09 a carteirinha é conferida na hora pela base simulada, então
     * a fila de "pendentes" costuma estar vazia. A tela vira CONSULTA: as
     * carteirinhas mais recentes e a situação de cada uma.
     */
    public function index(Request $request)
    {
        return view('admin.carteirinhas', [
            'pendentes' => PacientePlano::where('status', 'pendente')
                ->with('paciente.user', 'plano.convenio')->oldest()->get(),
            'recentes'  => PacientePlano::query()
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->with('paciente.user', 'plano.convenio')
                ->latest()->paginate(30)->withQueryString(),
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
