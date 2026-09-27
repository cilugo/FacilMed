<?php

namespace App\Http\Requests\Medico;

use App\Models\Disponibilidade;
use App\Support\Uf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Consultório próprio do médico autônomo. O local nasce com medico_id e
 * sem clinica_id (o CHECK chk_local_um_dono garante um dono só).
 *
 * horarios[segunda][abre] = 08:00 ... dia sem horário = fechado.
 * Sem nenhum horário, assume seg-sex 08:00-18:00.
 */
class SalvarConsultorioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->ehMedico() ?? false;
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
            'cep'         => ['required', 'digits:8'],
            'endereco'    => ['required', 'string', 'max:200'],
            'numero'      => ['required', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro'      => ['required', 'string', 'max:100'],
            'cidade'      => ['required', 'string', 'max:100'],
            'uf'          => ['required', Rule::in(Uf::TODAS)],
            'telefone'    => ['nullable', 'digits_between:10,11'],
            'aceita_convenio' => ['nullable', 'boolean'],
            'horarios'    => ['nullable', 'array'],
        ];

        foreach (Disponibilidade::DIAS as $dia) {
            $regras["horarios.$dia.abre"]  = ['nullable', 'date_format:H:i', "required_with:horarios.$dia.fecha"];
            $regras["horarios.$dia.fecha"] = ['nullable', 'date_format:H:i', "required_with:horarios.$dia.abre", "after:horarios.$dia.abre"];
        }

        return $regras;
    }

    public function messages(): array
    {
        return ['cep.digits' => 'O CEP deve ter 8 dígitos.', 'horarios.*.fecha.after' => 'O horário de fechar precisa ser depois do de abrir.'];
    }
}
