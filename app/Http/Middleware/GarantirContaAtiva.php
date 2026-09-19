<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Conta bloqueada e barrada AQUI, no middleware - nao escondida na
 * interface (AGENTS.md secao 6). A mensagem diz que a conta esta
 * bloqueada, sem detalhar o motivo.
 *
 * Roda a cada requisicao: se o admin bloquear alguem que ja esta
 * logado, a proxima acao dessa pessoa ja cai fora.
 */
class GarantirContaAtiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && ! $user->estaAtivo()) {
            $mensagem = $user->estaBloqueado()
                ? 'Sua conta esta bloqueada. Entre em contato com o suporte.'
                : 'Sua conta esta inativa. Entre em contato com o suporte.';

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => $mensagem]);
        }

        return $next($request);
    }
}
