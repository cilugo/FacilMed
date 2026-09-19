<?php

namespace App\Http\Requests;

use App\Rules\Cpf;
use App\Rules\SenhaPadrao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Cadastro de medico AUTONOMO - o que se cadastra sozinho e atende
 * em consultorio proprio.
 *
 * SE O GRUPO DECIDIR que so clinica cadastra medico, este arquivo e
 * a rota dele saem, e sobra so o fluxo da clinica
 * (Clinica\MedicoController). Nada mais muda: o banco ja suporta os
 * dois casos porque `locais` aceita clinica_id OU medico_id.
 *
 * O CRM AQUI NAO E VALIDADO - e so formato.
 * O medico entra com status_verificacao = 'pendente' e NAO aparece
 * em busca nenhuma ate um admin conferir no portal do CFM. O web
 * service oficial custa R$ 772/ano e exige CNPJ; por isso a
 * conferencia e humana. A tela nunca pode dizer "validado junto ao
 * CFM" - diz "verificado pela equipe FacilMed".
 */
class CadastroMedicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cpf'   => preg_replace('/\D/', '', (string) $this->input('cpf')),
            'crm'   => preg_replace('/\D/', '', (string) $this->input('crm')),
            'uf'    => mb_strtoupper(trim((string) $this->input('uf'))),
            'email' => trim(mb_strtolower((string) $this->input('email'))),
            'telefone_profissional' => preg_replace('/\D/', '', (string) $this->input('telefone_profissional')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],

            'password' => ['required', 'confirmed', SenhaPadrao::regra()],

            'cpf' => ['required', new Cpf, Rule::unique('medicos', 'cpf')],

            // CRM: 4 a 10 digitos. O numero varia por estado, entao
            // nao da para fixar tamanho.
            'crm' => ['required', 'digits_between:4,10'],

            // O par (crm, uf) e que e unico no banco: o mesmo numero
            // pode existir em estados diferentes, em medicos diferentes.
            'uf' => [
                'required',
                'size:2',
                Rule::in(self::UFS),
                Rule::unique('medicos', 'uf')->where(
                    fn ($q) => $q->where('crm', $this->input('crm'))
                ),
            ],

            // Pelo menos uma especialidade. Medico sem especialidade
            // nao aparece em busca nenhuma, porque a busca e por area.
            'especialidades'   => ['required', 'array', 'min:1'],
            'especialidades.*' => ['integer', Rule::exists('especialidades', 'id')->where('ativo', true)],

            'bio'                   => ['nullable', 'string', 'max:1000'],
            'anos_atuacao'          => ['nullable', 'integer', 'min:0', 'max:70'],
            'telefone_profissional' => ['nullable', 'digits_between:10,11'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'       => 'Ja existe uma conta com esse e-mail.',
            'password.confirmed' => 'As duas senhas nao sao iguais.',
            'cpf.unique'         => 'Ja existe um cadastro de medico com esse CPF.',
            'crm.required'       => 'Digite o numero do seu CRM.',
            'crm.digits_between' => 'O CRM deve ter entre 4 e 10 digitos, so numeros.',
            'uf.required'        => 'Escolha o estado do seu CRM.',
            'uf.in'              => 'Escolha um estado valido.',
            'uf.unique'          => 'Esse CRM ja esta cadastrado nesse estado.',
            'especialidades.required' => 'Escolha pelo menos uma especialidade.',
            'anos_atuacao.max'   => 'Confira os anos de atuacao.',
        ];
    }

    private const UFS = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG',
        'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
    ];
}
