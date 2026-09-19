<?php

namespace App\Http\Controllers\Medico;

use App\Http\Controllers\Controller;
use App\Models\Convenio;
use App\Models\Especialidade;
use Illuminate\Http\Request;

class PerfilController extends Controller
{
    public function edit()
    {
        return view('medico.perfil', [
            'medico'         => auth()->user()->medico->load('especialidades', 'convenios'),
            'especialidades' => Especialidade::where('ativo', true)->orderBy('nome')->get(),
            'convenios'      => Convenio::where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function update(Request $request)
    {
        // TODO: AtualizarPerfilMedicoRequest.
        //
        // CRM e UF NAO podem ser editados livremente: mudar o CRM
        // depois de verificado derruba a verificacao de volta para
        // 'pendente'. Senao basta cadastrar um CRM valido, ser
        // aprovado, e trocar pelo numero de verdade.
    }

    public function salvarEspecialidades(Request $request)
    {
        // TODO: sync com a pivot, marcando uma como principal.
    }

    /**
     * Convenios aceitos. O vinculo e com o MEDICO, nao com o endereco
     * (decisao de 18/09/2026) - aceitando a operadora, ele aceita
     * todos os planos dela.
     */
    public function salvarConvenios(Request $request)
    {
        auth()->user()->medico->convenios()->sync($request->input('convenios', []));

        return back()->with('sucesso', 'Convenios atualizados.');
    }
}
