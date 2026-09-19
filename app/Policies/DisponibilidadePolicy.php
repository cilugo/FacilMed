<?php

namespace App\Policies;

use App\Models\Disponibilidade;
use App\Models\User;

/**
 * Bloco recorrente de horario, preso a um vinculo.
 *
 * Quem define a propria agenda e o MEDICO, mesmo trabalhando em
 * clinica - a clinica diz o horario de funcionamento do lugar
 * (horarios_funcionamento), o medico diz quando ele esta la dentro
 * desse horario.
 */
class DisponibilidadePolicy
{
    public function update(User $user, Disponibilidade $disponibilidade): bool
    {
        return $user->ehMedico()
            && $user->medico?->id === $disponibilidade->vinculo?->medico_id;
    }

    /**
     * Apagar um bloco de disponibilidade.
     *
     * ATENCAO para o controller: apagar o bloco NAO cancela as
     * consultas ja marcadas naquele intervalo. Elas continuam de pe -
     * horario livre e calculado na hora, consulta marcada e fato
     * consumado. Se o medico quer derrubar as consultas tambem, isso
     * e um bloqueio (Bloqueio), nao uma exclusao de disponibilidade.
     */
    public function delete(User $user, Disponibilidade $disponibilidade): bool
    {
        return $this->update($user, $disponibilidade);
    }
}
