<?php

namespace App\Rules;

use App\Services\BaseSimulada;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * O CNPJ existe e está ativo na "Receita simulada" (base_cnpjs)?
 *
 * Vem DEPOIS da App\Rules\Cnpj na lista de regras: primeiro confere o
 * dígito verificador (conta), depois se o número existe na base.
 *
 * Uso:  'cnpj' => ['required', new Cnpj, new CnpjNaBaseSimulada, ...]
 */
class CnpjNaBaseSimulada implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $erro = app(BaseSimulada::class)->conferirCnpj((string) $value);

        if ($erro) {
            $fail($erro);
        }
    }
}
