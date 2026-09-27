<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Preco;
use Illuminate\Http\Request;

class PrecoController extends Controller
{
    /**
     * A tabela de precos da clinica: uma linha por
     * medico x unidade x especialidade.
     *
     * E a clinica quem define, porque o local pertence a ela.
     */
    public function index()
    {
        return view('clinica.precos', [
            'vinculos' => auth()->user()->clinica
                ->vinculos()
                ->with('medico.user', 'medico.especialidades', 'local', 'precos.especialidade')
                ->get(),
        ]);
    }

    /**
     * Grade inteira de uma vez: precos[vinculo_id][especialidade_id] = "250,00".
     * Campo vazio = aquela especialidade deixa de ser oferecida ali.
     */
    public function salvar(Request $request)
    {
        $clinica = $request->user()->clinica;
        $request->validate(['precos' => ['required', 'array']]);

        $vinculos = $clinica->vinculos()->with('medico.especialidades')->get()->keyBy('id');
        $erros = [];
        $linhas = [];

        foreach ($request->input('precos') as $vinculoId => $porEspecialidade) {
            $vinculo = $vinculos->get((int) $vinculoId);
            if (! $vinculo || ! is_array($porEspecialidade)) {
                abort(403, 'Esse vínculo não é de uma unidade da sua clínica.');
            }

            foreach ($porEspecialidade as $espId => $valor) {
                if (! $vinculo->medico->especialidades->contains('id', (int) $espId)) {
                    $erros["precos.$vinculoId.$espId"] = "{$vinculo->medico->user->name} não tem essa especialidade.";
                    continue;
                }

                $texto = trim((string) $valor);
                $numero = $texto === '' ? null : str_replace(',', '.', str_replace(['R$', ' ', '.'], '', $texto));

                if ($numero !== null && (! is_numeric($numero) || $numero < 0 || $numero > 99999)) {
                    $erros["precos.$vinculoId.$espId"] = "Valor inválido: \"{$texto}\".";
                    continue;
                }

                $linhas[] = [(int) $vinculoId, (int) $espId, $numero];
            }
        }

        if ($erros) {
            return back()->withErrors($erros)->withInput();
        }

        DB::transaction(function () use ($linhas) {
            foreach ($linhas as [$vinculoId, $espId, $valor]) {
                if ($valor === null) {
                    Preco::where('vinculo_id', $vinculoId)->where('especialidade_id', $espId)->update(['ativo' => false]);
                } else {
                    Preco::updateOrCreate(['vinculo_id' => $vinculoId, 'especialidade_id' => $espId], ['valor' => $valor, 'ativo' => true]);
                }
            }
        });

        return back()->with('sucesso', 'Tabela de preços salva.');
    }
}
