<?php

namespace App\Policies;

use App\Models\PacientePlano;
use App\Models\User;

/**
 * A carteirinha do paciente.
 *
 * O paciente gerencia a dele. O admin CONFERE (aprova ou recusa) pelo
 * codigo do comprovante da ANS, mas nao apaga nem edita o numero -
 * conferir e um ato registrado, com quem conferiu e quando.
 *
 * O medico e a clinica nao aparecem aqui: eles precisam saber se o
 * convenio e aceito, nao os dados da carteirinha.
 */
class PacientePlanoPolicy
{
    public function view(User $user, PacientePlano $plano): bool
    {
        if ($user->ehAdmin()) {
            return true;
        }

        return $user->ehPaciente() && $user->paciente?->id === $plano->paciente_id;
    }

    public function update(User $user, PacientePlano $plano): bool
    {
        return $user->ehPaciente() && $user->paciente?->id === $plano->paciente_id;
    }

    public function delete(User $user, PacientePlano $plano): bool
    {
        return $this->update($user, $plano);
    }

    /** Conferir o codigo do comprovante no site da ANS. So admin. */
    public function conferir(User $user, PacientePlano $plano): bool
    {
        return $user->ehAdmin();
    }
}
