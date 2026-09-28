<?php

namespace App\Http\Requests\Medico;

use App\Rules\CrmNaBaseSimulada;
use App\Services\BaseSimulada;
use App\Support\Uf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Dados do médico. CRM/UF podem mudar, mas o novo par passa de novo pela
 * base simulada (senão bastava cadastrar um CRM válido, ser verificado e
 * trocar pelo número que quisesse). Desde 28/09 a base também confere o
 * NOME: por isso trocar só o nome também passa por ela.
 */
class AtualizarPerfilMedicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->ehMedico() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'crm' => preg_replace('/\D/', '', (string) $this->input('crm')),
            'uf'  => mb_strtoupper(trim((string) $this->input('uf'))),
            'telefone_profissional' => preg_replace('/\D/', '', (string) $this->input('telefone_profissional')),
        ]);
    }

    public function crmMudou(): bool
    {
        $m = $this->user()->medico;

        return $this->input('crm') !== $m->crm || $this->input('uf') !== $m->uf;
    }

    /** "Dra. Helena Navarro" -> "Helena Navarro" não conta como mudança. */
    public function nomeMudou(): bool
    {
        return BaseSimulada::nomeComparavel($this->input('name')) !== BaseSimulada::nomeComparavel($this->user()->name);
    }

    public function rules(): array
    {
        $medico = $this->user()->medico;

        return [
            'name'  => ['required', 'string', 'min:3', 'max:255'],
            'crm'   => array_merge(['bail', 'required', 'digits_between:4,10'], $this->crmMudou() || $this->nomeMudou() ? [new CrmNaBaseSimulada] : []),
            'uf'    => ['required', Rule::in(Uf::TODAS),
                Rule::unique('medicos', 'uf')->where(fn ($q) => $q->where('crm', $this->input('crm')))->ignore($medico->id)],
            'bio'   => ['nullable', 'string', 'max:1000'],
            'anos_atuacao' => ['nullable', 'integer', 'min:0', 'max:70'],
            'telefone_profissional' => ['nullable', 'digits_between:10,11'],
        ];
    }

    public function messages(): array
    {
        return ['uf.unique' => 'Esse CRM já está cadastrado nesse estado.'];
    }
}
