<?php

namespace App\Http\Requests;

use App\Services\BaseSimulada;
use App\Support\Documento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Usuário cadastra a carteirinha do plano.
 *
 * CONFERÊNCIA AUTOMÁTICA NA BASE SIMULADA (decisão do grupo, 24/09/2026):
 * o número precisa existir na tabela base_carteirinhas, no plano
 * escolhido, no CPF do usuário logado, ativa e dentro da validade.
 * Bateu → o controller grava como 'ativa'. Não bateu → erro na hora.
 *
 * A conferência roda no after(), depois das regras simples: não adianta
 * consultar a base se o plano nem foi escolhido.
 */
class CadastroCarteirinhaRequest extends FormRequest
{
    /** Preenchido no after() quando a carteirinha confere. O controller lê daqui. */
    public $registroBase = null;

    public function authorize(): bool
    {
        return $this->user()?->ehUsuario() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'numero_carteirinha' => Documento::digitos($this->input('numero_carteirinha')),
        ]);
    }

    public function rules(): array
    {
        $usuarioId = $this->user()->usuario->id;

        return [
            // Só plano ativo de convênio ativo.
            'plano_id' => [
                'required', 'integer',
                Rule::exists('planos', 'id')->where('ativo', true)
                    ->whereIn('convenio_id', fn ($q) => $q->select('id')->from('convenios')->where('ativo', true)),
            ],
            'numero_carteirinha' => [
                'required', 'digits_between:6,20',
                // A mesma carteirinha não entra duas vezes para o mesmo usuário.
                Rule::unique('usuario_planos', 'numero_carteirinha')
                    ->where('plano_id', $this->input('plano_id'))
                    ->where('usuario_id', $usuarioId),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                [$erro, $registro] = app(BaseSimulada::class)->conferirCarteirinha(
                    (int) $this->input('plano_id'),
                    $this->input('numero_carteirinha'),
                    $this->user()->usuario->cpf,
                );

                if ($erro) {
                    $validator->errors()->add('numero_carteirinha', $erro);

                    return;
                }

                $this->registroBase = $registro;
            },
        ];
    }

    public function attributes(): array
    {
        return ['plano_id' => 'plano', 'numero_carteirinha' => 'número da carteirinha'];
    }

    public function messages(): array
    {
        return [
            'plano_id.exists'                   => 'Escolha um plano disponível.',
            'numero_carteirinha.digits_between' => 'Digite só os números da carteirinha (6 a 20 dígitos).',
            'numero_carteirinha.unique'         => 'Essa carteirinha já está cadastrada na sua conta.',
        ];
    }
}
