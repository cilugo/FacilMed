<?php

namespace App\Http\Requests\Usuario;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 30/09/2026 — confirmação da exclusão de conta (Usuario::excluirConta).
 *
 * Não dá para desfazer, então pedimos duas coisas:
 *  - a senha atual (regra 'current_password' do Laravel: confere com a senha
 *    de quem está logado). Protege de alguém que achou o computador aberto;
 *  - a caixa "entendo que não dá para desfazer".
 *
 * O campo se chama current_password (e não "senha_atual") de propósito: é um
 * dos nomes que o Laravel NUNCA guarda na sessão quando a validação falha. Com
 * outro nome, a senha digitada errada ficaria salva na tabela sessions.
 *
 * $errorBag: a tela de perfil tem vários formulários. Com um "saco de erros"
 * próprio, o erro da senha daqui não aparece no formulário de trocar senha
 * (e vice-versa). Na view: $errors->excluirConta.
 */
class ExcluirContaRequest extends FormRequest
{
    protected $errorBag = 'excluirConta';

    public function authorize(): bool
    {
        return $this->user()?->usuario !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'confirmacao'      => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required'         => 'Digite sua senha para confirmar.',
            'current_password.current_password' => 'Senha incorreta.',
            'confirmacao.accepted'              => 'Marque a caixa para confirmar que entendeu.',
        ];
    }
}
