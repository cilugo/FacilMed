<?php

namespace App\Http\Requests\Usuario;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Avaliação de um local ou de um médico (01/10/2026): 1 a 5 estrelas e
 * comentário opcional. O CHECK do banco também barra estrela fora de 1-5;
 * aqui é para a pessoa receber a mensagem certa em vez de um erro 500.
 */
class AvaliarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->ehUsuario() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $comentario = trim((string) $this->input('comentario'));
        $this->merge(['comentario' => $comentario === '' ? null : $comentario]);
    }

    public function rules(): array
    {
        return [
            'estrelas'   => ['required', 'integer', 'between:1,5'],
            'comentario' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'estrelas.required' => 'Escolha de 1 a 5 estrelas.',
            'estrelas.between'  => 'Escolha de 1 a 5 estrelas.',
            'comentario.max'    => 'O comentário pode ter no máximo 1000 caracteres.',
        ];
    }
}
