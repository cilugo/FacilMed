<?php

namespace App\Policies;

use App\Models\Bloqueio;
use App\Models\User;

/**
 * Ausencia do medico (ferias, congresso, imprevisto).
 *
 * 01/10/2026: quem registra e a CLINICA, sempre numa unidade dela (o
 * bloqueio tem vinculo_id). A clinica so mexe em ausencia de vinculo de
 * local dela - nunca na de outra clinica onde o mesmo medico atende.
 * Ausencia sem vinculo ("todos os lugares", do tempo em que o medico
 * cadastrava) nao tem dono unico: ninguem apaga por tela.
 */
class BloqueioPolicy
{
    public function update(User $user, Bloqueio $bloqueio): bool
    {
        return $user->ehClinica()
            && $bloqueio->vinculo?->local?->donoUserId() === $user->id;
    }

    public function delete(User $user, Bloqueio $bloqueio): bool
    {
        return $this->update($user, $bloqueio);
    }
}
