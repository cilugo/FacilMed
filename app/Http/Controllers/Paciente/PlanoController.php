<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Models\Convenio;
use App\Models\PacientePlano;
use Illuminate\Http\Request;

class PlanoController extends Controller
{
    /**
     * Carteirinha do paciente.
     *
     * NAO EXISTE VALIDACAO AUTOMATICA. Nem API da ANS, nem integracao
     * TISS, e o COMPROVA exige login gov.br do proprio beneficiario.
     *
     * O que da para recusar na hora: operadora fora da lista da ANS,
     * formato invalido, validade vencida, carteirinha ja cadastrada.
     * O resto entra como 'pendente' e alguem confere.
     *
     * A tela precisa dizer "em conferencia", nunca "validado".
     *
     * Guardamos SO o codigo de controle do comprovante e a data -
     * nunca o PDF, que traz CPF, nome da mae e dados do plano.
     */
    public function index()
    {
        return view('paciente.planos', [
            'planos'    => auth()->user()->paciente->planos()->with('plano.convenio')->get(),
            'convenios' => Convenio::with('planos')->where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function salvar(Request $request)
    {
        // TODO: trocar por CadastroCarteirinhaRequest.
        // Status inicial SEMPRE 'pendente'.
    }

    public function remover(PacientePlano $pacientePlano)
    {
        $this->authorize('delete', $pacientePlano);
        // TODO
    }
}
