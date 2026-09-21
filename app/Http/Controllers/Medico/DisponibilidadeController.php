<?php

namespace App\Http\Controllers\Medico;

use App\Http\Controllers\Controller;
use App\Models\Disponibilidade;
use Illuminate\Http\Request;

class DisponibilidadeController extends Controller
{
    /**
     * Blocos recorrentes por vinculo (medico + local).
     *
     * O ALMOCO NAO TEM CAMPO PROPRIO: sao dois blocos no mesmo dia,
     * 08:00-12:00 e 14:00-18:00, e o buraco entre eles e o almoco.
     * A tela precisa deixar isso obvio, senao o medico procura um
     * campo "almoco" que nao existe.
     */
    public function index()
    {
        return view('medico.disponibilidade', [
            'vinculos' => auth()->user()->medico
                ->vinculos()
                ->with('local', 'disponibilidades')
                ->get(),
            'dias' => Disponibilidade::DIAS,
        ]);
    }

    public function salvar(Request $request)
    {
        // TODO: SalvarDisponibilidadeRequest.
        // Validar: hora_fim > hora_inicio, o bloco cabe no horario de
        // funcionamento do local, e nao se sobrepoe a outro bloco do
        // mesmo vinculo e dia.
        // Validar tambem que o vinculo e DESTE medico.
    }

    public function remover(Disponibilidade $disponibilidade)
    {
        $this->authorize('delete', $disponibilidade);

        // ATENCAO: apagar bloco nao cancela consulta ja marcada dentro
        // dele. Avise na tela quantas consultas futuras existem ali.
    }
}
