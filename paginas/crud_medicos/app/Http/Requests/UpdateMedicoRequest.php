<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateMedicoRequest extends FormRequest
{
    /**
     * Determina se o usuário autenticado pode fazer essa requisição.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para atualização de médico.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $medico = $this->route('medico');
        $userId = $medico?->user_id;
        $medicoId = $medico?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'telefone' => ['nullable', 'string', 'max:20'],
            'password' => ['sometimes', 'confirmed', Password::defaults()],

            'crm' => ['sometimes', 'required', 'string', 'max:20'],
            'uf_crm' => ['sometimes', 'required', 'string', 'size:2'],
            'ativo' => ['sometimes', 'boolean'],

            'especialidades' => ['sometimes', 'array', 'min:1'],
            'especialidades.*' => ['integer', 'exists:especialidades,id'],

            'estabelecimentos' => ['sometimes', 'array'],
            'estabelecimentos.*' => ['integer', 'exists:estabelecimentos,id'],

            'convenios' => ['sometimes', 'array'],
            'convenios.*' => ['integer', 'exists:convenios,id'],
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
            'uf_crm.size' => 'A UF do CRM deve ter 2 letras (ex: SP).',
            'especialidades.min' => 'Selecione ao menos uma especialidade.',
        ];
    }
}
