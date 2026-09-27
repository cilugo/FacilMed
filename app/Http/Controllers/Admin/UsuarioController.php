<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
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
            'cancelar_consultas' => ['nullable', 'boolean'],
        ], [
            'motivo.required' => 'Informe o motivo do bloqueio.',
            'motivo.min'      => 'Descreva o motivo com pelo menos 10 caracteres.',
        ]);

        abort_if($user->ehAdmin(), 403, 'Não é possível bloquear um administrador.');

        // Consultas futuras afetadas (24/09). Paciente bloqueado: as dele.
        // Médico: as que ele atenderia. Clínica: as das unidades dela.
        $futuras = $user->consultasFuturasAfetadas()->get();

        if ($futuras->isNotEmpty() && ! $request->boolean('cancelar_consultas')) {
            return back()->with('erro', "{$user->name} tem {$futuras->count()} " .
                ($futuras->count() === 1 ? 'consulta futura' : 'consultas futuras') .
                '. Para bloquear, confirme marcando "cancelar as consultas" (os envolvidos são avisados).');
        }

        DB::transaction(function () use ($user, $dados, $futuras) {
            $user->update([
                'status'          => 'bloqueado',
                'motivo_bloqueio' => $dados['motivo'],
                'bloqueado_por'   => auth()->id(),
                'bloqueado_em'    => now(),
            ]);

            // O motivo do bloqueio NÃO vai para o paciente (AGENTS.md §6: sem detalhar).
            $futuras->each->cancelar(auth()->id(), 'Cancelada pela administração do FacilMed');
        });

        return back()->with('sucesso', 'Conta bloqueada' .
            ($futuras->isNotEmpty() ? " e {$futuras->count()} " . ($futuras->count() === 1 ? 'consulta cancelada.' : 'consultas canceladas.') : '.'));
    }

    public function desbloquear(User $user)
    {
        abort_unless($user->status === 'bloqueado', 422, 'Essa conta não está bloqueada.');

        $user->update([
            'status'          => 'ativo',
            'motivo_bloqueio' => null,
            'bloqueado_por'   => null,
            'bloqueado_em'    => null,
        ]);

        return back()->with('sucesso', 'Conta reativada.');
    }
}
