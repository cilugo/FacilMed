<?php

namespace App\Policies;

use App\Models\Avaliacao;
use App\Models\User;

/** Só o autor apaga a própria avaliação (01/10/2026). */
class AvaliacaoPolicy
{
    public function delete(User $user, Avaliacao $avaliacao): bool
    {
        return $user->ehUsuario() && $user->usuario?->id === $avaliacao->usuario_id;
    }
}
