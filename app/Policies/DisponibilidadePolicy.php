<?php

namespace App\Policies;

use App\Models\Disponibilidade;
use App\Models\User;

/**
 * Bloco recorrente de horario, preso a um vinculo.
 *
 * 01/10/2026 (plano novo do grupo): quem cadastra os horarios do medico e
 * a CLINICA dona do local - o medico so ve a agenda. A pergunta e a mesma
 * da VinculoPolicy: "este usuario e o dono do local deste vinculo?"
 * (Local::donoUserId). A clinica A nao mexe em bloco da clinica B, nem no
 * do mesmo medico em outro endereco.
 */
class DisponibilidadePolicy
{
    public function update(User $user, Disponibilidade $disponibilidade): bool
    {
        return $user->ehClinica()
            && $disponibilidade->vinculo?->local?->donoUserId() === $user->id;
    }

    /**
     * Apagar um bloco de disponibilidade.
     *
     * ATENCAO para o controller: apagar o bloco NAO cancela as
     * consultas ja marcadas naquele intervalo. Elas continuam de pe -
     * horario livre e calculado na hora, consulta marcada e fato
     * consumado. Para derrubar as consultas tambem, isso e uma ausencia
     * (Bloqueio), nao uma exclusao de disponibilidade.
     */
    public function delete(User $user, Disponibilidade $disponibilidade): bool
    {
        return $this->update($user, $disponibilidade);
    }
}
