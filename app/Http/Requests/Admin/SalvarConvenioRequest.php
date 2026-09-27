<?php

namespace App\Http\Requests\Admin;

use App\Rules\Cnpj;
use App\Support\Documento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Criar OU editar convênio (a mesma regra serve para os dois).
 *
 * Quando a rota tem {convenio} é edição: o Rule::unique()->ignore()
 * deixa o convênio manter o próprio nome/CNPJ sem acusar duplicado.
 *
 * Error bag com nome (ex.: "convenio_3"): a tela tem um formulário por
 * convênio, e sem isso o erro de um aparecia em todos. O nome é
 * definido em prepareForValidation(), que roda antes da validação.
 */
class SalvarConvenioRequest extends FormRequest
{
    public function authorize(): bool
    {
        // A rota já exige 'tipo:admin'. Conferido de novo aqui porque
        // FormRequest pode ser reaproveitado em outra rota no futuro.
        return $this->user()?->ehAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $convenio = $this->route('convenio');
        $this->errorBag = $convenio ? 'convenio_' . $convenio->id : 'novoConvenio';

        $this->merge([
            // So digitos no banco (mesma convencao dos cadastros).
            'cnpj'     => $this->filled('cnpj') ? Documento::digitos($this->input('cnpj')) : null,
            'telefone' => $this->filled('telefone') ? trim($this->input('telefone')) : null,
            'email'    => $this->filled('email') ? mb_strtolower(trim($this->input('email'))) : null,
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('convenio')?->id;

        return [
            'nome'      => ['required', 'string', 'max:150', Rule::unique('convenios', 'nome')->ignore($id)],
            'cnpj'      => ['nullable', 'bail', 'digits:14', new Cnpj, Rule::unique('convenios', 'cnpj')->ignore($id)],
            'telefone'  => ['nullable', 'string', 'max:20'],
            'email'     => ['nullable', 'email', 'max:150'],
            'descricao' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nome'      => 'nome do convênio',
            'cnpj'      => 'CNPJ',
            'email'     => 'e-mail',
            'descricao' => 'descrição',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.unique' => 'Já existe um convênio com esse nome.',
            'cnpj.unique' => 'Já existe um convênio com esse CNPJ.',
            'cnpj.digits' => 'O CNPJ precisa ter 14 dígitos.',
        ];
    }
}
