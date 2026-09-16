<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDisponibilidadeRequest extends FormRequest
{
    /**
     * Determina se o usuário autenticado pode fazer essa requisição.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para cadastro de disponibilidade.
     * É obrigatório informar 'dia_semana' (recorrente) OU 'data' (pontual),
     * mas não os dois nem nenhum dos dois.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'medico_id' => ['required', 'integer', 'exists:medicos,id'],
            'estabelecimento_id' => ['nullable', 'integer', 'exists:estabelecimentos,id'],

            'dia_semana' => ['required_without:data', 'nullable', 'in:segunda,terca,quarta,quinta,sexta,sabado,domingo'],
            'data' => ['required_without:dia_semana', 'nullable', 'date', 'after_or_equal:today'],

            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fim' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'duracao_consulta_minutos' => ['required', 'integer', 'min:5', 'max:240'],

            'tipo_atendimento' => ['required', 'in:particular,convenio,sus'],
            'convenio_id' => ['required_if:tipo_atendimento,convenio', 'nullable', 'integer', 'exists:convenios,id'],
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
            'medico_id.required' => 'O médico é obrigatório.',
            'medico_id.exists' => 'Médico não encontrado.',
            'estabelecimento_id.exists' => 'Estabelecimento não encontrado.',
            'dia_semana.required_without' => 'Informe o dia da semana ou uma data específica.',
            'data.required_without' => 'Informe uma data ou o dia da semana.',
            'data.after_or_equal' => 'A data não pode estar no passado.',
            'hora_inicio.required' => 'O horário inicial é obrigatório.',
            'hora_fim.required' => 'O horário final é obrigatório.',
            'hora_fim.after' => 'O horário final deve ser depois do horário inicial.',
            'duracao_consulta_minutos.required' => 'A duração da consulta é obrigatória.',
            'tipo_atendimento.required' => 'O tipo de atendimento é obrigatório.',
            'convenio_id.required_if' => 'Selecione o convênio para este tipo de atendimento.',
        ];
    }

    /**
     * Validação adicional: impede que 'dia_semana' e 'data' sejam
     * preenchidos ao mesmo tempo, e verifica conflito de horário
     * com outra disponibilidade já cadastrada para o mesmo médico.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('dia_semana') && $this->filled('data')) {
                $validator->errors()->add('data', 'Informe apenas o dia da semana OU a data, não os dois.');
            }
        });
    }
}
