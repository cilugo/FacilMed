<?php

namespace App\Http\Controllers;

use App\Models\Convenio;
use App\Models\Especialidade;
use App\Models\Medico;
use Illuminate\Http\Request;

class BuscaController extends Controller
{
    /**
     * Busca de medicos.
     *
     * Filtros: especialidade, cidade, forma de pagamento, convenio.
     *
     * REGRA INVIOLAVEL: so entra medico com status_verificacao
     * 'verificado' e conta ativa. O scope visivel() cuida disso -
     * nao escreva o where a mao aqui.
     *
     * TODO(front): ordenacao por preco e por avaliacao.
     * TODO(back): paginar; a lista cresce com o seeder.
     */
    public function index(Request $request)
    {
        $medicos = Medico::visivel()
            ->with([
                'user',
                'especialidades',
                'vinculos.local',
                'vinculos.precos.especialidade',
            ])
            ->when($request->especialidade, fn ($q, $slug) => $q->whereHas(
                'especialidades', fn ($e) => $e->where('slug', $slug)
            ))
            ->when($request->cidade, fn ($q, $cidade) => $q->whereHas(
                'vinculos.local', fn ($l) => $l->where('cidade', $cidade)->where('ativo', true)
            ))
            // Convenio esta ligado ao MEDICO, nao ao endereco
            // (decisao de 18/09/2026). Por isso o filtro e por
            // convenio_medico, e a tela de confirmacao precisa
            // exibir o aviso da recepcao.
            ->when($request->convenio, fn ($q, $id) => $q->whereHas(
                'convenios', fn ($c) => $c->where('convenios.id', $id)
            ))
            ->orderByDesc('media_avaliacoes')
            ->paginate(12)
            ->withQueryString();

        return view('busca.index', [
            'medicos'        => $medicos,
            'especialidades' => Especialidade::where('ativo', true)->orderBy('nome')->get(),
            'convenios'      => Convenio::where('ativo', true)->orderBy('nome')->get(),
            'filtros'        => $request->only(['especialidade', 'cidade', 'convenio', 'pagamento']),
        ]);
    }
}
