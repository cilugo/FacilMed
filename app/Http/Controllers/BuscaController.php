<?php

namespace App\Http\Controllers;

use App\Models\Convenio;
use App\Models\Especialidade;
use App\Models\Local;
use App\Models\Medico;
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
     * 'verificado'. O scope visivel() cuida disso - nao escreva o where
     * a mao aqui.
     *
     * Ordem (?ordem=): avaliacao (padrão), preco (faixa de preço mais baixa
     * primeiro; quem não tem preço vai para o fim) ou nome. Paginado de 12.
     * 01/10: a tela mostra a FAIXA ($ a $$$$), não o valor.
     */
    public function index(Request $request)
    {
        $ordem = in_array($request->ordem, ['avaliacao', 'preco', 'nome'], true) ? $request->ordem : 'avaliacao';

        $especialidade = $this->texto($request, 'especialidade');
        $cidade = $this->texto($request, 'cidade');
        $convenio = $this->texto($request, 'convenio');

        $medicos = Medico::visivel()
            ->select('medicos.*')
            // Só entra quem atende em algum lugar público que oferece a
            // especialidade (na cidade, se filtrou). Sem isso aparecia médico
            // recém-cadastrado, sem clínica no ar.
            ->whereHas('vinculos', fn ($v) => $v->publicos()
                ->oferece($especialidade)
                ->when($cidade, fn ($q) => $q->whereHas('local', fn ($l) => $l->where('cidade', $cidade))))
            ->with([
                'especialidades' => fn ($e) => $e->where('ativo', true),
                // Na tela, só os lugares públicos (a mesma regra).
                'vinculos' => fn ($v) => $v->publicos(),
                'vinculos.local',
                'vinculos.medico.especialidades',
            ])
            ->when($especialidade, fn ($q, $slug) => $q->whereHas(
                'especialidades', fn ($e) => $e->where('slug', $slug)
            ))
            // Convenio esta ligado ao MEDICO, nao ao endereco
            // (decisao de 18/09/2026). Por isso o filtro e por
            // convenio_medico, e a tela exibe o aviso da recepcao.
            ->when($convenio, fn ($q, $id) => $q->whereHas(
                'convenios', fn ($c) => $c->where('convenios.id', $id)
            ))
            // Menor faixa de preço entre os lugares públicos onde ele atende
            // particular (05/10: a faixa é da unidade, escolhida pela clínica).
            ->when($ordem === 'preco', fn ($q) => $q
                ->addSelect(['menor_faixa' => Vinculo::query()
                    ->selectRaw('MIN(locais.faixa_preco)')
                    ->join('locais', 'locais.id', '=', 'vinculos.local_id')
                    ->whereColumn('vinculos.medico_id', 'medicos.id')
                    ->where('vinculos.aceita_particular', true)
                    ->whereIn('vinculos.id', Vinculo::publicos()->select('vinculos.id'))])
                ->orderByRaw('menor_faixa IS NULL')->orderBy('menor_faixa'))
            ->when($ordem === 'nome', fn ($q) => $q->orderBy('medicos.nome'))
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
     * "Locais perto de você" (29/09/2026, plano do app): clínicas e hospitais
     * do mais perto para o mais longe.
     *
     * De onde medir (Localizacao::origem): a posição que o navegador deu
     * (?lat=&lng=) ou o centro da cidade escolhida (?cidade=). Sem nenhum dos
     * dois, a lista sai em ordem alfabética, sem distância.
     *
     * Só entra local público (Local::publicos - a mesma regra da busca de
     * médicos) e, com ?especialidade=, que a ofereça. Poucos locais (TCC):
     * a distância é calculada em PHP, sem SQL de trigonometria.
     *
     * 01/10 (documento de modificações): ?convenio= filtra os locais que
     * aceitam aquele convênio — um médico daquele local, da especialidade
     * buscada, aceita o convênio (convenio_medico) e a clínica liga "aceita
     * convênio" para ele ali (vinculos.aceita_convenio).
     *
     * 07/10/2026 (trazido da main; slides da defesa: RF03 e RN07):
     *  - ?cep= (o que a pessoa DIGITOU agora): o Geocodificador acha a
     *    coordenada (ViaCEP + Nominatim, ou o bairro/cidade se falhar) e a tela
     *    é REDIRECIONADA para ?lat=&lng=&cep_origem=, arredondados. Assim a
     *    posição não fica guardada em lugar nenhum (AGENTS.md §3) e trocar um
     *    filtro depois não consulta o CEP de novo. CEP digitado vale mais que
     *    a cidade e a posição que já estavam no formulário.
     *  - ?raio= (5, 10 ou 20 km): só com origem (sem ela não há distância).
     */
    public function locais(Request $request)
    {
        $especialidade = $this->texto($request, 'especialidade');
        $cidade = $this->texto($request, 'cidade');
        $convenio = $this->texto($request, 'convenio');
        $cepDigitado = preg_replace('/\D/', '', (string) $this->texto($request, 'cep'));
        $aviso = null;

        if ($cepDigitado !== '') {
            $achou = strlen($cepDigitado) === 8 ? Geocodificador::doCep($cepDigitado) : null;

            if ($achou) {
                return redirect()->route('busca.locais', array_filter([
                    'especialidade' => $especialidade,
                    'convenio'      => $convenio,
                    'raio'          => $request->query('raio'),
                    'lat'           => round($achou['lat'], 3),
                    'lng'           => round($achou['lng'], 3),
                    'cep_origem'    => $cepDigitado,
                ], fn ($v) => is_scalar($v) && $v !== ''));
            }

            $aviso = strlen($cepDigitado) === 8
                ? 'Não encontramos o CEP ' . Localizacao::formatarCep($cepDigitado) . '. Confira os números, use a sua localização ou escolha a cidade.'
                : 'O CEP precisa ter 8 números.';
        }

        $origem = Localizacao::origem($request->query('lat'), $request->query('lng'), $cidade, $this->texto($request, 'cep_origem'));
        $raio = in_array((int) $request->query('raio'), Localizacao::RAIOS, true) ? (int) $request->query('raio') : null;

        // O mesmo filtro de vínculo para "quais locais" e "quais médicos mostrar".
        $vinculosQueServem = fn ($v) => $v->publicos()->oferece($especialidade)
            ->when($convenio, fn ($q, $id) => $q->where('vinculos.aceita_convenio', true)
                ->whereHas('medico.convenios', fn ($c) => $c->where('convenios.id', $id)));

        $locais = Local::publicos($especialidade)
            ->whereHas('vinculos', $vinculosQueServem)
            ->with([
                'clinica',
                'vinculos' => $vinculosQueServem,
                'vinculos.medico',
                'vinculos.medico.especialidades',
            ])
            ->get();

        $resultados = $locais->map(fn (Local $local) => (object) [
            'local'     => $local,
            'distancia' => $origem ? $local->distanciaAte($origem['lat'], $origem['lng']) : null,
        ]);

        // Raio: local sem coordenada fica de fora, porque não dá para garantir
        // que está dentro. A tela conta quantos ficaram fora, para a pessoa
        // saber que vale aumentar o raio.
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
            'cidades'        => Local::where('ativo', true)->select('cidade', 'uf')->distinct()->orderBy('cidade')->get(),
            'convenios'      => Convenio::where('ativo', true)->orderBy('nome')->get(),
            'filtros'        => ['especialidade' => $especialidade, 'cidade' => $cidade, 'convenio' => $convenio, 'raio' => $raio],
            'origem'         => $origem,
            'aviso'          => $aviso,
            'foraDoRaio'     => $foraDoRaio,
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
