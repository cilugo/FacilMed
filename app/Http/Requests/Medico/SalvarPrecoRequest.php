<?php

namespace App\Http\Requests\Medico;

use App\Models\Vinculo;
use App\Support\Dinheiro;
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
        // "250,00" -> 250.00 e "150.00" -> 150.00 (antes virava 15000: ver
        // App\Support\Dinheiro). Se não der para entender o valor, fica o
        // texto original e a regra "numeric" recusa com a mensagem certa.
        $digitado = (string) $this->input('valor');
        $this->merge(['valor' => Dinheiro::lerDigitado($digitado) ?? $digitado]);
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
