<?php

namespace App\Http\Controllers\Medico;

use App\Http\Controllers\Controller;
use App\Models\Preco;
use App\Http\Requests\Medico\SalvarPrecoRequest;
use Illuminate\Http\Request;

class PrecoController extends Controller
{
    /**
     * Preco por VINCULO + ESPECIALIDADE.
     *
     * O medico so edita preco de local que e consultorio proprio dele.
     * Em unidade de clinica, quem define e a clinica - a tela mostra
     * o valor, em modo leitura.
     *
     * Exemplo real do seeder: o Dr. Rafael atende clinica geral e
     * dermatologia em duas clinicas, com quatro precos diferentes.
     */
    public function index()
    {
        return view('medico.precos', [
            'vinculos' => auth()->user()->medico
                ->vinculos()
                ->with('local.clinica', 'precos.especialidade')
                ->get(),
            'especialidades' => auth()->user()->medico->especialidades,
        ]);
    }

    public function salvar(SalvarPrecoRequest $request)
    {
        $dados = $request->validated();

        Preco::updateOrCreate(
            ['vinculo_id' => $dados['vinculo_id'], 'especialidade_id' => $dados['especialidade_id']],
            ['valor' => $dados['valor'], 'ativo' => $request->has('ativo') ? $request->boolean('ativo') : true],
        );

        return back()->with('sucesso', 'Preço salvo.');
    }
}
