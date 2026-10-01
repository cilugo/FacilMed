<?php

namespace App\Policies;

use App\Models\Medico;
use App\Models\User;

/**
 * 01/10/2026 (plano novo do grupo): o medico so ve a agenda; quem cuida do
 * perfil dele (bio, especialidades, convenios, foto) e a clinica.
 *
 * Qual clinica? Qualquer uma onde ele atende HOJE (vinculo ativo numa
 * unidade dela). Se o mesmo medico atende em duas clinicas, as duas podem
 * editar - o perfil e um so, vale para todos os lugares (como antes, quando
 * era o proprio medico que editava). Clinica sem vinculo com ele: 403.
 */
class MedicoPolicy
{
    public function gerenciar(User $user, Medico $medico): bool
    {
        $clinicaId = $user->ehClinica() ? $user->clinica?->id : null;

        return $clinicaId !== null && $medico->vinculos()
            ->where('ativo', true)
            ->whereHas('local', fn ($l) => $l->where('clinica_id', $clinicaId))
            ->exists();
    }
}
