<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConvenioRequest extends FormRequest
{
    /**
     * Determina se o usuário autenticado pode fazer essa requisição.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para cadastro de convênio.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255', 'unique:convenios,nome'],
            'cnpj' => ['nullable', 'string', 'size:18', 'unique:convenios,cnpj'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
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
            'nome.required' => 'O nome do convênio é obrigatório.',
            'nome.unique' => 'Este convênio já está cadastrado.',
            'cnpj.unique' => 'Este CNPJ já está cadastrado.',
            'cnpj.size' => 'O CNPJ deve estar no formato 00.000.000/0000-00.',
            'email.email' => 'Informe um e-mail válido.',
        ];
    }
}
