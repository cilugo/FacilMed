<?php

namespace App\Http\Requests;

use App\Rules\Cnpj;
use App\Rules\CnpjNaBaseSimulada;
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
 *
 * CNPJ CONFERIDO NA BASE SIMULADA (24/09/2026): alem do digito
 * verificador (Cnpj), o numero precisa existir e estar 'ativa' na
 * tabela base_cnpjs, que faz o papel da Receita Federal. Serve para
 * clinica e para hospital (o tipo fica na unidade: locais.tipo).
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
            // Os campos do formulário são unidade_cep e unidade_uf (antes limpava 'cep'/'uf',
            // que não existem, e o CEP digitado com hífen era recusado).
            'unidade_cep' => preg_replace('/\D/', '', (string) $this->input('unidade_cep')),
            'telefone' => preg_replace('/\D/', '', (string) $this->input('telefone')),
            'email'    => trim(mb_strtolower((string) $this->input('email'))),
            'unidade_uf'  => mb_strtoupper(trim((string) $this->input('unidade_uf'))),
        ]);
    }

    public function rules(): array
    {
        return [
            // Nome da pessoa responsavel pela conta, nao da clinica.
            'name'  => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],

            'password' => ['required', 'confirmed', SenhaPadrao::regra()],

            'cnpj' => ['bail', 'required', new Cnpj, new CnpjNaBaseSimulada, Rule::unique('clinicas', 'cnpj')],

            'razao_social'  => ['required', 'string', 'min:3', 'max:150'],
            'nome_fantasia' => ['required', 'string', 'min:2', 'max:150'],
            'descricao'     => ['nullable', 'string', 'max:2000'],
            'telefone'      => ['nullable', 'digits_between:10,11'],

            // --- Primeira unidade ---
            'unidade_nome'     => ['required', 'string', 'max:150'],
            // clinica ou hospital (24/09) - o mesmo cadastro serve para os dois.
            'unidade_tipo'     => ['required', Rule::in(['clinica', 'hospital'])],
            'unidade_cep'      => ['required', 'digits:8'],
            'unidade_endereco' => ['required', 'string', 'max:255'],
            'unidade_numero'   => ['required', 'string', 'max:20'],
            'unidade_bairro'   => ['required', 'string', 'max:100'],
            'unidade_cidade'   => ['required', 'string', 'max:100'],
            'unidade_uf'       => ['required', 'size:2', Rule::in(self::UFS)],
            'unidade_complemento' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'Digite o nome do responsável pela conta.',
            'email.unique'       => 'Já existe uma conta com esse e-mail.',
            'password.confirmed' => 'As duas senhas não são iguais.',
            'cnpj.unique'        => 'Já existe uma clínica cadastrada com esse CNPJ.',
            'razao_social.required'  => 'Digite a razão social, como está no CNPJ.',
            'nome_fantasia.required' => 'Digite o nome pelo qual a clínica é conhecida.',
            'unidade_nome.required'  => 'Dê um nome para esta unidade (ex.: "Unidade Centro").',
            'unidade_cep.digits'     => 'O CEP deve ter 8 dígitos.',
            'unidade_uf.in'          => 'Escolha um estado válido (ex.: SP).',
        ];
    }

    private const UFS = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG',
        'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
    ];
}
