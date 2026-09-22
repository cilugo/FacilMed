<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Concerns\MostraConsultasRealizadas;
use App\Http\Controllers\Controller;
use App\Services\EstatisticasDeConsultas;
use Illuminate\Http\Request;

class ConsultaController extends Controller
{
    use MostraConsultasRealizadas;

    /**
     * Consultas de todas as unidades da clínica logada.
     */
    public function index(Request $request)
    {
        $clinica = auth()->user()->clinica;

        return $this->mostrarConsultasRealizadas(
            $request,
            EstatisticasDeConsultas::daClinica($clinica),
            'clinica.consultas',
            'medico',
            'clinica.dashboard'
        );
    }
}
