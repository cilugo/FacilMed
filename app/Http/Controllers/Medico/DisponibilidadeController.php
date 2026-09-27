<?php

namespace App\Http\Controllers\Medico;

use App\Http\Controllers\Controller;
use App\Http\Requests\Medico\SalvarDisponibilidadeRequest;
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
            'duracoes' => SalvarDisponibilidadeRequest::DURACOES,
        ]);
    }

    public function salvar(SalvarDisponibilidadeRequest $request)
    {
        Disponibilidade::create($request->validated() + ['ativo' => true]);

        return back()->with('sucesso', 'Horário de atendimento adicionado.');
    }

    /**
     * Apagar bloco NÃO cancela consulta já marcada dentro dele: a mensagem
     * diz quantas continuam de pé, para o médico decidir na agenda.
     */
    public function remover(Disponibilidade $disponibilidade)
    {
        $this->authorize('delete', $disponibilidade);

        $futuras = $disponibilidade->consultasFuturasDentro()->count();
        $disponibilidade->delete();

        $msg = 'Horário removido. Ele não aparece mais para agendamento.';
        if ($futuras > 0) {
            $msg .= " Atenção: {$futuras} " . ($futuras === 1 ? 'consulta já marcada continua' : 'consultas já marcadas continuam')
                . ' na sua agenda — cancele por lá se não for atender.';
        }

        return back()->with('sucesso', $msg);
    }
}
