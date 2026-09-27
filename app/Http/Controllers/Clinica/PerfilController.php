<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class PerfilController extends Controller
{
    public function edit()
    {
        return view('clinica.perfil', ['clinica' => auth()->user()->clinica]);
    }

    /**
     * CNPJ e razão social NÃO mudam aqui: são o que foi conferido na base
     * simulada. O resto (nome fantasia, descrição, telefone, responsável) muda.
     */
    public function update(Request $request)
    {
        $request->merge(['telefone' => preg_replace('/\D/', '', (string) $request->input('telefone'))]);

        $dados = $request->validate([
            'name'          => ['required', 'string', 'min:3', 'max:255'],
            'nome_fantasia' => ['required', 'string', 'min:2', 'max:150'],
            'descricao'     => ['nullable', 'string', 'max:2000'],
            'telefone'      => ['nullable', 'digits_between:10,11'],
        ], [
            'name.required' => 'Digite o nome do responsável pela conta.',
        ]);

        DB::transaction(function () use ($request, $dados) {
            $request->user()->update(['name' => $dados['name'], 'telefone' => $dados['telefone'] ?: null]);
            $request->user()->clinica->update([
                'nome_fantasia' => $dados['nome_fantasia'],
                'descricao'     => $dados['descricao'] ?? null,
                'telefone'      => $dados['telefone'] ?: null,
            ]);
        });

        return back()->with('sucesso', 'Dados da clínica salvos.');
    }
}
