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
        ], [
            'motivo.required' => 'Informe o motivo do bloqueio.',
            'motivo.min'      => 'Descreva o motivo com pelo menos 10 caracteres.',
        ]);

        abort_if($user->ehAdmin(), 403, 'Não é possível bloquear um administrador.');
        // 30/09: conta excluída pelo usuário fica como está. Bloquear e depois
        // "desbloquear" a colocaria de volta como ativa.
        abort_if($user->foiExcluida(), 422, 'Essa conta foi excluída pelo próprio usuário.');

        // 28/09: forceFill, e não update(). Esses três campos NÃO estão no
        // $fillable do User (de propósito: ninguém deve preenchê-los por
        // formulário), e o update() os descartava EM SILÊNCIO — o status
        // mudava, mas o motivo, quem bloqueou e quando nunca eram gravados.
        $user->forceFill([
            'status'          => 'bloqueado',
            'motivo_bloqueio' => $dados['motivo'],
            'bloqueado_por'   => auth()->id(),
            'bloqueado_em'    => now(),
        ])->save();

        // O motivo do bloqueio NÃO vai para a pessoa (AGENTS.md §3: sem detalhar).
        return back()->with('sucesso', 'Conta bloqueada.');
    }

    public function desbloquear(User $user)
    {
        abort_unless($user->status === 'bloqueado', 422, 'Essa conta não está bloqueada.');

        $user->forceFill([   // forceFill: ver o comentário em bloquear()
            'status'          => 'ativo',
            'motivo_bloqueio' => null,
            'bloqueado_por'   => null,
            'bloqueado_em'    => null,
        ])->save();

        return back()->with('sucesso', 'Conta reativada.');
    }
}
