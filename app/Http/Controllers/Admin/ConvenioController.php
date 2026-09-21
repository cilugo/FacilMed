<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Convenio;
use App\Models\OperadoraAns;
use Illuminate\Http\Request;

class ConvenioController extends Controller
{
    /**
     * Convenio SO existe ancorado numa operadora real da ANS.
     *
     * Se a lista de operadoras estiver vazia, a tela precisa avisar
     * para rodar: php artisan facilmed:importar-operadoras
     *
     * Deixe visivel na interface: a OPERADORA e real, importada dos
     * dados abertos da ANS; os PLANOS sao ficticios, criados por
     * voces. Sem isso, parece que a plataforma tem contrato assinado
     * com a Unimed.
     */
    public function index()
    {
        return view('admin.convenios', [
            'convenios'  => Convenio::with('operadora', 'planos')->orderBy('nome')->get(),
            'operadoras' => OperadoraAns::doesntHave('convenio')->orderBy('razao_social')->get(),
        ]);
    }

    public function salvar(Request $request)
    {
        $dados = $request->validate([
            // Existe na tabela importada = existe na ANS.
            'operadora_ans_id' => ['required', 'exists:operadoras_ans,id', 'unique:convenios,operadora_ans_id'],
            'nome'             => ['required', 'string', 'max:150'],
            'descricao'        => ['nullable', 'string'],
        ]);

        Convenio::create([...$dados, 'ativo' => true]);

        return back()->with('sucesso', 'Convenio criado.');
    }

    public function salvarPlano(Request $request, Convenio $convenio)
    {
        // TODO: SalvarPlanoRequest.
    }
}
