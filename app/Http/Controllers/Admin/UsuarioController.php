<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.usuarios', [
            'usuarios' => User::query()
                ->when($request->tipo, fn ($q, $t) => $q->where('tipo', $t))
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->when($request->busca, fn ($q, $b) => $q->where(fn ($s) => $s
                    ->where('name', 'like', "%{$b}%")
                    ->orWhere('email', 'like', "%{$b}%")))
                ->latest()
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    /**
     * Bloqueio EXIGE motivo. Bloqueio sem registro de quem bloqueou e
     * por que e ingovernavel - ninguem sabe se pode desbloquear.
     *
     * O middleware GarantirContaAtiva roda em toda requisicao, entao
     * quem ja esta logado cai fora na proxima acao.
     */
    public function bloquear(Request $request, User $user)
    {
        $dados = $request->validate([
            'motivo' => ['required', 'string', 'min:10', 'max:255'],
        ]);

        abort_if($user->ehAdmin(), 403, 'Nao e possivel bloquear um administrador.');

        $user->update([
            'status'          => 'bloqueado',
            'motivo_bloqueio' => $dados['motivo'],
            'bloqueado_por'   => auth()->id(),
            'bloqueado_em'    => now(),
        ]);

        // TODO: decidir o que acontece com as consultas futuras dele.
        // Bloquear um medico deixa pacientes com consulta marcada.

        return back()->with('sucesso', 'Conta bloqueada.');
    }

    public function desbloquear(User $user)
    {
        $user->update([
            'status'          => 'ativo',
            'motivo_bloqueio' => null,
            'bloqueado_por'   => null,
            'bloqueado_em'    => null,
        ]);

        return back()->with('sucesso', 'Conta reativada.');
    }
}
