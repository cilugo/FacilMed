<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida CNPJ pelo digito verificador.
 *
 * Mesma logica do CPF, com dois detalhes diferentes: sao 14 digitos
 * e os pesos da soma vao de 2 a 9 ciclicamente, da direita para a
 * esquerda.
 *
 * Como no CPF: confere o numero, nao a existencia da empresa.
 */
class Cnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cnpj = preg_replace('/\D/', '', (string) $value);

        if (strlen($cnpj) !== 14) {
            $fail('O CNPJ deve ter 14 digitos.');

            return;
        }

        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            $fail('Esse CNPJ nao e valido.');

            return;
        }

        foreach ([12, 13] as $posicao) {
            $soma = 0;
            $peso = 2;

            for ($i = $posicao - 1; $i >= 0; $i--) {
                $soma += (int) $cnpj[$i] * $peso;
                $peso = ($peso === 9) ? 2 : $peso + 1;
            }

            $resto = $soma % 11;
            $digito = ($resto < 2) ? 0 : 11 - $resto;

            if ($digito !== (int) $cnpj[$posicao]) {
                $fail('Esse CNPJ nao e valido.');

                return;
            }
        }
    }
}
