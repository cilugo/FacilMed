<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Paciente\ExcluirContaRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PerfilController extends Controller
{
    public function edit()
    {
        return view('paciente.perfil', [
            'paciente' => auth()->user()->paciente->load('acessibilidade'),
        ]);
    }

    /**
     * Dados pessoais (24/09). CPF e e-mail NAO mudam por aqui: CPF identifica
     * a pessoa e e conferido nas carteirinhas; e-mail e o login.
     * Senha tem formulario proprio (rota password.update, do Breeze).
     */
    public function update(Request $request)
    {
        $request->merge(['telefone' => preg_replace('/\D/', '', (string) $request->input('telefone'))]);

        $dados = $request->validate([
            'name'            => ['required', 'string', 'min:3', 'max:255'],
            'telefone'        => ['nullable', 'digits_between:10,11'],
            'data_nascimento' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'sexo'            => ['nullable', Rule::in(['Masculino', 'Feminino', 'Prefiro nao informar'])],
        ], [
            'telefone.digits_between' => 'O telefone deve ter DDD + número, com 10 ou 11 dígitos.',
        ]);

        $user = $request->user();
        $user->update(['name' => $dados['name'], 'telefone' => $dados['telefone'] ?: null]);
        $user->paciente->update([
            'data_nascimento' => $dados['data_nascimento'] ?? null,
            'sexo'            => $dados['sexo'] ?? null,
        ]);

        return back()->with('sucesso', 'Dados atualizados.');
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
            'descricao'          => ['nullable', 'required_if:possui_deficiencia,1', 'string', 'max:500'],
            'consentimento'      => ['accepted_if:possui_deficiencia,1'],
        ], [
            'descricao.required_if'     => 'Conte brevemente do que você precisa.',
            'consentimento.accepted_if' => 'Precisamos da sua autorização para guardar essa informação.',
        ]);

        $paciente = auth()->user()->paciente;

        if (! $dados['possui_deficiencia']) {
            $paciente->acessibilidade()->delete();

            return back()->with('sucesso', 'Informação removida.');
        }

        $paciente->acessibilidade()->updateOrCreate([], [
            'possui_deficiencia'   => true,
            'descricao'            => $dados['descricao'],
            'consentimento_em'     => now(),
            'consentimento_versao' => '1.0',
        ]);

        return back()->with('sucesso', 'Informação salva.');
    }

    /**
     * 30/09/2026 — o paciente exclui a própria conta (LGPD). A regra inteira
     * está em Paciente::excluirConta(); aqui só confirma, chama e desloga.
     */
    public function excluir(ExcluirContaRequest $request)
    {
        $canceladas = $request->user()->paciente->excluirConta();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Sua conta foi excluída e seus dados pessoais foram apagados.' .
            ($canceladas > 0 ? " {$canceladas} " . ($canceladas === 1 ? 'consulta futura foi cancelada' : 'consultas futuras foram canceladas') . ' e os médicos foram avisados.' : ''));
    }
}
