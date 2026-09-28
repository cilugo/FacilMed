<?php

namespace App\Http\Controllers;

use App\Models\Convenio;
use App\Models\Especialidade;
use App\Models\Medico;
use App\Models\Preco;
use App\Models\User;
use App\Models\Vinculo;
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

        // Filtros da URL: só texto. "?cidade[]=x" (lista, montada à mão) é
        // ignorado - antes dava erro 500 (28/09, 3ª revisão).
        $filtro = fn (string $campo) => is_string($v = $request->query($campo)) && trim($v) !== '' ? trim($v) : null;
        $especialidade = $filtro('especialidade');
        $cidade = $filtro('cidade');
        $convenio = $filtro('convenio');

        $medicos = Medico::visivel()
            ->select('medicos.*')
            // 28/09 (3ª revisão): só entra quem tem ONDE ser agendado - um
            // vínculo que recebe agendamento e oferece a especialidade (na
            // cidade, se filtrou). Antes aparecia médico recém-cadastrado, sem
            // consultório nem clínica, e o paciente clicava e não conseguia marcar.
            ->whereHas('vinculos', fn ($v) => $v->agendaveis()
                ->oferece($especialidade)
                ->when($cidade, fn ($q) => $q->whereHas('local', fn ($l) => $l->where('cidade', $cidade))))
            ->with([
                'user',
                'especialidades' => fn ($e) => $e->where('ativo', true),
                // Na tela, só os lugares onde dá para agendar (a mesma regra).
                'vinculos' => fn ($v) => $v->agendaveis(),
                'vinculos.local',
                'vinculos.precos.especialidade',
            ])
            ->when($especialidade, fn ($q, $slug) => $q->whereHas(
                'especialidades', fn ($e) => $e->where('slug', $slug)
            ))
            // Convenio esta ligado ao MEDICO, nao ao endereco
            // (decisao de 18/09/2026). Por isso o filtro e por
            // convenio_medico, e a tela de confirmacao precisa
            // exibir o aviso da recepcao.
            ->when($convenio, fn ($q, $id) => $q->whereHas(
                'convenios', fn ($c) => $c->where('convenios.id', $id)
            ))
            // Menor preço entre os que aparecem no card: preço ativo, de
            // especialidade ativa, num lugar que recebe agendamento.
            ->when($ordem === 'preco', fn ($q) => $q
                ->addSelect(['menor_preco' => Preco::query()
                    ->selectRaw('MIN(precos.valor)')
                    ->join('vinculos', 'vinculos.id', '=', 'precos.vinculo_id')
                    ->join('especialidades', 'especialidades.id', '=', 'precos.especialidade_id')
                    ->whereColumn('vinculos.medico_id', 'medicos.id')
                    ->where('precos.ativo', true)->where('especialidades.ativo', true)
                    ->whereIn('vinculos.id', Vinculo::agendaveis()->select('vinculos.id'))])
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
            'filtros'        => ['especialidade' => $especialidade, 'cidade' => $cidade, 'convenio' => $convenio],
            'ordem'          => $ordem,
        ]);
    }
}
