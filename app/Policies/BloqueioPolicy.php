<?php

namespace App\Policies;

use App\Models\Bloqueio;
use App\Models\User;

/** Ferias, feriado, imprevisto. So o proprio medico mexe. */
class BloqueioPolicy
{
    public function update(User $user, Bloqueio $bloqueio): bool
    {
        return $user->ehMedico() && $user->medico?->id === $bloqueio->medico_id;
    }

    public function delete(User $user, Bloqueio $bloqueio): bool
    {
        return $this->update($user, $bloqueio);
    }
}
