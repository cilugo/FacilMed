<?php

namespace App\Http\Controllers;

use App\Models\Convenio;
use App\Models\Especialidade;
use App\Models\Local;
use App\Models\Medico;
use App\Models\Preco;
use App\Models\User;
use App\Models\Vinculo;
use App\Support\Localizacao;
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

        $especialidade = $this->texto($request, 'especialidade');
        $cidade = $this->texto($request, 'cidade');
        $convenio = $this->texto($request, 'convenio');

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

    /**
     * "Locais perto de você" (29/09/2026, plano do app): clínicas, hospitais e
     * consultórios do mais perto para o mais longe.
     *
     * De onde medir (Localizacao::origem): a posição que o navegador deu
     * (?lat=&lng=) ou o centro da cidade escolhida (?cidade=). Sem nenhum dos
     * dois, a lista sai em ordem alfabética, sem distância.
     *
     * Só entra local que dá para agendar (Local::agendaveis - a mesma regra da
     * busca de médicos) e, com ?especialidade=, que a ofereça. Poucos locais
     * (TCC): a distância é calculada em PHP, sem SQL de trigonometria.
     */
    public function locais(Request $request)
    {
        $especialidade = $this->texto($request, 'especialidade');
        $cidade = $this->texto($request, 'cidade');

        // 30/09: a home, para quem está logado, manda o CEP no lugar da cidade.
        // O CEP vira a cidade dele (Localizacao::cidadePorCep) e a distância
        // parte do centro dela. Cidade escolhida na lista vale mais que o CEP.
        $cep = $this->texto($request, 'cep');
        $cidadeDoCep = Localizacao::cidadePorCep($cep);
        $cidade ??= $cidadeDoCep;

        $origem = Localizacao::origem($request->query('lat'), $request->query('lng'), $cidade);

        $locais = Local::agendaveis($especialidade)
            ->with([
                'clinica',
                'vinculos' => fn ($v) => $v->agendaveis()->oferece($especialidade),
                'vinculos.medico.user',
                'vinculos.precos.especialidade',
            ])
            ->get();

        $notas = Local::notas($locais->pluck('id'));

        $resultados = $locais->map(fn (Local $local) => (object) [
            'local'     => $local,
            'distancia' => $origem ? $local->distanciaAte($origem['lat'], $origem['lng']) : null,
            'nota'      => $notas->get($local->id),
        ]);

        // Com origem: mais perto primeiro; local sem coordenada vai para o fim.
        // "<=>" com listas compara item a item: sem coordenada?, distância, nome.
        $resultados = $origem
            ? $resultados->sort(fn ($a, $b) => [$a->distancia === null, $a->distancia, $a->local->nome]
                <=> [$b->distancia === null, $b->distancia, $b->local->nome])
            : $resultados->sortBy(fn ($r) => $r->local->nome);

        return view('busca.locais', [
            'resultados'     => $resultados->values(),
            'especialidades' => Especialidade::where('ativo', true)->orderBy('nome')->get(),
            'cidades'        => Local::where('ativo', true)->select('cidade', 'uf')->distinct()->orderBy('cidade')->get(),
            'filtros'        => ['especialidade' => $especialidade, 'cidade' => $cidade],
            'origem'         => $origem,
            // Para a tela avisar "CEP X: a partir do centro de Y" ou "CEP não encontrado".
            'cep'            => $cep,
            'cidadeDoCep'    => $cidadeDoCep,
        ]);
    }

    /**
     * Filtro da URL: só texto. "?cidade[]=x" (lista, montada à mão) é
     * ignorado - antes dava erro 500 (28/09, 3ª revisão).
     */
    private function texto(Request $request, string $campo): ?string
    {
        $valor = $request->query($campo);

        return is_string($valor) && trim($valor) !== '' ? trim($valor) : null;
    }
}
