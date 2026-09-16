<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreAdministradorRequest extends FormRequest
{
    /**
     * Determina se o usuário autenticado pode fazer essa requisição.
     * Recomenda-se restringir esta rota a administradores já existentes.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para cadastro de administrador.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'cpf' => ['required', 'string', 'size:14', 'unique:users,cpf'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'cargo' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Mensagens de erro personalizadas em português.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'cpf.required' => 'O CPF é obrigatório.',
            'cpf.unique' => 'Este CPF já está cadastrado.',
            'password.required' => 'A senha é obrigatória.',
            'password.confirmed' => 'A confirmação de senha não confere.',
        ];
    }
}
