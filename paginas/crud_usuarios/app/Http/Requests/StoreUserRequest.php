<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    /**
     * Determina se o usuário autenticado pode fazer essa requisição.
     * Ajuste essa regra conforme a política de autorização do FacilMed
     * (ex: apenas administradores podem cadastrar outros administradores).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para cadastro de usuário.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'cpf' => ['required', 'string', 'size:14', 'unique:users,cpf'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'data_nascimento' => ['nullable', 'date', 'before:today'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'tipo_usuario' => ['required', 'in:paciente,medico,administrador'],
        ];
    }

    /**
     * Mensagens de erro personalizadas em português.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'cpf.required' => 'O CPF é obrigatório.',
            'cpf.unique' => 'Este CPF já está cadastrado.',
            'cpf.size' => 'O CPF deve estar no formato 000.000.000-00.',
            'data_nascimento.before' => 'A data de nascimento deve ser anterior a hoje.',
            'password.required' => 'A senha é obrigatória.',
            'password.confirmed' => 'A confirmação de senha não confere.',
            'tipo_usuario.required' => 'O tipo de usuário é obrigatório.',
            'tipo_usuario.in' => 'O tipo de usuário deve ser paciente, médico ou administrador.',
        ];
    }
}
