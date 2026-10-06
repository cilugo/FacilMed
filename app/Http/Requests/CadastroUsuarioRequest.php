<?php

namespace App\Http\Requests;

use App\Rules\Cpf;
use App\Rules\SenhaPadrao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacao do cadastro de usuário.
 *
 * REGRA DO AGENTS.md: validacao mora em FormRequest, nunca so no
 * JavaScript. O JS e conforto para quem preenche - qualquer pessoa
 * abre o inspetor, apaga o `required` e envia. No prototipo antigo
 * dava para agendar consulta por R$ 0,00 exatamente assim.
 *
 * 01/10/2026: o cartao de acessibilidade saiu com as consultas (o dado so
 * era mostrado ao medico DA consulta; sem consulta, ficou sem finalidade).
 */
class CadastroUsuarioRequest extends FormRequest
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

            'cpf' => ['required', new Cpf, Rule::unique('usuarios', 'cpf')],

            'telefone' => ['nullable', 'digits_between:10,11'],

            // before:today porque nascer hoje ou no futuro nao acontece.
            'data_nascimento' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],

            'sexo' => ['nullable', Rule::in(['Masculino', 'Feminino', 'Prefiro nao informar'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'Digite seu nome completo.',
            'name.min'       => 'O nome precisa ter pelo menos 3 letras.',
            'email.email'    => 'Esse e-mail não parece válido.',
            'email.unique'   => 'Já existe uma conta com esse e-mail.',
            'password.confirmed' => 'As duas senhas não são iguais.',
            'cpf.required'   => 'Digite seu CPF.',
            'cpf.unique'     => 'Já existe um cadastro com esse CPF.',
            'telefone.digits_between' => 'O telefone deve ter DDD + número, com 10 ou 11 dígitos.',
            'data_nascimento.before'  => 'A data de nascimento precisa ser no passado.',
        ];
    }
}
