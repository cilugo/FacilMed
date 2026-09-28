<?php

namespace App\Rules;

use App\Services\BaseSimulada;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * O CRM + UF existe, está ativo e é DESSA pessoa no "CFM simulado" (base_crms)?
 *
 * DataAwareRule: a regra precisa ler os campos `uf` e `name` do mesmo
 * formulário - o mesmo número de CRM pode existir em estados diferentes, e
 * desde 28/09 o nome também é conferido (senão dava para se cadastrar com o
 * CRM de outra pessoa). O Laravel entrega todos os campos em setData().
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
        $erro = app(BaseSimulada::class)->conferirCrm((string) $value, $this->dados['uf'] ?? null, $this->dados['name'] ?? null);

        if ($erro) {
            $fail($erro);
        }
    }
}
