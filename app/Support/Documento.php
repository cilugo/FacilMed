<?php

namespace App\Support;

/**
 * CPF, CNPJ e CRM: limpar e formatar.
 *
 * Convenção do projeto (ver CadastroUsuarioRequest): no banco fica SÓ
 * DÍGITO; a máscara é só na tela. Use Documento::cpf($x) na view.
 */
class Documento
{
    public static function digitos(?string $valor): string
    {
        return preg_replace('/\D/', '', (string) $valor);
    }

    public static function cpf(?string $valor): string
    {
        $d = self::digitos($valor);

        return strlen($d) === 11
            ? substr($d, 0, 3) . '.' . substr($d, 3, 3) . '.' . substr($d, 6, 3) . '-' . substr($d, 9, 2)
            : (string) $valor;
    }

    public static function cnpj(?string $valor): string
    {
        $d = self::digitos($valor);

        return strlen($d) === 14
            ? substr($d, 0, 2) . '.' . substr($d, 2, 3) . '.' . substr($d, 5, 3) . '/' . substr($d, 8, 4) . '-' . substr($d, 12, 2)
            : (string) $valor;
    }
}
