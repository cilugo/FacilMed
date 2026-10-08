<?php

namespace App\Http\Requests\Clinica;

use App\Models\HorarioFuncionamento;
use App\Support\Uf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Nova unidade (clínica ou hospital) da clínica logada. */
class SalvarUnidadeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->ehClinica() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cep'      => preg_replace('/\D/', '', (string) $this->input('cep')),
            'telefone' => preg_replace('/\D/', '', (string) $this->input('telefone')),
            'uf'       => mb_strtoupper(trim((string) $this->input('uf'))),
        ]);
    }

    public function rules(): array
    {
        $regras = [
            'nome'        => ['required', 'string', 'max:150'],
            'tipo'        => ['required', Rule::in(['clinica', 'hospital'])],
            'cep'         => ['required', 'digits:8'],
            'endereco'    => ['required', 'string', 'max:200'],
            'numero'      => ['required', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro'      => ['required', 'string', 'max:100'],
            'cidade'      => ['required', 'string', 'max:100'],
            'uf'          => ['required', Rule::in(Uf::TODAS)],
            'telefone'    => ['nullable', 'digits_between:10,11'],
            'faixa_preco' => ['nullable', 'integer', 'between:1,4'],   // 05/10: escolhida pela clínica
            'horarios'    => ['nullable', 'array'],
        ];

        return $regras + HorariosDeFuncionamento::regras();
    }

    public function messages(): array
    {
        return ['cep.digits' => 'O CEP deve ter 8 dígitos.'] + HorariosDeFuncionamento::mensagens();
    }
}
