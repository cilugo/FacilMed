<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgendarConsultaRequest;
use App\Models\Clinica;
use App\Models\Consulta;
use App\Models\Especialidade;
use App\Models\PacientePlano;
use App\Models\Vinculo;
use App\Services\AlocadorDeMedico;
use App\Services\CalculadoraDeHorarios;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

/**
 * O NUCLEO DO SISTEMA. Leia inteiro antes de mexer.
 *
 * As quatro camadas de protecao deste controller, da mais fraca para
 * a mais forte. Nenhuma delas substitui a outra:
 *
 *   1. A TELA so mostra horario livre.       (conforto)
 *   2. O FormRequest confere o formato.      (barra lixo)
 *   3. estaLivre() reconfere no servidor.    (barra formulario editado)
 *   4. O INDICE UNICO do banco.              (barra condicao de corrida)
 *
 * A camada 4 e a unica que funciona quando duas pessoas clicam no
 * mesmo segundo. As tres primeiras existem para o erro dela ser raro
 * o bastante para ser aceitavel.
 */
class AgendamentoController extends Controller
{
    public function __construct(
        private readonly CalculadoraDeHorarios $calculadora,
        private readonly AlocadorDeMedico $alocador,
    ) {
    }

    /**
     * Passo 1 — escolher data e horario com um medico especifico.
     */
    public function escolherHorario(Vinculo $vinculo)
    {
        abort_unless($vinculo->ativo && $vinculo->medico->status_verificacao === 'verificado', 404);

        return view('agendamento.horario', [
            'vinculo'        => $vinculo->load('medico.user', 'local', 'precos.especialidade'),
            'especialidades' => $vinculo->precos->pluck('especialidade'),
            'janelaDias'     => config('agendamento.janela_maxima_dias'),
        ]);
    }

    /**
     * Agendamento pela clinica: o paciente escolhe so a especialidade
     * e o sistema aloca um medico com horario livre.
     *
     * O NOME DO MEDICO ALOCADO APARECE ANTES DA CONFIRMACAO
     * (decisao de 18/09/2026). Se o medico cancelar depois, o paciente
     * e avisado e o sistema oferece outro.
     *
     * A consulta gerada leva origem = 'clinica'.
     */
    public function porEspecialidade(Request $request, Clinica $clinica, Especialidade $especialidade)
    {
        $data = $request->filled('data')
            ? Carbon::parse($request->input('data'))
            : null;

        // Com data: tenta aquele dia. Sem data: procura a primeira vaga.
        $alocacao = $data
            ? $this->alocador->paraData($clinica, $especialidade, $data)
            : $this->alocador->primeiraVaga($clinica, $especialidade);

        // Sem vaga nao e erro - e resposta. A tela oferece outra data.
        if ($alocacao === null) {
            return view('agendamento.sem-vaga', [
                'clinica'       => $clinica,
                'especialidade' => $especialidade,
                'dataTentada'   => $data?->toDateString(),
            ]);
        }

        $vinculo = $alocacao['vinculo'];

        return view('agendamento.horario', [
            'vinculo'        => $vinculo,
            'especialidades' => collect([$especialidade]),
            'horarios'       => $alocacao['horarios'],
            'dataEscolhida'  => $alocacao['data'] ?? $data?->toDateString(),
            'janelaDias'     => config('agendamento.janela_maxima_dias'),

            // A tela usa isto para deixar claro que o sistema escolheu
            // o medico, e para oferecer "quero outro profissional".
            'alocadoPelaClinica' => true,
            'clinica'            => $clinica,
        ]);
    }

    /**
     * Horarios livres de um vinculo numa data. Chamado pela tela ao
     * trocar de dia.
     *
     * Toda a conta esta em CalculadoraDeHorarios - este metodo so
     * traduz para JSON. Nao coloque regra aqui: a gravacao chama a
     * mesma classe, e duas regras para a mesma pergunta sempre acabam
     * divergindo (era o bug do prototipo antigo).
     */
    public function horariosDisponiveis(Request $request, Vinculo $vinculo)
    {
        $request->validate(['data' => ['required', 'date']]);

        $data = Carbon::parse($request->input('data'));

        return response()->json([
            'data'      => $data->toDateString(),
            'horarios'  => $this->calculadora->paraData($vinculo, $data),
            'duracao'   => config('agendamento.duracao_padrao_minutos'),
        ]);
    }

