<?php

namespace App\Http\Controllers\Medico;

use App\Http\Controllers\Controller;
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

    public function salvar(Request $request)
    {
        // TODO: SalvarPrecoRequest.
        // A Policy precisa checar Local::donoUserId() === auth()->id().
    }
}
