<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Médico cadastrado pela clínica entra com SENHA TEMPORÁRIA (a clínica
 * viu essa senha). Até trocar, só consegue abrir o perfil, trocar a
 * senha e sair. Assim a clínica nunca sabe a senha definitiva.
 */
class ExigirTrocaDeSenha
{
    private const LIBERADAS = ['medico.perfil', 'password.update', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->ehMedico() && $user->medico?->senha_temporaria
            && ! in_array($request->route()?->getName(), self::LIBERADAS, true)) {
            return redirect()->route('medico.perfil')
                ->with('erro', 'Você entrou com a senha temporária que a clínica recebeu. Crie a sua senha para continuar.');
        }

        return $next($request);
    }
}
