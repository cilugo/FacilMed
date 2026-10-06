<?php

namespace App\Http\Controllers\Usuario;

use App\Http\Controllers\Controller;
use App\Http\Requests\Usuario\ExcluirContaRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PerfilController extends Controller
{
    public function edit()
    {
        return view('usuario.perfil', [
            'usuario' => auth()->user()->usuario,
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
        $user->usuario->update([
            'data_nascimento' => $dados['data_nascimento'] ?? null,
            'sexo'            => $dados['sexo'] ?? null,
        ]);

        return back()->with('sucesso', 'Dados atualizados.');
    }

    /**
     * 30/09/2026 — o usuário exclui a própria conta (LGPD). A regra inteira
     * está em Usuario::excluirConta(); aqui só confirma, chama e desloga.
     */
    public function excluir(ExcluirContaRequest $request)
    {
        $request->user()->usuario->excluirConta();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Sua conta foi excluída e seus dados pessoais foram apagados.');
    }
}
