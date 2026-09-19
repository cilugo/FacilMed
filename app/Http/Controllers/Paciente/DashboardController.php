<?php

namespace App\Http\Controllers\Paciente;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /**
     * Dashboard do paciente.
     *
     * O QUE NAO ENTRA AQUI, por mais que apareca nos mockups:
     * "Resumo da sua saude", pressao arterial, medicamentos em uso,
     * exames, receitas, atestados e documentos. Nao ha dado para isso
     * e nao pode haver leitura clinica (AGENTS.md secao 6 e secao 2).
     */
    public function index()
    {
        $paciente = auth()->user()->paciente;

        return view('paciente.dashboard', [
            'proximas' => $paciente->consultas()
                ->agendadas()
                ->whereDate('data_consulta', '>=', today())
                ->with('medico.user', 'especialidade', 'vinculo.local')
                ->orderBy('data_consulta')->orderBy('horario')
                ->limit(3)->get(),

            'historico' => $paciente->consultas()
                ->whereIn('status', ['realizada', 'nao_compareceu'])
                ->with('medico.user', 'especialidade')
                ->latest('data_consulta')
                ->limit(5)->get(),

            // Alimenta o aviso "voce tem consultas para avaliar".
            'aAvaliar' => $paciente->consultas()
                ->where('status', 'realizada')
                ->whereDoesntHave('avaliacao')
                ->count(),

            'planoAtivo' => $paciente->planosAtivos()->with('plano.convenio')->first(),
        ]);
    }
}
