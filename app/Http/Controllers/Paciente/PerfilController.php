<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PerfilController extends Controller
{
    public function edit()
    {
        return view('paciente.perfil', [
            'paciente' => auth()->user()->paciente->load('acessibilidade'),
        ]);
    }

    public function update(Request $request)
    {
        // TODO: AtualizarPerfilPacienteRequest.
    }

    /**
     * DADO SENSIVEL DE SAUDE - LGPD art. 11.
     *
     *  - preenchimento OPCIONAL;
     *  - exige consentimento explicito, com data e versao do texto;
     *  - so texto: NAO existe upload de laudo (decisao de 18/09/2026);
     *  - o paciente pode apagar a qualquer momento, e apagar remove
     *    a linha inteira - nao marca como inativo.
     *
     * Quem le: apenas o profissional com consulta marcada com ele,
     * via PacienteAcessibilidadePolicy. Nunca em listagem ou busca.
     */
    public function salvarAcessibilidade(Request $request)
    {
        $dados = $request->validate([
            'possui_deficiencia' => ['required', 'boolean'],
            'descricao'          => ['nullable', 'string', 'max:500'],
            'consentimento'      => ['accepted_if:possui_deficiencia,1'],
        ]);

        $paciente = auth()->user()->paciente;

        if (! $dados['possui_deficiencia']) {
            $paciente->acessibilidade()->delete();

            return back()->with('sucesso', 'Informacao removida.');
        }

        $paciente->acessibilidade()->updateOrCreate([], [
            'possui_deficiencia'   => true,
            'descricao'            => $dados['descricao'],
            'consentimento_em'     => now(),
            'consentimento_versao' => '1.0',
        ]);

        return back()->with('sucesso', 'Informacao salva.');
    }
}
