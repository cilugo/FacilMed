<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Models\Especialidade;
use App\Models\Vinculo;
use Illuminate\Http\Request;

class MedicoController extends Controller
{
    public function index()
    {
        return view('clinica.medicos', [
            'vinculos' => auth()->user()->clinica
                ->vinculos()->with('medico.user', 'medico.especialidades', 'local')
                ->get(),
        ]);
    }

    public function form()
    {
        return view('clinica.medicos-form', [
            'especialidades' => Especialidade::where('ativo', true)->orderBy('nome')->get(),
            'unidades'       => auth()->user()->clinica->locais()->where('ativo', true)->get(),
        ]);
    }

    /**
     * A clinica cadastra o medico.
     *
     * O sistema gera SENHA TEMPORARIA e marca senha_temporaria = true;
     * o medico e obrigado a trocar no primeiro login. A clinica nunca
     * fica sabendo a senha definitiva do profissional.
     *
     * O medico entra como 'pendente' de verificacao de CRM, igual a
     * quem se cadastra sozinho - clinica nao verifica CRM.
     *
     * Se o medico JA existe no sistema (mesmo CRM+UF), nao crie outro:
     * crie so o vinculo com a unidade.
     */
    public function salvar(Request $request)
    {
        // TODO: CadastrarMedicoPelaClinicaRequest, dentro de transacao.
    }

    public function desvincular(Vinculo $vinculo)
    {
        $this->authorize('delete', $vinculo);

        // ATENCAO: desvincular com consulta futura agendada deixa
        // paciente sem atendimento. Liste as consultas antes e exija
        // uma decisao.
    }
}
