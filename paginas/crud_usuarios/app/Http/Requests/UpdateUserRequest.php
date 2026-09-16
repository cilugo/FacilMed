<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determina se o usuário autenticado pode fazer essa requisição.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para atualização de usuário.
     * Os campos são "sometimes" pois a edição pode ser parcial (PATCH).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'cpf' => ['sometimes', 'required', 'string', 'size:14', Rule::unique('users', 'cpf')->ignore($userId)],
            'telefone' => ['nullable', 'string', 'max:20'],
            'data_nascimento' => ['nullable', 'date', 'before:today'],
            'password' => ['sometimes', 'confirmed', Password::defaults()],
            'tipo_usuario' => ['sometimes', 'required', 'in:paciente,medico,administrador'],
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
            'cpf.unique' => 'Este CPF já está cadastrado para outro usuário.',
            'cpf.size' => 'O CPF deve estar no formato 000.000.000-00.',
            'data_nascimento.before' => 'A data de nascimento deve ser anterior a hoje.',
            'password.confirmed' => 'A confirmação de senha não confere.',
            'tipo_usuario.in' => 'O tipo de usuário deve ser paciente, médico ou administrador.',
        ];
    }
}
