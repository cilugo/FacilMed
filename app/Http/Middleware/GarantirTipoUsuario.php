<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe a rota a um ou mais tipos de usuario.
 *
 *   Route::middleware(['auth', 'tipo:medico'])->group(...)
 *   Route::middleware(['auth', 'tipo:clinica,admin'])->group(...)
 *
 * ATENCAO: isto responde "que tipo de usuario e", nao "e dono disto".
 * A segunda pergunta e da Policy. Rota sem as duas e o furo classico:
 * o medico A abrindo a agenda do medico B.
 */
class GarantirTipoUsuario
{
    public function handle(Request $request, Closure $next, string ...$tipos): Response
    {
        $user = Auth::user();

        if (! $user || ! in_array($user->tipo, $tipos, true)) {
            abort(403, 'Voce nao tem acesso a esta area.');
        }

        return $next($request);
    }
}
