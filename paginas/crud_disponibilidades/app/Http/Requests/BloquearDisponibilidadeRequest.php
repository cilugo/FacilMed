<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BloquearDisponibilidadeRequest extends FormRequest
{
    /**
     * Determina se o usuário autenticado pode fazer essa requisição.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para bloquear (ou desbloquear) um período.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bloqueado' => ['required', 'boolean'],
            'motivo_bloqueio' => ['required_if:bloqueado,true', 'nullable', 'string', 'max:255'],
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
            'bloqueado.required' => 'Informe se o período deve ser bloqueado ou desbloqueado.',
            'motivo_bloqueio.required_if' => 'Informe o motivo do bloqueio (ex: férias, licença).',
        ];
    }
}
