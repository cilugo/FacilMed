<?php

namespace App\Http\Requests\Clinica;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ausência de um médico (férias, congresso, imprevisto).
 *
 * 01/10/2026 (plano novo do grupo): quem registra é a CLÍNICA. A ausência vale
 * para um médico numa unidade DESTA clínica (vinculo_id obrigatório): a clínica
 * não tira horário do mesmo médico em outra clínica onde ele atende.
 *
 * Consultas já marcadas no período NÃO são canceladas sozinhas: só se a
 * clínica marcar "cancelar_consultas". Cancelamento silencioso deixa o
 * paciente indo até a clínica à toa.
 */
class SalvarAusenciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->ehClinica() ?? false;
    }

    public function rules(): array
    {
        return [
            'vinculo_id' => ['required', 'integer', Rule::exists('vinculos', 'id')
                ->whereIn('local_id', $this->user()->clinica?->locais()->pluck('id')->all() ?? [])],
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
            'vinculo_id.required'   => 'Escolha o médico e a unidade.',
            'vinculo_id.exists'     => 'Escolha um médico de uma das suas unidades.',
        ];
    }
}
