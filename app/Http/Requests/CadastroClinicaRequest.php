<?php

namespace App\Http\Requests;

use App\Rules\Cnpj;
use App\Rules\SenhaPadrao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Cadastro de clinica ou hospital.
 *
 * A clinica se cadastra e DEPOIS cadastra os medicos dela, numa
 * outra tela (Clinica\MedicoController). Aqui e so a conta da
 * empresa e a primeira unidade.
 *
 * A primeira unidade entra junto de proposito: clinica sem endereco
 * nao aparece em busca nenhuma e nao recebe agendamento, entao
 * deixar para depois so cria conta morta no banco.
 */
class CadastroClinicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cnpj'     => preg_replace('/\D/', '', (string) $this->input('cnpj')),
            'cep'      => preg_replace('/\D/', '', (string) $this->input('cep')),
            'telefone' => preg_replace('/\D/', '', (string) $this->input('telefone')),
            'email'    => trim(mb_strtolower((string) $this->input('email'))),
            'uf'       => mb_strtoupper(trim((string) $this->input('uf'))),
        ]);
    }

    public function rules(): array
    {
        return [
            // Nome da pessoa responsavel pela conta, nao da clinica.
            'name'  => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],

            'password' => ['required', 'confirmed', SenhaPadrao::regra()],

            'cnpj' => ['required', new Cnpj, Rule::unique('clinicas', 'cnpj')],

            'razao_social'  => ['required', 'string', 'min:3', 'max:150'],
            'nome_fantasia' => ['required', 'string', 'min:2', 'max:150'],
            'descricao'     => ['nullable', 'string', 'max:2000'],
            'telefone'      => ['nullable', 'digits_between:10,11'],

            // --- Primeira unidade ---
            'unidade_nome'     => ['required', 'string', 'max:150'],
            'unidade_cep'      => ['required', 'digits:8'],
            'unidade_endereco' => ['required', 'string', 'max:255'],
            'unidade_numero'   => ['required', 'string', 'max:20'],
            'unidade_bairro'   => ['required', 'string', 'max:100'],
            'unidade_cidade'   => ['required', 'string', 'max:100'],
            'unidade_uf'       => ['required', 'size:2'],
            'unidade_complemento' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'Digite o nome do responsavel pela conta.',
            'email.unique'       => 'Ja existe uma conta com esse e-mail.',
            'password.confirmed' => 'As duas senhas nao sao iguais.',
            'cnpj.unique'        => 'Ja existe uma clinica cadastrada com esse CNPJ.',
            'razao_social.required'  => 'Digite a razao social, como esta no CNPJ.',
            'nome_fantasia.required' => 'Digite o nome pelo qual a clinica e conhecida.',
            'unidade_nome.required'  => 'De um nome para esta unidade (ex: "Unidade Centro").',
            'unidade_cep.digits'     => 'O CEP deve ter 8 digitos.',
        ];
    }
}