    /**
     * Passo 2 — tela de confirmacao. Mostra quem, onde, quando e
     * quanto, antes de gravar.
     */
    public function confirmar(Request $request, Vinculo $vinculo)
    {
        $dados = $request->validate([
            'especialidade_id' => ['required', 'integer'],
            'data_consulta'    => ['required', 'date'],
            'horario'          => ['required', 'date_format:H:i'],
            'forma_pagamento'  => ['required', 'in:particular,convenio'],
        ]);

        $data = Carbon::parse($dados['data_consulta']);

        // Reconfere antes de mostrar a tela: entre escolher o horario
        // e chegar aqui, alguem pode ter marcado.
        if (! $this->calculadora->estaLivre($vinculo, $data, $dados['horario'])) {
            return redirect()
                ->route('agendamento.horario', $vinculo)
                ->withErrors(['horario' => 'Esse horario acabou de ser preenchido. Escolha outro.']);
        }

        $especialidade = Especialidade::findOrFail($dados['especialidade_id']);

        $paciente = $request->user()->paciente;

        return view('agendamento.confirmar', [
            'vinculo'       => $vinculo->load('medico.user', 'local'),
            'especialidade' => $especialidade,
            'data'          => $data,
            'horario'       => $dados['horario'],
            'formaPagamento' => $dados['forma_pagamento'],
            'valor'         => $this->calcularValor($vinculo, $especialidade->id, $dados['forma_pagamento']),

            // Carteirinhas utilizaveis do paciente, para ele escolher.
            'planos' => $paciente?->planos()->utilizavel()->with('plano.convenio')->get() ?? collect(),

            /**
             * AVISO OBRIGATORIO NA TELA, quando for convenio:
             * "Confirme na recepcao se o seu plano e aceito neste
             * endereco."
             *
             * Nao e decorativo. O convenio esta ligado ao MEDICO, nao
             * ao endereco (decisao de modelagem registrada no
             * AGENTS.md secao 6): o medico aparece na busca por Unimed
             * em todos os lugares onde atende, inclusive onde o plano
             * nao vale. Este aviso e a mitigacao acordada.
             */
            'avisoConvenio' => $dados['forma_pagamento'] === 'convenio',
        ]);
    }

    /**
     * Grava a consulta.
     *
     * NAO confie apenas na checagem de horario livre feita na tela.
     * Entre o SELECT e o INSERT, outra pessoa pode ter marcado o mesmo
     * horario - era exatamente o bug do prototipo antigo.
     *
     * A protecao real e o indice unico
     * uq_consulta_horario_ativo (medico_id, data_consulta, horario_ativo).
     */
    public function salvar(AgendarConsultaRequest $request)
    {
        $dados    = $request->validated();
        $vinculo  = Vinculo::with('medico', 'local', 'precos')->findOrFail($dados['vinculo_id']);
        $paciente = $request->user()->paciente;

        abort_if($paciente === null, 403, 'Só paciente agenda consulta.');

        // --- Regras que o FormRequest nao tem contexto para checar ---

        if (! $vinculo->ativo || $vinculo->medico->status_verificacao !== 'verificado') {
            return back()->withErrors(['vinculo_id' => 'Esse profissional nao esta disponivel.']);
        }

        $data = Carbon::parse($dados['data_consulta']);

        if (! $this->calculadora->estaLivre($vinculo, $data, $dados['horario'])) {
            return back()->withErrors([
                'horario' => 'Esse horario nao esta mais disponivel. Escolha outro.',
            ])->withInput();
        }

        $planoId = null;

        if ($dados['forma_pagamento'] === 'convenio') {
            $erro = $this->validarCarteirinha($paciente->id, $vinculo, (int) $dados['paciente_plano_id']);

            if ($erro !== null) {
                return back()->withErrors(['paciente_plano_id' => $erro])->withInput();
            }

            $planoId = (int) $dados['paciente_plano_id'];
        }

        /**
         * O VALOR E CALCULADO AQUI, NO SERVIDOR. SEMPRE.
         *
         * No prototipo antigo ele vinha do POST, e dava para agendar
         * por R$ 0,00 editando o HTML. O campo nem existe no
         * FormRequest - se vier no formulario, nao chega ate aqui.
         */
        $valor = $this->calcularValor($vinculo, (int) $dados['especialidade_id'], $dados['forma_pagamento']);

        if ($valor === null) {
            return back()->withErrors([
                'especialidade_id' => 'Esse profissional nao tem preco definido para essa especialidade.',
            ])->withInput();
        }

        try {
            $consulta = Consulta::create([
                'paciente_id'       => $paciente->id,
                'medico_id'         => $vinculo->medico_id,
                'vinculo_id'        => $vinculo->id,
                'especialidade_id'  => (int) $dados['especialidade_id'],
                'data_consulta'     => $data->toDateString(),
                'horario'           => $dados['horario'],
                'duracao_minutos'   => $this->duracaoDoBloco($vinculo, $data, $dados['horario']),
                'forma_pagamento'   => $dados['forma_pagamento'],
                'paciente_plano_id' => $planoId,
                'valor'             => $valor,
                'status'            => 'agendada',
                'origem'            => $request->input('origem') === 'clinica' ? 'clinica' : 'medico',
                'observacoes'       => $dados['observacoes'] ?? null,
            ]);
        } catch (QueryException $e) {
            // 23000 = violacao de constraint. Aqui significa que
            // alguem gravou este mesmo horario entre a checagem e o
            // insert. Nao e bug: e a protecao funcionando.
            if ($e->getCode() === '23000') {
                return back()->withErrors([
                    'horario' => 'Esse horario acabou de ser preenchido. Escolha outro.',
                ])->withInput();
            }

            throw $e;
        }

        /**
         * TODO (bloco de e-mails): disparar a confirmacao e gravar em
         * notificacoes_enviadas com tipo 'confirmacao'.
         *
         * ⚠ Regra de deduplicacao: so mandar o lembrete de 24h se
         * faltar MAIS de 24h no momento do agendamento - senao a
         * pessoa recebe confirmacao, lembrete e "no dia" quase juntos.
         */

        return redirect()
            ->route('paciente.consultas.show', $consulta)
            ->with('sucesso', 'Consulta agendada! Voce vai receber a confirmacao por e-mail.');
    }

