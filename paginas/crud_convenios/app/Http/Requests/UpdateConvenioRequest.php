<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConvenioRequest extends FormRequest
{
    /**
     * Determina se o usuário autenticado pode fazer essa requisição.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para atualização de convênio.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $convenioId = $this->route('convenio');

        return [
            'nome' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('convenios', 'nome')->ignore($convenioId)],
            'cnpj' => ['nullable', 'string', 'size:18', Rule::unique('convenios', 'cnpj')->ignore($convenioId)],
            'telefone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
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
            'nome.unique' => 'Este convênio já está cadastrado.',
            'cnpj.unique' => 'Este CNPJ já está cadastrado para outro convênio.',
            'cnpj.size' => 'O CNPJ deve estar no formato 00.000.000/0000-00.',
            'email.email' => 'Informe um e-mail válido.',
        ];
    }
}
