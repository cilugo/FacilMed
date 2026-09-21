<?php

namespace App\Http\Requests;

use App\Rules\Cpf;
use App\Rules\SenhaPadrao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacao do cadastro de paciente.
 *
 * REGRA DO AGENTS.md: validacao mora em FormRequest, nunca so no
 * JavaScript. O JS e conforto para quem preenche - qualquer pessoa
 * abre o inspetor, apaga o `required` e envia. No prototipo antigo
 * dava para agendar consulta por R$ 0,00 exatamente assim.
 *
 * ACESSIBILIDADE: os campos `possui_deficiencia` e
 * `descricao_deficiencia` sao OPCIONAIS e so sao gravados com
 * consentimento explicito, em tabela separada. Nao existe upload de
 * laudo - ver AGENTS.md secao 2.
 */
class CadastroPacienteRequest extends FormRequest
{
    /** Rota de cadastro e publica: quem valida o acesso e o middleware 'guest'. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Tira a mascara do CPF ANTES de validar e de gravar.
     *
     * O formulario manda "123.456.789-01". Se gravar assim, o mesmo
     * CPF digitado sem ponto vira um segundo cadastro e o UNIQUE nao
     * pega. Guardar sempre so digito e formatar na exibicao.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'cpf'      => preg_replace('/\D/', '', (string) $this->input('cpf')),
            'telefone' => preg_replace('/\D/', '', (string) $this->input('telefone')),
            'email'    => trim(mb_strtolower((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],

            'password' => ['required', 'confirmed', SenhaPadrao::regra()],

            'cpf' => ['required', new Cpf, Rule::unique('pacientes', 'cpf')],

            'telefone' => ['nullable', 'digits_between:10,11'],

            // before:today porque nascer hoje ou no futuro nao acontece.
            'data_nascimento' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],

            'sexo' => ['nullable', Rule::in(['Masculino', 'Feminino', 'Prefiro nao informar'])],

            // --- Acessibilidade (opcional, LGPD art. 11) ---
            'possui_deficiencia' => ['nullable', 'boolean'],

            // So exigido se a pessoa marcou que possui. Sem isso a
            // linha e gravada vazia e nao serve para nada.
            'descricao_deficiencia' => ['nullable', 'required_if:possui_deficiencia,1', 'string', 'max:1000'],

            // O consentimento e obrigatorio QUANDO ha dado sensivel.
            // Sem ele, o dado nao pode ser gravado - nao e formalidade.
            'consentimento_acessibilidade' => ['nullable', 'required_if:possui_deficiencia,1', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'Digite seu nome completo.',
            'name.min'       => 'O nome precisa ter pelo menos 3 letras.',
            'email.email'    => 'Esse e-mail nao parece valido.',
            'email.unique'   => 'Ja existe uma conta com esse e-mail.',
            'password.confirmed' => 'As duas senhas nao sao iguais.',
            'cpf.required'   => 'Digite seu CPF.',
            'cpf.unique'     => 'Ja existe um cadastro com esse CPF.',
            'telefone.digits_between' => 'O telefone deve ter DDD + numero, com 10 ou 11 digitos.',
            'data_nascimento.before'  => 'A data de nascimento precisa ser no passado.',
            'descricao_deficiencia.required_if' => 'Conte brevemente do que voce precisa, para prepararmos o atendimento.',
            'consentimento_acessibilidade.required_if' => 'Precisamos da sua autorizacao para guardar essa informacao.',
            'consentimento_acessibilidade.accepted'    => 'Precisamos da sua autorizacao para guardar essa informacao.',
        ];
    }
}
