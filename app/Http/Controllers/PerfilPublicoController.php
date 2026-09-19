<?php

namespace App\Http\Controllers;

use App\Models\Clinica;
use App\Models\Medico;

class PerfilPublicoController extends Controller
{
    /**
     * Pagina publica do medico: bio, especialidades, onde atende,
     * precos, convenios aceitos e a MEDIA das avaliacoes.
     *
     * NUNCA carregue o comentario das avaliacoes aqui. O publico ve
     * apenas a nota (AGENTS.md secao 6). O campo ja esta em $hidden
     * no model, mas nao confie so nisso: nao selecione a coluna.
     */
    public function medico(Medico $medico)
    {
        abort_unless($medico->status_verificacao === 'verificado', 404);
        abort_unless($medico->user->estaAtivo(), 404);

        $medico->load([
            'user',
            'especialidades',
            'convenios',
            'vinculos.local.horarios',
            'vinculos.precos.especialidade',
        ]);

        return view('publico.medico', [
            'medico' => $medico,
            // So a distribuicao de notas, sem comentario.
            'notas'  => $medico->avaliacoes()
                ->selectRaw('estrelas, COUNT(*) as total')
                ->groupBy('estrelas')
                ->pluck('total', 'estrelas'),
        ]);
    }

    /**
     * Pagina publica da clinica: descricao, unidades, horario de
     * funcionamento e as especialidades atendidas.
     *
     * Aqui o paciente escolhe a ESPECIALIDADE, nao o medico - o
     * sistema aloca depois e mostra o nome antes de confirmar.
     */
    public function clinica(Clinica $clinica)
    {
        abort_unless($clinica->user->estaAtivo(), 404);

        $clinica->load(['locais.horarios', 'vinculos.medico.user', 'vinculos.precos.especialidade']);

        return view('publico.clinica', [
            'clinica'        => $clinica,
            'especialidades' => $clinica->especialidades()->get(),
        ]);
    }
}
