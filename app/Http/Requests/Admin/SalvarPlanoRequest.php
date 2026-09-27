<?php

namespace App\Http\Requests\Admin;

use App\Models\Plano;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Criar plano dentro de um convênio, ou editar um plano existente.
 *
 * - Criação: a rota traz {convenio}.
 * - Edição:  a rota traz {plano}; o convênio é o do próprio plano
 *   (plano não muda de convênio — "SpSaúde Família" dentro do
 *   Horizonte Med não faria sentido, e as carteirinhas iriam junto).
 *
 * O nome é único DENTRO do convênio (índice unique convenio_id+nome
 * na migration 001600): dois convênios podem ter um plano "Família".
 */
class SalvarPlanoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->ehAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $plano = $this->route('plano');

        $this->errorBag = $plano
            ? 'plano_' . $plano->id
            : 'novoPlano_' . $this->route('convenio')?->id;
    }

    public function rules(): array
    {
        $plano      = $this->route('plano');
        $convenioId = $plano?->convenio_id ?? $this->route('convenio')?->id;

        return [
            'nome' => [
                'required', 'string', 'max:150',
                Rule::unique('planos', 'nome')
                    ->where('convenio_id', $convenioId)
                    ->ignore($plano?->id),
            ],
            'tipo'        => ['required', Rule::in(array_keys(Plano::TIPOS))],
            'abrangencia' => ['required', Rule::in(array_keys(Plano::ABRANGENCIAS))],
            'descricao'   => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nome'        => 'nome do plano',
            'abrangencia' => 'abrangência',
            'descricao'   => 'descrição',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.unique' => 'Este convênio já tem um plano com esse nome.',
        ];
    }
}
