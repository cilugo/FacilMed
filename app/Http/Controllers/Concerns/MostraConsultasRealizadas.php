<?php

namespace App\Http\Controllers\Concerns;

use App\Services\EstatisticasDeConsultas;
use App\Support\Formatador;
use Illuminate\Http\Request;

/**
 * A tela "Consultas realizadas" é a mesma para o médico e para a clínica;
 * muda só DE QUEM são as consultas (o serviço já nasce preso ao dono) e
 * qual ranking aparece ao lado ("por médico" na clínica, "por local" no
 * médico). Por isso a montagem fica aqui e os dois controllers só dizem
 * quem são.
 */
trait MostraConsultasRealizadas
{
    /**
     * @param string $rota        nome da rota da própria tela (para os botões 7/30/90 dias)
     * @param string $quebrarPor  'medico' ou 'local'
     * @param string $voltar      nome da rota do dashboard
     */
    protected function mostrarConsultasRealizadas(
        Request $request,
        EstatisticasDeConsultas $stats,
        string $rota,
        string $quebrarPor,
        string $voltar
    ) {
        // Só três janelas são aceitas: qualquer outro valor cai em 30.
        // O valor vem da URL, então nunca é usado sem passar por aqui.
        $dias = (int) $request->query('dias');
        $dias = in_array($dias, [7, 30, 90], true) ? $dias : 30;

        $abas = [];
        foreach ([7, 30, 90] as $opcao) {
            $abas[] = [
                'rotulo' => $opcao . ' dias',
                'url'    => route($rota, ['dias' => $opcao]),
                'ativo'  => $opcao === $dias,
            ];
        }

        return view('consultas.realizadas', [
            'relatorio' => $stats->relatorio($dias, $quebrarPor),
            'abas'      => $abas,
            'dataHoje'  => Formatador::dataExtensa(today()),
            'voltarUrl' => route($voltar),
        ]);
    }
}
