<?php

namespace App\Support;

/**
 * Lê o valor em reais que a pessoa DIGITOU num formulário de preço.
 *
 * POR QUE ISSO EXISTE (28/09/2026, 3ª revisão)
 * --------------------------------------------
 * Os dois formulários de preço (médico e grade da clínica) tiravam TODOS os
 * pontos do texto, pensando em "1.250,00". Só que muita gente digita o
 * centavo com ponto, como na calculadora: "150.00" virava "15000" e o
 * sistema salvava R$ 15.000,00 — e ainda dizia "salvo".
 *
 * A regra agora olha a POSIÇÃO do ponto:
 *
 *   "250"   "250,00"   "250,5"   "R$ 250,00"   -> 250 / 250.00 / 250.5 / 250.00
 *   "1.250,00"   "1.250"   "12.500"            -> ponto de milhar: 1250.00 / 1250 / 12500
 *   "150.00"   "150.5"                         -> ponto decimal: 150.00 / 150.5
 *   "1,250.00"  "12,345"  "1.25.0"  "abc"      -> null (ambíguo ou inválido)
 *
 * Ponto seguido de exatamente 3 dígitos é milhar (é como se escreve no
 * Brasil); seguido de 1 ou 2 dígitos, é centavo.
 */
final class Dinheiro
{
    /**
     * Devolve o número com ponto decimal, pronto para o banco ("1250.00"),
     * ou null quando não dá para ter certeza do valor.
     */
    public static function lerDigitado(?string $texto): ?string
    {
        $t = str_replace(['R$', ' ', "\u{00A0}"], '', trim((string) $texto));

        // Vírgula é o centavo; pontos, se houver, só podem ser de milhar.
        if (preg_match('/^(\d{1,3}(\.\d{3})+|\d+),\d{1,2}$/', $t)) {
            return str_replace(',', '.', str_replace('.', '', $t));
        }

        // Sem vírgula, só pontos de milhar: "1.250", "12.500".
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $t)) {
            return str_replace('.', '', $t);
        }

        // Inteiro ou ponto de centavo: "150", "150.00", "150.5".
        if (preg_match('/^\d+(\.\d{1,2})?$/', $t)) {
            return $t;
        }

        return null;
    }
}
