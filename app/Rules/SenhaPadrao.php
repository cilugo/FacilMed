<?php

namespace App\Rules;

use Illuminate\Validation\Rules\Password;

/**
 * A POLITICA DE SENHA DO FACILMED MORA AQUI, E SO AQUI.
 *
 * Cadastro de paciente, de medico, de clinica, troca de senha e
 * recuperacao de senha chamam este metodo. Mudou a regra? Muda a
 * linha abaixo e vale para os cinco lugares de uma vez.
 *
 * REGRA ATUAL: minimo 8 caracteres, sem maximo, sem exigir
 * maiuscula nem simbolo.
 *
 * POR QUE NAO TEM TETO
 * --------------------
 * Teto de tamanho corta justamente a senha longa, que e a mais
 * forte. "meucachorrochamabolinha" e melhor que "Ab3$x!" e tem 23
 * caracteres - um limite de 10 proibiria a boa e aceitaria a ruim.
 * O unico limite real e tecnico: o bcrypt ignora o que passa de 72
 * bytes, entao o teto de 72 existe so para a pessoa nao achar que
 * digitou uma senha maior do que o sistema guardou.
 *
 * POR QUE NAO EXIGE MAIUSCULA E SIMBOLO
 * -------------------------------------
 * Exigencia de composicao produz senha previsivel: quase todo mundo
 * responde com a mesma forma - inicial maiuscula, numero no fim,
 * "!" no final. Comprimento vence complexidade.
 *
 * COMO MUDAR SE O GRUPO DECIDIR OUTRA COISA
 * -----------------------------------------
 *   6 a 10 caracteres:  Password::min(6)->max(10)
 *   exigir simbolo:     ->symbols()
 *   exigir numero:      ->numbers()
 *   barrar senha vazada: ->uncompromised()  (consulta a API do
 *                        Have I Been Pwned - precisa de internet)
 */
class SenhaPadrao
{
    /** Limite tecnico do bcrypt, em bytes. Nao e escolha de produto. */
    public const MAXIMO_BCRYPT = 72;

    /**
     * Uso:
     *   'password' => ['required', 'confirmed', SenhaPadrao::regra()],
     *
     * O 'confirmed' exige um campo password_confirmation no formulario.
     */
    public static function regra(): Password
    {
        return Password::min(8)->max(self::MAXIMO_BCRYPT);
    }
}
