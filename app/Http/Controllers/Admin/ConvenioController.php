<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalvarConvenioRequest;
use App\Http\Requests\Admin\SalvarPlanoRequest;
use App\Models\Consulta;
use App\Models\Convenio;
use App\Models\Plano;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * CRUD de convênios e planos (admin).
 *
 * CONVÊNIOS FICTÍCIOS desde 24/09/2026 — não dependem mais da ANS.
 * A tela deixa isso escrito para o avaliador: a plataforma não tem
 * contrato com nenhuma operadora de verdade.
 *
 * NADA SE APAGA AQUI, só se desativa. Motivo: apagar um convênio
 * apagaria em cascata os planos, e apagar um plano apagaria as
 * carteirinhas dos pacientes (paciente_planos é cascadeOnDelete) —
 * e as consultas por convênio perderiam a referência do plano usado.
 * Desativado, some do agendamento mas o histórico continua de pé.
 */
class ConvenioController extends Controller
{
    public function index(Request $request)
    {
        $filtro = $request->query('status', 'todos');

        $convenios = Convenio::query()
            ->with(['planos' => fn ($q) => $q->withCount('pacientePlanos')])
            ->withCount('medicos')
            ->when($filtro === 'ativos', fn ($q) => $q->where('ativo', true))
            ->when($filtro === 'inativos', fn ($q) => $q->where('ativo', false))
            ->orderByDesc('ativo')
            ->orderBy('nome')
            ->get();

        return view('admin.convenios', [
            'convenios'    => $convenios,
            'filtro'       => $filtro,
            'tipos'        => Plano::TIPOS,
            'abrangencias' => Plano::ABRANGENCIAS,
            'totais'       => [
                'convenios' => Convenio::where('ativo', true)->count(),
                'planos'    => Plano::where('ativo', true)
                    ->whereHas('convenio', fn ($q) => $q->where('ativo', true))
                    ->count(),
                'inativos'  => Convenio::where('ativo', false)->count(),
            ],
        ]);
    }

    // -----------------------------------------------------------------
    // Convênio
    // -----------------------------------------------------------------

    public function salvar(SalvarConvenioRequest $request): RedirectResponse
    {
        $convenio = Convenio::create([...$request->validated(), 'ativo' => true]);

        return redirect()
            ->route('admin.convenios')
            ->with('sucesso', "Convênio {$convenio->nome} criado. Agora cadastre os planos dele.");
    }

    public function atualizar(SalvarConvenioRequest $request, Convenio $convenio): RedirectResponse
    {
        $convenio->update($request->validated());

        return back()->with('sucesso', "Convênio {$convenio->nome} atualizado.");
    }

    /**
     * Ativa/desativa.
     *
     * Ao DESATIVAR não cancelamos nada automaticamente: as consultas
     * futuras já marcadas por esse convênio continuam de pé (o paciente
     * não pode ser surpreendido). O admin é AVISADO de quantas são, e o
     * AgendamentoController passa a recusar agendamentos novos.
     */
    public function alternar(Convenio $convenio): RedirectResponse
    {
        $convenio->update(['ativo' => ! $convenio->ativo]);

        if ($convenio->ativo) {
            return back()->with('sucesso', "Convênio {$convenio->nome} reativado.");
        }

        $futuras = $this->consultasFuturas(fn ($q) => $q->where('planos.convenio_id', $convenio->id));

        return back()->with('sucesso', "Convênio {$convenio->nome} desativado. Ele não aparece mais para agendamento."
            . ($futuras > 0 ? " Atenção: {$futuras} consulta(s) futura(s) por este convênio continuam marcadas." : ''));
    }

    // -----------------------------------------------------------------
    // Plano
    // -----------------------------------------------------------------

    public function salvarPlano(SalvarPlanoRequest $request, Convenio $convenio): RedirectResponse
    {
        $plano = $convenio->planos()->create([...$request->validated(), 'ativo' => true]);

        return back()->with('sucesso', "Plano {$plano->nome} criado em {$convenio->nome}.");
    }

    public function atualizarPlano(SalvarPlanoRequest $request, Plano $plano): RedirectResponse
    {
        $plano->update($request->validated());

        return back()->with('sucesso', "Plano {$plano->nome} atualizado.");
    }

    public function alternarPlano(Plano $plano): RedirectResponse
    {
        $plano->update(['ativo' => ! $plano->ativo]);

        if ($plano->ativo) {
            return back()->with('sucesso', "Plano {$plano->nome} reativado.");
        }

        $futuras = $this->consultasFuturas(fn ($q) => $q->where('planos.id', $plano->id));

        return back()->with('sucesso', "Plano {$plano->nome} desativado."
            . ($futuras > 0 ? " Atenção: {$futuras} consulta(s) futura(s) com este plano continuam marcadas." : ''));
    }

    // -----------------------------------------------------------------
    // Interno
    // -----------------------------------------------------------------

    /** Consultas por convênio, ainda agendadas, de hoje em diante. */
    private function consultasFuturas(callable $filtroPlano): int
    {
        return Consulta::query()
            ->join('paciente_planos', 'paciente_planos.id', '=', 'consultas.paciente_plano_id')
            ->join('planos', 'planos.id', '=', 'paciente_planos.plano_id')
            ->where('consultas.forma_pagamento', 'convenio')
            ->where('consultas.status', 'agendada')
            ->whereDate('consultas.data_consulta', '>=', today())
            ->where($filtroPlano)
            ->count();
    }
}
