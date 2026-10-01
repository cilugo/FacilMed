<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Models\Consulta;
use App\Models\Vinculo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Convênios atendidos nas unidades da clínica (item "Convênios" do
 * menu lateral).
 *
 * REGRA (README §6, 18/09): quem aceita convênio é o MÉDICO
 * (tabela convenio_medico), não o endereço. Por isso esta tela:
 *
 *   - MOSTRA o conjunto dos convênios aceitos pelos médicos da clínica,
 *     com os planos de cada um — é a "cobertura" da clínica;
 *   - DEIXA a clínica decidir, por médico e por unidade, se aquele
 *     atendimento aceita convênio (vinculos.aceita_convenio). Isso é da
 *     clínica, porque o vínculo pertence ao dono do local (VinculoPolicy);
 *   - NÃO edita a lista de convênios do médico: ela vale para todos os
 *     lugares onde ele atende. Desde 01/10/2026 ela é editada pela clínica
 *     na tela do perfil do médico (Clinica\MedicoController::salvarConvenios).
 */
class ConvenioController extends Controller
{
    public function index()
    {
        $clinica = auth()->user()->clinica;

        $vinculos = $clinica->vinculos()
            ->where('vinculos.ativo', true)
            ->with([
                'local',
                'medico.user',
                'medico.convenios' => fn ($q) => $q->orderBy('nome'),
                'medico.convenios.planos',
            ])
            ->get()
            ->sortBy(fn ($v) => $v->medico->user->name . $v->local->nome)
            ->values();

        // Cobertura: convênio -> planos ativos + médicos que atendem por ele
        // AQUI (só conta vínculo com aceita_convenio ligado).
        $cobertura = collect();
        foreach ($vinculos as $vinculo) {
            if (! $vinculo->aceita_convenio) {
                continue;
            }

            foreach ($vinculo->medico->convenios as $convenio) {
                $item = $cobertura->get($convenio->id, [
                    'convenio' => $convenio,
                    'planos'   => $convenio->planos->where('ativo', true)->values(),
                    'medicos'  => collect(),
                ]);
                $item['medicos']->put($vinculo->medico_id, $vinculo->medico->user->name);
                $cobertura->put($convenio->id, $item);
            }
        }
        $cobertura = $cobertura->sortBy(fn ($i) => [! $i['convenio']->ativo, $i['convenio']->nome])->values();

        $consultasConvenio = Consulta::query()
            ->whereIn('vinculo_id', $vinculos->pluck('id'))
            ->where('forma_pagamento', 'convenio')
            ->where('status', 'realizada')
            ->whereDate('data_consulta', '>=', today()->subDays(30))
            ->count();

        return view('clinica.convenios', [
            'vinculos'  => $vinculos,
            'cobertura' => $cobertura,
            'cartoes'   => [
                [
                    'icone'  => 'shield',
                    'tom'    => 'azul',
                    'rotulo' => 'Convênios atendidos',
                    'valor'  => $cobertura->filter(fn ($i) => $i['convenio']->ativo)->count(),
                ],
                [
                    'icone'  => 'doctors',
                    'tom'    => 'verde',
                    'rotulo' => 'Médicos que atendem convênio',
                    'valor'  => $vinculos->where('aceita_convenio', true)->pluck('medico_id')->unique()->count(),
                    'nota'   => 'de ' . $vinculos->pluck('medico_id')->unique()->count() . ' vinculados',
                ],
                [
                    'icone'  => 'calendar-check',
                    'tom'    => 'roxo',
                    'rotulo' => 'Consultas por convênio',
                    'valor'  => $consultasConvenio,
                    'nota'   => 'realizadas nos últimos 30 dias',
                ],
            ],
        ]);
    }

    /**
     * Liga/desliga "aceita convênio" de UM médico em UMA unidade.
     *
     * A Policy (VinculoPolicy::update) garante que a clínica só mexe em
     * vínculo de unidade DELA — sem isso, trocando o id no formulário,
     * uma clínica alteraria o médico de outra.
     */
    public function salvar(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'vinculo_id'      => ['required', 'integer', 'exists:vinculos,id'],
            'aceita_convenio' => ['required', 'boolean'],
        ]);

        $vinculo = Vinculo::with('local', 'medico.user')->findOrFail($dados['vinculo_id']);

        $this->authorize('update', $vinculo);

        if ($dados['aceita_convenio'] && $vinculo->medico->convenios()->doesntExist()) {
            return back()->with('erro',
                "{$vinculo->medico->user->name} ainda não cadastrou nenhum convênio. "
                . 'O médico precisa informar os convênios que aceita no perfil dele.');
        }

        $vinculo->update(['aceita_convenio' => (bool) $dados['aceita_convenio']]);

        $texto = $dados['aceita_convenio'] ? 'passa a atender' : 'deixa de atender';

        return back()->with('sucesso',
            "{$vinculo->medico->user->name} {$texto} por convênio em {$vinculo->local->nome}.");
    }
}
