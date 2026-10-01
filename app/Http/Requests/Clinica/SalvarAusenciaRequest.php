<?php

namespace App\Http\Requests\Medico;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ausência (férias, congresso, imprevisto). Sem vínculo = vale para todos
 * os lugares onde o médico atende.
 *
 * Consultas já marcadas no período NÃO são canceladas sozinhas: só se o
 * médico marcar "cancelar_consultas". Cancelamento silencioso deixa o
 * paciente indo até a clínica à toa.
 */
class SalvarBloqueioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->ehMedico() ?? false;
    }

    public function rules(): array
    {
        return [
            'vinculo_id' => ['nullable', 'integer', Rule::exists('vinculos', 'id')->where('medico_id', $this->user()->medico?->id)],
            'inicio'     => ['required', 'date', 'after_or_equal:today'],
            'fim'        => ['required', 'date', 'after:inicio'],
            'motivo'     => ['nullable', 'string', 'max:255'],
            'cancelar_consultas' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'inicio.after_or_equal' => 'A ausência não pode começar no passado.',
            'fim.after'             => 'O fim precisa ser depois do início.',
            'vinculo_id.exists'     => 'Escolha um dos lugares onde você atende.',
        ];
    }
}
