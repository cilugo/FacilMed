<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEnderecoRequest extends FormRequest
{
    /**
     * Determina se o usuário autenticado pode fazer essa requisição.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para atualização de endereço.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cep' => ['sometimes', 'required', 'string', 'size:9'],
            'logradouro' => ['sometimes', 'required', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'bairro' => ['sometimes', 'required', 'string', 'max:255'],
            'cidade' => ['sometimes', 'required', 'string', 'max:255'],
            'estado' => ['sometimes', 'required', 'string', 'size:2'],
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
            'cep.size' => 'O CEP deve estar no formato 00000-000.',
            'estado.size' => 'O estado deve ser a sigla com 2 letras (ex: SP).',
        ];
    }
}
