<?php

namespace App\Policies;

use App\Models\UsuarioPlano;
use App\Models\User;

/**
 * A carteirinha do usuário.
 *
 * O usuário gerencia a dele. O admin CONFERE (aprova ou recusa) pelo
 * codigo do comprovante da ANS, mas nao apaga nem edita o numero -
 * conferir e um ato registrado, com quem conferiu e quando.
 *
 * O medico e a clinica nao aparecem aqui: eles precisam saber se o
 * convenio e aceito, nao os dados da carteirinha.
 */
class UsuarioPlanoPolicy
{
    public function view(User $user, UsuarioPlano $plano): bool
    {
        if ($user->ehAdmin()) {
            return true;
        }

        return $user->ehUsuario() && $user->usuario?->id === $plano->usuario_id;
    }

    public function update(User $user, UsuarioPlano $plano): bool
    {
        return $user->ehUsuario() && $user->usuario?->id === $plano->usuario_id;
    }

    public function delete(User $user, UsuarioPlano $plano): bool
    {
        return $this->update($user, $plano);
    }
}
