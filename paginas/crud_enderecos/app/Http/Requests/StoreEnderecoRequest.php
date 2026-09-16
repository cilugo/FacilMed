<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnderecoRequest extends FormRequest
{
    /**
     * Determina se o usuário autenticado pode fazer essa requisição.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para cadastro de endereço.
     * enderecavel_type e enderecavel_id são opcionais aqui: um endereço
     * pode ser cadastrado avulso e vinculado depois, ou já vir vinculado
     * (ex: ao cadastrar um Estabelecimento).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cep' => ['required', 'string', 'size:9'], // formato 00000-000
            'logradouro' => ['required', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'bairro' => ['required', 'string', 'max:255'],
            'cidade' => ['required', 'string', 'max:255'],
            'estado' => ['required', 'string', 'size:2'],
            'enderecavel_type' => ['nullable', 'string', 'in:App\\Models\\Estabelecimento,App\\Models\\Medico'],
            'enderecavel_id' => ['nullable', 'integer', 'required_with:enderecavel_type'],
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
            'cep.required' => 'O CEP é obrigatório.',
            'cep.size' => 'O CEP deve estar no formato 00000-000.',
            'logradouro.required' => 'O logradouro é obrigatório.',
            'bairro.required' => 'O bairro é obrigatório.',
            'cidade.required' => 'A cidade é obrigatória.',
            'estado.required' => 'O estado (UF) é obrigatório.',
            'estado.size' => 'O estado deve ser a sigla com 2 letras (ex: SP).',
        ];
    }
}
