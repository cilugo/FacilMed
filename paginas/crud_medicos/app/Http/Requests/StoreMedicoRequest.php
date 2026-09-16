<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreMedicoRequest extends FormRequest
{
    /**
     * Determina se o usuário autenticado pode fazer essa requisição.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para cadastro de médico.
     * Cria a conta de usuário e o perfil de médico juntos, além de
     * vincular especialidades, estabelecimentos e convênios (arrays de IDs).
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
            'data_nascimento' => ['nullable', 'date', 'before:today'],
            'password' => ['required', 'confirmed', Password::defaults()],

            'crm' => ['required', 'string', 'max:20'],
            'uf_crm' => ['required', 'string', 'size:2'],

            'especialidades' => ['required', 'array', 'min:1'],
            'especialidades.*' => ['integer', 'exists:especialidades,id'],

            'estabelecimentos' => ['nullable', 'array'],
            'estabelecimentos.*' => ['integer', 'exists:estabelecimentos,id'],

            'convenios' => ['nullable', 'array'],
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
            'name.required' => 'O nome é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'cpf.required' => 'O CPF é obrigatório.',
            'cpf.unique' => 'Este CPF já está cadastrado.',
            'password.required' => 'A senha é obrigatória.',
            'password.confirmed' => 'A confirmação de senha não confere.',
            'crm.required' => 'O CRM é obrigatório.',
            'uf_crm.required' => 'A UF do CRM é obrigatória.',
            'uf_crm.size' => 'A UF do CRM deve ter 2 letras (ex: SP).',
            'especialidades.required' => 'Selecione ao menos uma especialidade.',
            'especialidades.min' => 'Selecione ao menos uma especialidade.',
            'especialidades.*.exists' => 'Uma das especialidades selecionadas não existe.',
            'estabelecimentos.*.exists' => 'Um dos estabelecimentos selecionados não existe.',
            'convenios.*.exists' => 'Um dos convênios selecionados não existe.',
        ];
    }
}
