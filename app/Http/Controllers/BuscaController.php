<?php

namespace App\Http\Controllers;

use App\Models\Convenio;
use App\Models\Especialidade;
use App\Models\Medico;
use App\Models\Preco;
use App\Models\User;
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
     * Ordem (?ordem=): avaliacao (padrão), preco (menor preço particular
     * primeiro; quem não tem preço vai para o fim) ou nome. Paginado de 12.
     */
    public function index(Request $request)
    {
        $ordem = in_array($request->ordem, ['avaliacao', 'preco', 'nome'], true) ? $request->ordem : 'avaliacao';

        $medicos = Medico::visivel()
            ->select('medicos.*')
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
            ->when($ordem === 'preco', fn ($q) => $q
                ->addSelect(['menor_preco' => Preco::query()
                    ->selectRaw('MIN(precos.valor)')
                    ->join('vinculos', 'vinculos.id', '=', 'precos.vinculo_id')
                    ->whereColumn('vinculos.medico_id', 'medicos.id')
                    ->where('precos.ativo', true)->where('vinculos.ativo', true)])
                ->orderByRaw('menor_preco IS NULL')->orderBy('menor_preco'))
            ->when($ordem === 'nome', fn ($q) => $q->orderBy(
                User::select('name')->whereColumn('users.id', 'medicos.user_id')))
            ->when($ordem === 'avaliacao', fn ($q) => $q->orderByDesc('media_avaliacoes')->orderByDesc('total_avaliacoes'))
            ->paginate(12)
            ->withQueryString();

        return view('busca.index', [
            'medicos'        => $medicos,
            'especialidades' => Especialidade::where('ativo', true)->orderBy('nome')->get(),
            'convenios'      => Convenio::where('ativo', true)->orderBy('nome')->get(),
            'filtros'        => $request->only(['especialidade', 'cidade', 'convenio']),
            'ordem'          => $ordem,
        ]);
    }
}
