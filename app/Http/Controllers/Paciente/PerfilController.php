<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Paciente\ExcluirContaRequest;
use App\Models\Foto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PerfilController extends Controller
{
    public function edit()
    {
        $paciente = auth()->user()->paciente->load('acessibilidade', 'foto');

        return view('paciente.perfil', [
            'paciente'   => $paciente,
            // 01/10/2026: "Minhas avaliações". O comentário aparece aqui porque
            // quem lê é o próprio autor (makeVisible: no model ele é escondido).
            'avaliacoes' => $paciente->avaliacoes()
                ->with('medico.user', 'consulta.especialidade', 'consulta.vinculo.local')
                ->latest()->get()
                ->each->makeVisible('comentario'),
            'avaliacoesLocais' => $paciente->avaliacoesLocais()
                ->with('local.clinica.user', 'local.medico')
                ->latest('updated_at')->get()
                ->each->makeVisible('comentario'),
        ]);
    }

    /**
     * 01/10/2026: foto do perfil. Uma só por paciente - mandar outra troca a
     * antiga. Fica no banco (ver App\Models\Foto) e só o próprio paciente vê.
     */
    public function salvarFoto(Request $request)
    {
        $request->validate(['foto' => Foto::regras()], Foto::mensagens());

        $paciente = $request->user()->paciente;
        Foto::updateOrCreate(['paciente_id' => $paciente->id], Foto::dadosDoArquivo($request->file('foto')));

        return redirect()->to(route('paciente.perfil'))->with('sucesso', 'Foto atualizada.');
    }

    public function removerFoto(Request $request)
    {
        Foto::where('paciente_id', $request->user()->paciente->id)->delete();

        return redirect()->to(route('paciente.perfil'))->with('sucesso', 'Foto removida.');
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
