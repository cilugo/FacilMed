<?php

namespace App\Http\Controllers\Medico;

use App\Http\Controllers\Concerns\MostraConsultasRealizadas;
use App\Http\Controllers\Controller;
use App\Services\EstatisticasDeConsultas;
use Illuminate\Http\Request;

class ConsultaController extends Controller
{
    use MostraConsultasRealizadas;

    /**
     * Consultas realizadas pelo PRÓPRIO médico. O serviço é criado com o
     * médico logado, então não há como aparecer consulta de colega.
     */
    public function index(Request $request)
    {
        $medico = auth()->user()->medico;

        return $this->mostrarConsultasRealizadas(
            $request,
            EstatisticasDeConsultas::doMedico($medico),
            'medico.consultas',
            'local',
            'medico.dashboard'
        );
    }
}
