<?php

namespace App\Policies;

use App\Models\Avaliacao;
use App\Models\User;

/**
 * Avaliação de médico (por consulta realizada).
 *
 * 01/10/2026 (plano novo do grupo): o paciente vê o histórico das avaliações
 * que fez, no perfil, e pode editar ou excluir cada uma. Só o AUTOR - nenhum
 * outro paciente vê ou mexe (o comentário continua privado: o médico avaliado,
 * a clínica dele e o admin leem; o autor lê o que ele mesmo escreveu).
 */
class AvaliacaoPolicy
{
    public function update(User $user, Avaliacao $avaliacao): bool
    {
        return $user->ehPaciente() && $user->paciente?->id === $avaliacao->paciente_id;
    }

    public function delete(User $user, Avaliacao $avaliacao): bool
    {
        return $this->update($user, $avaliacao);
    }
}