    // -----------------------------------------------------------------
    // Interno
    // -----------------------------------------------------------------

    /**
     * Quanto custa.
     *
     *   particular -> precos (vinculo + especialidade)
     *   convenio   -> 0,00
     *
     * ⚠ DECISAO EM ABERTO (secao 11 do relatorio): coparticipacao.
     * Hoje consulta por convenio grava valor 0. Se o grupo decidir
     * registrar coparticipacao, e aqui que muda - e vai precisar de
     * uma coluna nova em `planos`.
     */
    private function calcularValor(Vinculo $vinculo, int $especialidadeId, string $formaPagamento): ?float
    {
        if ($formaPagamento === 'convenio') {
            return 0.0;
        }

        if (! $vinculo->aceita_particular) {
            return null;
        }

        return $vinculo->precoDe($especialidadeId);
    }

    /**
     * A carteirinha serve para esta consulta?
     *
     * Tres perguntas, nessa ordem: e do paciente logado, esta ativa e
     * dentro da validade, e o medico aceita esse convenio.
     *
     * A terceira e a que mais esquece. Sem ela, a pessoa marca com
     * uma carteirinha que o profissional nao atende e so descobre na
     * recepcao.
     */
    private function validarCarteirinha(int $pacienteId, Vinculo $vinculo, int $planoId): ?string
    {
        $carteirinha = PacientePlano::with('plano')->find($planoId);

        if ($carteirinha === null || $carteirinha->paciente_id !== $pacienteId) {
            return 'Essa carteirinha nao e sua.';
        }

        if ($carteirinha->status !== 'ativa') {
            return 'Essa carteirinha ainda nao foi conferida pela nossa equipe.';
        }

        if ($carteirinha->estaVencida()) {
            return 'Essa carteirinha esta vencida.';
        }

        if (! $vinculo->aceita_convenio) {
            return 'Esse profissional nao atende por convenio neste endereco.';
        }

        $convenioId = $carteirinha->plano?->convenio_id;

        $aceita = $vinculo->medico->convenios()
            ->where('convenios.id', $convenioId)
            ->exists();

        if (! $aceita) {
            return 'Esse profissional nao atende esse convenio.';
        }

        return null;
    }

    /**
     * Duracao da consulta, tirada do bloco de disponibilidade que
     * contem esse horario.
     *
     * Precisa ser do bloco, nao do padrao: um medico pode ter
     * consultas de 30 minutos de manha e de 50 a tarde, e gravar a
     * duracao errada faz o proximo horario ser calculado errado.
     */
    private function duracaoDoBloco(Vinculo $vinculo, Carbon $data, string $horario): int
    {
        $nomeDoDia = \App\Models\Disponibilidade::DIAS[$data->dayOfWeek];

        $bloco = $vinculo->disponibilidades()
            ->where('dia_semana', $nomeDoDia)
            ->where('ativo', true)
            ->where('hora_inicio', '<=', $horario . ':00')
            ->where('hora_fim', '>', $horario . ':00')
            ->first();

        return (int) ($bloco->duracao_consulta_minutos
            ?? config('agendamento.duracao_padrao_minutos'));
    }
}
