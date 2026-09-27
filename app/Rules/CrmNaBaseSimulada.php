<?php

namespace App\Rules;

use App\Services\BaseSimulada;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * O CRM + UF existe e está ativo no "CFM simulado" (base_crms)?
 *
 * DataAwareRule: a regra precisa ler o campo `uf` do mesmo formulário,
 * porque o mesmo número de CRM pode existir em estados diferentes.
 * O Laravel entrega todos os campos em setData() antes de validar.
 *
 * Uso:  'crm' => ['required', 'digits_between:4,10', new CrmNaBaseSimulada]
 */
class CrmNaBaseSimulada implements ValidationRule, DataAwareRule
{
    private array $dados = [];

    public function setData(array $data): static
    {
        $this->dados = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $erro = app(BaseSimulada::class)->conferirCrm((string) $value, $this->dados['uf'] ?? null);

        if ($erro) {
            $fail($erro);
        }
    }
}
