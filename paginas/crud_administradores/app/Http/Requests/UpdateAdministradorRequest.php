<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateAdministradorRequest extends FormRequest
{
    /**
     * Determina se o usuário autenticado pode fazer essa requisição.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para atualização de administrador.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $administrador = $this->route('administrador');
        $userId = $administrador?->user_id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'telefone' => ['nullable', 'string', 'max:20'],
            'password' => ['sometimes', 'confirmed', Password::defaults()],
            'cargo' => ['nullable', 'string', 'max:255'],
            'ativo' => ['sometimes', 'boolean'],
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
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado para outro usuário.',
            'password.confirmed' => 'A confirmação de senha não confere.',
        ];
    }
}
