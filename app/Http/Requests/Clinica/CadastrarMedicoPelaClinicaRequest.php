<?php

namespace App\Http\Requests\Clinica;

use App\Models\Medico;
use App\Rules\Cpf;
use App\Rules\CrmNaBaseSimulada;
use App\Support\Uf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Clínica cadastra (ou só vincula) um médico numa unidade dela.
 *
 * - CRM+UF já existe no PointMed → não cria outro médico: só o vínculo.
 *   Nome, CPF e especialidades são ignorados.
 * - CRM novo → precisa passar na base simulada (decisão de 24/09: CRM
 *   ativo, UF certa e nome igual ao do CRM) e aí name, cpf e
 *   especialidades são obrigatórios.
 *
 * Desde 29/09 é o ÚNICO jeito de um médico entrar no PointMed. Desde 01/10
 * o médico é só um PERFIL: não tem e-mail de login nem senha.
 */
class CadastrarMedicoPelaClinicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->ehClinica() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'crm'   => preg_replace('/\D/', '', (string) $this->input('crm')),
            'uf'    => mb_strtoupper(trim((string) $this->input('uf'))),
            'cpf'   => preg_replace('/\D/', '', (string) $this->input('cpf')),
        ]);
    }

    public function medicoExistente(): ?Medico
    {
        return Medico::where('crm', $this->input('crm'))->where('uf', $this->input('uf'))->first();
    }

    public function rules(): array
    {
        $novo = $this->medicoExistente() === null;

        $regras = [
            'crm'      => array_merge(['bail', 'required', 'digits_between:4,10'], $novo ? [new CrmNaBaseSimulada] : []),
            'uf'       => ['required', Rule::in(Uf::TODAS)],
            'local_id' => ['required', 'integer', Rule::exists('locais', 'id')
                ->where('clinica_id', $this->user()->clinica?->id)->where('ativo', true)],
            'aceita_particular' => ['nullable', 'boolean'],
            'aceita_convenio'   => ['nullable', 'boolean'],
        ];

        if ($novo) {
            $regras += [
                'name'  => ['required', 'string', 'min:3', 'max:255'],
                'cpf'   => ['required', new Cpf, Rule::unique('medicos', 'cpf')],
                'especialidades'   => ['required', 'array', 'min:1'],
                'especialidades.*' => ['integer', Rule::exists('especialidades', 'id')->where('ativo', true)],
            ];
        }

        return $regras;
    }

    public function after(): array
    {
        return [function (Validator $v) {
            $existente = $this->medicoExistente();
            if (! $existente || $v->errors()->isNotEmpty()) {
                return;
            }
            if ($existente->status_verificacao !== 'verificado') {
                $v->errors()->add('crm', 'Esse médico está com o CRM pendente ou recusado e não pode ser vinculado.');
            } elseif ($existente->vinculos()->where('local_id', $this->input('local_id'))->where('ativo', true)->exists()) {
                $v->errors()->add('local_id', 'Esse médico já atende nessa unidade.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'local_id.exists'  => 'Escolha uma unidade ativa da sua clínica.',
            'cpf.unique'       => 'Já existe um médico com esse CPF.',
            'especialidades.required' => 'Escolha pelo menos uma especialidade.',
        ];
    }
}
