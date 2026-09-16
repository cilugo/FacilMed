<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDisponibilidadeRequest extends FormRequest
{
    /**
     * Determina se o usuário autenticado pode fazer essa requisição.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para atualização de disponibilidade.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'estabelecimento_id' => ['nullable', 'integer', 'exists:estabelecimentos,id'],
            'dia_semana' => ['nullable', 'in:segunda,terca,quarta,quinta,sexta,sabado,domingo'],
            'data' => ['nullable', 'date'],
            'hora_inicio' => ['sometimes', 'required', 'date_format:H:i'],
            'hora_fim' => ['sometimes', 'required', 'date_format:H:i', 'after:hora_inicio'],
            'duracao_consulta_minutos' => ['sometimes', 'required', 'integer', 'min:5', 'max:240'],
            'tipo_atendimento' => ['sometimes', 'required', 'in:particular,convenio,sus'],
            'convenio_id' => ['required_if:tipo_atendimento,convenio', 'nullable', 'integer', 'exists:convenios,id'],
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
            'hora_fim.after' => 'O horário final deve ser depois do horário inicial.',
            'convenio_id.required_if' => 'Selecione o convênio para este tipo de atendimento.',
        ];
    }
}
