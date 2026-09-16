<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdatePacienteRequest extends FormRequest
{
    /**
     * Determina se o usuário autenticado pode fazer essa requisição.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para atualização de paciente (e da conta de usuário vinculada).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $paciente = $this->route('paciente');
        $userId = $paciente?->user_id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'telefone' => ['nullable', 'string', 'max:20'],
            'password' => ['sometimes', 'confirmed', Password::defaults()],
            'numero_carteirinha' => ['nullable', 'string', 'max:50'],
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
