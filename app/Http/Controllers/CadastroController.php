<?php

namespace App\Http\Controllers;

use App\Models\Especialidade;
use Illuminate\Http\Request;

class CadastroController extends Controller
{
    /**
     * Cadastro dos tres tipos de usuario.
     *
     * TODA validacao mora em FormRequest - nunca so no JavaScript.
     * O prototipo antigo validava senha apenas no JS: quem desabilitava
     * o JS ou mandava POST direto cadastrava com a senha "1".
     *
     * Senha: minimo 8, maximo 72 (limite do bcrypt). NAO existe limite
     * de 10 caracteres - limitar o maximo enfraquece sem ganho nenhum.
     *
     * TODO: criar os FormRequests CadastroPacienteRequest,
     * CadastroMedicoRequest e CadastroClinicaRequest.
     */
    public function escolher()
    {
        return view('cadastro.escolher');
    }

    public function formPaciente()
    {
        return view('cadastro.paciente');
    }

    public function salvarPaciente(Request $request)
    {
        // TODO: trocar por CadastroPacienteRequest.
        // Usar transacao: user + paciente precisam nascer juntos,
        // senao sobra conta orfa que trava o login.
        //
        // O campo de acessibilidade e OPCIONAL e exige consentimento
        // explicito. Dado sensivel de saude - sem upload de arquivo.
    }

    public function formMedico()
    {
        return view('cadastro.medico', [
            'especialidades' => Especialidade::where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function salvarMedico(Request $request)
    {
        // TODO: trocar por CadastroMedicoRequest.
        //
        // O medico nasce com status_verificacao = 'pendente' e NAO
        // aparece na busca ate um admin conferir o CRM no portal do CFM.
        // A tela de sucesso precisa dizer isso, senao ele acha que o
        // sistema esta quebrado porque ninguem agenda com ele.
    }

    public function formClinica()
    {
        return view('cadastro.clinica');
    }

    public function salvarClinica(Request $request)
    {
        // TODO: trocar por CadastroClinicaRequest. Valida CNPJ.
    }
}
