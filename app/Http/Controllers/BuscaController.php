<?php

namespace App\Http\Controllers;

use App\Models\Convenio;
use App\Models\Especialidade;
use App\Models\Local;
use App\Models\Medico;
use App\Models\Preco;
use App\Models\User;
use App\Models\Vinculo;
use App\Support\Geocodificador;
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
     * "Clínicas perto de você" - a busca principal do plano novo (01/10/2026):
     * o paciente diz a especialidade e ONDE está, e vê clínicas e hospitais do
     * mais perto para o mais longe.
     *
     * Onde está (prioridade de cima para baixo):
     *   ?onde=      o que ele digitou: CEP (8 números) ou nome da cidade;
     *   ?cidade=    a cidade escolhida numa lista (endereço antigo, 29/09);
     *   ?lat=&lng=  a posição do navegador, ou a de um CEP (com ?cep=).
     * CEP: o Geocodificador acha a coordenada (ViaCEP + Nominatim, ou o
     * bairro/cidade se falhar) e a tela é REDIRECIONADA para ?lat=&lng=&cep=,
     * arredondados. Assim a posição não fica guardada em lugar nenhum
     * (AGENTS.md §3) e trocar um filtro não consulta o CEP de novo.
     *
     * Filtros: ?raio= (5, 10 ou 20 km), ?plano=1 (aceita o convênio da
     * carteirinha do paciente logado), ?convenio=ID e ?particular=1.
     *
     * Só entra local que dá para agendar (Local::agendaveis - a mesma regra da
     * busca de médicos) e, com ?especialidade=, que a ofereça. Poucos locais
     * (TCC): a distância é calculada em PHP, sem SQL de trigonometria.
     */
    public function locais(Request $request)
    {
        $especialidade = $this->texto($request, 'especialidade');
        $onde = $this->texto($request, 'onde');
        $cidade = $this->texto($request, 'cidade');
        $cep = preg_replace('/\D/', '', (string) $this->texto($request, 'cep'));
        [$lat, $lng] = [$request->query('lat'), $request->query('lng')];
        $aviso = null;

        // O que foi DIGITADO agora vale mais que a posição que veio na URL.
        if ($onde !== null) {
            $digitos = preg_replace('/\D/', '', $onde);
            [$lat, $lng, $cep, $cidade] = [null, null, strlen($digitos) === 8 ? $digitos : '', strlen($digitos) === 8 ? null : $onde];
        }

        if (strlen($cep) === 8 && Localizacao::lerCoordenada($lat, 90) === null) {
            $achou = Geocodificador::doCep($cep);
            if ($achou) {
                return redirect()->route('busca.locais', array_filter([
                    'especialidade' => $especialidade,
                    'cep'           => $cep,
                    'lat'           => round($achou['lat'], 3),
                    'lng'           => round($achou['lng'], 3),
                ] + $request->only(['raio', 'plano', 'convenio', 'particular'])));
            }
            $aviso = 'Não encontramos o CEP ' . Localizacao::formatarCep($cep) . '. Confira os números, use a sua localização ou digite a cidade.';
            $cep = '';
        }

        if ($cidade !== null && Localizacao::coordenadas($cidade) === null) {
            $aviso = "Ainda não conhecemos a cidade \"{$cidade}\" no mapa do FacilMed. Tente o CEP, a sua localização ou uma cidade do Vale do Paraíba.";
            $cidade = null;
        }

        $origem = Localizacao::origem($lat, $lng, $cidade, $cep);
        $raio = in_array((int) $request->query('raio'), Localizacao::RAIOS, true) ? (int) $request->query('raio') : null;
        $particular = $request->boolean('particular');

        // "Aceita meu plano": os convênios das carteirinhas ATIVAS do paciente
        // logado. Sem login (ou sem carteirinha), vale o ?convenio= escolhido na lista.
        $paciente = $request->user()?->ehPaciente() ? $request->user()->paciente : null;
        $meusConvenios = $paciente
            ? $paciente->planosAtivos()->with('plano')->get()->pluck('plano.convenio_id')->filter()->unique()->values()
            : collect();
        $convenioId = ctype_digit((string) $request->query('convenio')) ? (int) $request->query('convenio') : null;
        $convenios = $request->boolean('plano') && $meusConvenios->isNotEmpty()
            ? $meusConvenios
            : ($convenioId ? collect([$convenioId]) : collect());

        // A mesma condição filtra os locais E os vínculos carregados (o card
        // conta só os médicos que servem para esta busca).
        $vinculoServe = fn ($v) => $v->agendaveis()->oferece($especialidade)
            ->when($particular, fn ($q) => $q->where('vinculos.aceita_particular', true))
            ->when($convenios->isNotEmpty(), fn ($q) => $q->where('vinculos.aceita_convenio', true)
                ->whereHas('medico.convenios', fn ($c) => $c->whereIn('convenios.id', $convenios)->where('convenios.ativo', true)));

        $locais = Local::where('locais.ativo', true)
            ->whereHas('vinculos', $vinculoServe)
            ->with([
                'clinica',
                'fotos',
                'vinculos' => $vinculoServe,
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

        // Raio: só com origem (sem ela não há distância). Local sem coordenada
        // fica de fora, porque não dá para garantir que está dentro do raio.
        $foraDoRaio = 0;
        if ($origem && $raio) {
            $dentro = $resultados->filter(fn ($r) => $r->distancia !== null && $r->distancia <= $raio);
            $foraDoRaio = $resultados->count() - $dentro->count();
            $resultados = $dentro;
        }

        // Com origem: mais perto primeiro; local sem coordenada vai para o fim.
        // "<=>" com listas compara item a item: sem coordenada?, distância, nome.
        $resultados = $origem
            ? $resultados->sort(fn ($a, $b) => [$a->distancia === null, $a->distancia, $a->local->nome]
                <=> [$b->distancia === null, $b->distancia, $b->local->nome])
            : $resultados->sortBy(fn ($r) => $r->local->nome);

        return view('busca.locais', [
            'resultados'     => $resultados->values(),
            'especialidades' => Especialidade::where('ativo', true)->orderBy('nome')->get(),
            'convenios'      => Convenio::where('ativo', true)->orderBy('nome')->get(),
            'escolhida'      => $especialidade ? Especialidade::where('slug', $especialidade)->first() : null,
            'filtros'        => [
                'especialidade' => $especialidade, 'cidade' => $cidade, 'raio' => $raio, 'particular' => $particular,
                'plano' => $request->boolean('plano'), 'convenio' => $convenioId,
            ],
            'origem'         => $origem,
            'aviso'          => $aviso,
            'foraDoRaio'     => $foraDoRaio,
            'temCarteirinha' => $meusConvenios->isNotEmpty(),
            'ehPaciente'     => $paciente !== null,
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
