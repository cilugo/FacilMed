<?php

namespace App\Policies;

use App\Models\Local;
use App\Models\User;

/**
 * Local = endereco de atendimento. Pertence a uma clinica OU a um
 * medico autonomo - exatamente um dos dois, garantido por CHECK no
 * banco.
 *
 * Local::donoUserId() devolve o user_id de quem manda, seja qual for
 * o caso. Toda a autorizacao deste arquivo passa por ele.
 */
class LocalPolicy
{
    public function update(User $user, Local $local): bool
    {
        return $local->donoUserId() === $user->id;
    }

    /** Editar horario de funcionamento e editar o local. */
    public function gerenciarHorarios(User $user, Local $local): bool
    {
        return $this->update($user, $local);
    }

    /**
     * Definir preco neste local.
     *
     * Se o local pertence a uma clinica, quem define a tabela de
     * precos e a clinica - o medico contratado nao mexe no valor que
     * a unidade cobra. Se e consultorio proprio, o medico define.
     * A regra e a mesma de update, e e de proposito.
     */
    public function definirPrecos(User $user, Local $local): bool
    {
        return $this->update($user, $local);
    }
}
