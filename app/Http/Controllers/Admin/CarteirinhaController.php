<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PacientePlano;
use App\Services\BaseSimulada;
use Illuminate\Http\Request;

class CarteirinhaController extends Controller
{
    /**
     * Desde 24/09 a carteirinha é conferida na hora pela base simulada, então
     * a fila de "pendentes" costuma estar vazia. A tela vira CONSULTA: as
     * carteirinhas mais recentes e a situação de cada uma.
     */
    public function index(Request $request)
    {
        return view('admin.carteirinhas', [
            'pendentes' => PacientePlano::where('status', 'pendente')
                ->with('paciente.user', 'plano.convenio')->oldest()->get(),
            'recentes'  => PacientePlano::query()
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->with('paciente.user', 'plano.convenio')
                ->latest()->paginate(30)->withQueryString(),
        ]);
    }

    public function aprovar(PacientePlano $pacientePlano, BaseSimulada $base)
    {
        // 28/09 (3ª revisão): aprovar confere a base simulada, como o
        // "Aprovar" do CRM. Antes o admin aprovava qualquer número, e a
        // carteirinha virava "ativa" (e servia para agendar por convênio)
        // sem existir na base, ser da pessoa ou estar na validade.
        [$erro, $registro] = $base->conferirCarteirinha(
            $pacientePlano->plano_id,
            $pacientePlano->numero_carteirinha,
            $pacientePlano->paciente?->cpf,
        );

        if ($erro) {
            return back()->with('erro', "Não dá para aprovar esta carteirinha. {$erro}");
        }

        $pacientePlano->update([
            'status'        => 'ativa',
            'validade'      => $registro->validade,   // a da base, não a digitada
            'conferido_por' => auth()->id(),
            'conferido_em'  => now(),
            'motivo_recusa' => null,
        ]);

        return back()->with('sucesso', 'Carteirinha conferida na base simulada e aprovada.');
    }

    public function recusar(Request $request, PacientePlano $pacientePlano)
    {
        $dados = $request->validate([
            'motivo' => ['required', 'string', 'min:10', 'max:255'],
        ]);

        $pacientePlano->update([
            'status'        => 'recusada',
            'conferido_por' => auth()->id(),
            'conferido_em'  => now(),
            'motivo_recusa' => $dados['motivo'],
        ]);

        return back()->with('sucesso', 'Carteirinha recusada.');
    }
}
