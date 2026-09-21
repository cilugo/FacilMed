<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vinculo;

/**
 * Vinculo = "este medico atende neste local".
 *
 * Quem manda no vinculo e o dono do LOCAL, nao o medico: e a clinica
 * que decide quem atende na unidade dela. O medico autonomo e dono do
 * proprio consultorio, entao no caso dele as duas coisas coincidem.
 *
 * Local::donoUserId() ja resolve essa diferenca - use sempre ele em
 * vez de escrever o if na mao.
 */
class VinculoPolicy
{
    public function update(User $user, Vinculo $vinculo): bool
    {
        return $vinculo->local?->donoUserId() === $user->id;
    }

    /**
     * Desvincular o medico do local.
     *
     * NAO apaga a linha na pratica - o vinculo tem consultas
     * penduradas (a FK e restrictOnDelete, o banco recusaria).
     * O controller marca ativo = false. A permissao e a mesma.
     */
    public function delete(User $user, Vinculo $vinculo): bool
    {
        return $vinculo->local?->donoUserId() === $user->id;
    }
}
