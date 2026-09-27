<?php

namespace App\Http\Requests\Medico;

use App\Models\Vinculo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Preço de uma especialidade num lugar. O médico só define preço no
 * CONSULTÓRIO PRÓPRIO; em unidade de clínica, quem define é a clínica
 * (LocalPolicy::definirPrecos).
 */
class SalvarPrecoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vinculo = Vinculo::with('local')->find($this->input('vinculo_id'));

        return $vinculo !== null
            && $vinculo->medico_id === $this->user()->medico?->id
            && $this->user()->can('definirPrecos', $vinculo->local);
    }

    protected function prepareForValidation(): void
    {
        // "250,00" -> 250.00
        $valor = str_replace(['R$', ' ', '.'], '', (string) $this->input('valor'));
        $this->merge(['valor' => str_replace(',', '.', $valor)]);
    }

    public function rules(): array
    {
        return [
            'vinculo_id'       => ['required', 'integer'],
            'especialidade_id' => ['required', 'integer', Rule::exists('medico_especialidade', 'especialidade_id')
                ->where('medico_id', $this->user()->medico?->id)],
            'valor'            => ['required', 'numeric', 'min:0', 'max:99999'],
            'ativo'            => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['especialidade_id.exists' => 'Você só define preço das suas especialidades.'];
    }
}
