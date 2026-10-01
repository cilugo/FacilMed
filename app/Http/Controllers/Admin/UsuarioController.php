<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    /**
     * 01/10/2026 (pedido do Sidney): três telas separadas, no mesmo visual -
     *   - Contas ativas: só quem pode entrar, com o botão de bloquear;
     *   - Contas bloqueadas: bloqueadas pela administração, com o motivo
     *     (interno) e o botão de desbloquear;
     *   - Contas excluídas: excluídas pelo próprio paciente (LGPD). Só
     *     consulta: os dados pessoais já foram apagados e não voltam.
     * A tela é a mesma view (admin.usuarios) com $tela dizendo qual é.
     */
    public const TELAS = [
        'ativas' => [
            'rota'      => 'admin.usuarios',
            'titulo'    => 'Contas ativas',
            'subtitulo' => 'Quem pode entrar no FacilMed. Bloqueie quem não deve mais acessar.',
            'icone'     => 'users',
        ],
        'bloqueadas' => [
            'rota'      => 'admin.usuarios.bloqueadas',
            'titulo'    => 'Contas bloqueadas',
            'subtitulo' => 'Bloqueadas pela administração. A pessoa só vê que a conta está bloqueada, sem o motivo.',
            'icone'     => 'user-x',
        ],
        'excluidas' => [
            'rota'      => 'admin.usuarios.excluidas',
            'titulo'    => 'Contas excluídas',
            'subtitulo' => 'Excluídas pelo próprio paciente (LGPD). Os dados pessoais foram apagados e a conta não pode ser reativada.',
            'icone'     => 'power',
        ],
    ];

    public function index(Request $request)
    {
        // Endereços antigos (?status=bloqueado / ?status=inativo) vão para a tela nova.
        $destino = match ($request->query('status')) {
            'bloqueado' => 'admin.usuarios.bloqueadas',
            'inativo'   => 'admin.usuarios.excluidas',
            default     => null,
        };
        if ($destino) {
            return redirect()->route($destino, $request->only(['tipo', 'busca']));
        }

        return $this->listar($request, 'ativas');
    }

    public function bloqueadas(Request $request)
    {
        return $this->listar($request, 'bloqueadas');
    }

    public function excluidas(Request $request)
    {
        return $this->listar($request, 'excluidas');
    }

    private function listar(Request $request, string $tela)
    {
        // Filtro da URL: só texto (lista montada à mão era erro 500).
        $texto = fn (string $campo) => is_string($v = $request->query($campo)) && trim($v) !== '' ? trim($v) : null;

        $daTela = fn () => User::query()
            ->when($tela === 'ativas', fn ($q) => $q->where('status', 'ativo'))
            ->when($tela === 'bloqueadas', fn ($q) => $q->where('status', 'bloqueado'))
            ->when($tela === 'excluidas', fn ($q) => $q->whereNotNull('excluida_em'));

        return view('admin.usuarios', [
            'tela'      => $tela,
            'telas'     => self::TELAS,
            'contagem'  => [
                'ativas'     => User::where('status', 'ativo')->count(),
                'bloqueadas' => User::where('status', 'bloqueado')->count(),
                'excluidas'  => User::whereNotNull('excluida_em')->count(),
            ],
            'usuarios'  => $daTela()
                ->when($texto('tipo'), fn ($q, $t) => $q->where('tipo', $t))
                ->when($texto('busca'), fn ($q, $b) => $q->where(fn ($s) => $s
                    ->where('name', 'like', "%{$b}%")
                    ->orWhere('email', 'like', "%{$b}%")))
                ->when($tela === 'bloqueadas', fn ($q) => $q->orderByDesc('bloqueado_em'))
                ->when($tela === 'excluidas', fn ($q) => $q->orderByDesc('excluida_em'))
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
        // 30/09: conta excluída pelo paciente fica como está. Bloquear e depois
        // "desbloquear" a colocaria de volta como ativa.
        abort_if($user->foiExcluida(), 422, 'Essa conta foi excluída pelo próprio paciente.');

        // Consultas futuras afetadas (24/09). Paciente bloqueado: as dele.
        // Médico: as que ele atenderia. Clínica: as das unidades dela.
        $futuras = $user->consultasFuturasAfetadas()->get();

        if ($futuras->isNotEmpty() && ! $request->boolean('cancelar_consultas')) {
            return back()->with('erro', "{$user->name} tem {$futuras->count()} " .
                ($futuras->count() === 1 ? 'consulta futura' : 'consultas futuras') .
                '. Para bloquear, confirme marcando "cancelar as consultas" (os envolvidos são avisados).');
        }

        DB::transaction(function () use ($user, $dados, $futuras) {
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

            // O motivo do bloqueio NÃO vai para o paciente (AGENTS.md §6: sem detalhar).
            $futuras->each->cancelar(auth()->id(), 'Cancelada pela administração do FacilMed');
        });

        return back()->with('sucesso', 'Conta bloqueada' .
            ($futuras->isNotEmpty() ? " e {$futuras->count()} " . ($futuras->count() === 1 ? 'consulta cancelada' : 'consultas canceladas') : '') .
            '. Ela agora está em "Contas bloqueadas".');
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

        return back()->with('sucesso', 'Conta reativada. Ela voltou para "Contas ativas".');
    }
}
