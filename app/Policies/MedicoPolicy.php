<?php

namespace App\Policies;

use App\Models\Medico;
use App\Models\User;

/**
 * Quem pode editar o perfil de um médico (01/10/2026).
 *
 * O médico não tem conta: quem mantém o perfil é a clínica/hospital onde
 * ele atende. Se ele atende em mais de uma clínica (ex.: Dra. Helena na
 * Vida Plena e no Santa Clara), qualquer uma delas pode editar — as duas
 * mostram o mesmo médico. Clínica sem vínculo ativo com ele não edita.
 */
class MedicoPolicy
{
    public function update(User $user, Medico $medico): bool
    {
        return $user->ehClinica() && $medico->atendeNaClinica($user->clinica?->id);
    }
}
