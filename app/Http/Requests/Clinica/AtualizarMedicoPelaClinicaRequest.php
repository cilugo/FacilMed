<?php

namespace App\Http\Requests\Clinica;

use App\Support\FotoDePerfil;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A clínica edita o perfil de um médico que atende numa unidade dela
 * (01/10/2026: o médico não tem conta; quem mantém o perfil é a clínica).
 *
 * Nome, CRM e UF NÃO mudam aqui: são o que foi conferido na base simulada
 * no cadastro. O resto — foto, bio, anos de carreira, telefone,
 * especialidades e convênios aceitos — muda.
 *
 * Quem pode: MedicoPolicy::update (vínculo ativo com esta clínica).
 */
class AtualizarMedicoPelaClinicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('medico')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'telefone_profissional' => preg_replace('/\D/', '', (string) $this->input('telefone_profissional')) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'bio'                   => ['nullable', 'string', 'max:1000'],
            'anos_atuacao'          => ['nullable', 'integer', 'min:0', 'max:70'],
            'telefone_profissional' => ['nullable', 'digits_between:10,11'],
            'foto'                  => FotoDePerfil::REGRAS,
            'remover_foto'          => ['nullable', 'boolean'],
            'especialidades'        => ['required', 'array', 'min:1'],
            'especialidades.*'      => ['integer', Rule::exists('especialidades', 'id')->where('ativo', true)],
            'principal'             => ['nullable', 'integer', 'in:' . implode(',', array_map('intval', (array) $this->input('especialidades', [])))],
            'convenios'             => ['nullable', 'array'],
            'convenios.*'           => ['integer', Rule::exists('convenios', 'id')->where('ativo', true)],
        ];
    }

    public function messages(): array
    {
        return [
            'especialidades.required' => 'Escolha pelo menos uma especialidade.',
            'principal.in'            => 'A principal precisa ser uma das especialidades marcadas.',
            'convenios.*.exists'      => 'Um dos convênios escolhidos não existe ou foi desativado.',
            'foto.image'              => 'O arquivo precisa ser uma imagem.',
            'foto.mimes'              => 'Use uma imagem JPG, PNG ou WEBP.',
            'foto.max'                => 'A imagem pode ter no máximo 2 MB.',
        ];
    }
}
