<?php

namespace App\Policies;

use App\Models\AvaliacaoLocal;
use App\Models\User;

/** Só o autor edita ou exclui a avaliação que fez de um local (01/10/2026). */
class AvaliacaoLocalPolicy
{
    public function delete(User $user, AvaliacaoLocal $avaliacao): bool
    {
        return $user->ehPaciente() && $user->paciente?->id === $avaliacao->paciente_id;
    }
}
