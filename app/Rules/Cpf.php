<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida CPF pelo digito verificador.
 *
 * POR QUE ISSO EXISTE
 * -------------------
 * O prototipo antigo so contava 11 digitos - em JS e em PHP. Isso
 * deixa passar "111.111.111-11" e qualquer sequencia inventada, e
 * como cpf e UNIQUE no banco, um cadastro errado bloqueia o CPF
 * verdadeiro de outra pessoa para sempre.
 *
 * O calculo e o oficial da Receita: dois digitos verificadores,
 * cada um sendo o resto da soma ponderada por 11.
 *
 * ATENCAO: isto confere se o NUMERO e matematicamente valido. Nao
 * confere se a pessoa existe, nem se o CPF e dela - para isso
 * precisaria de consulta na Receita, que e paga. Mesmo caso do CRM.
 */
class Cpf implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cpf = preg_replace('/\D/', '', (string) $value);

        if (strlen($cpf) !== 11) {
            $fail('O CPF deve ter 11 digitos.');

            return;
        }

        // 000.000.000-00, 111.111.111-11 etc passam no calculo dos
        // digitos, entao precisam ser barrados na mao.
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            $fail('Esse CPF nao e valido.');

            return;
        }

        foreach ([9, 10] as $posicao) {
            $soma = 0;

            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cpf[$i] * (($posicao + 1) - $i);
            }

            $resto = ($soma * 10) % 11;
            $digito = ($resto === 10) ? 0 : $resto;

            if ($digito !== (int) $cpf[$posicao]) {
                $fail('Esse CPF nao e valido.');

                return;
            }
        }
    }
}
