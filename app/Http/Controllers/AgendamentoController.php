<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgendarConsultaRequest;
use App\Models\Clinica;
use App\Models\Consulta;
use App\Models\Especialidade;
use App\Models\Paciente;
use App\Models\PacientePlano;
use App\Models\Vinculo;
use App\Services\AlocadorDeMedico;
use App\Services\CalculadoraDeHorarios;
use App\Services\Notificador;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        // 28/09: a mesma regra da CalculadoraDeHorarios (conta bloqueada também some).
        abort_unless($vinculo->recebeAgendamento(), 404);

        return view('agendamento.horario', [
            'vinculo'        => $vinculo->load('medico.user', 'local', 'precos.especialidade'),
            'especialidades' => $vinculo->especialidadesOferecidas(),
            'janelaDias'     => config('agendamento.janela_maxima_dias'),
            // 24/09: os proximos dias com vaga, para a tela abrir ja mostrando
            // horarios (sem o paciente ter que adivinhar uma data).
            'diasComVaga'    => $this->calculadora->proximosDias($vinculo, 10),
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
        // 28/09: "?data=banana" dava erro 500 no Carbon::parse.
        $request->validate(['data' => ['nullable', 'date']]);

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
                ->route('agendamento.horario', array_filter(['vinculo' => $vinculo->id, 'remarcar' => $request->input('remarcar_consulta_id')]))
                ->withErrors(['horario' => 'Esse horário acabou de ser preenchido. Escolha outro.']);
        }

        $vinculo->loadMissing('precos.especialidade');
        if (! $vinculo->ofereceEspecialidade((int) $dados['especialidade_id'])) {
            return redirect()
                ->route('agendamento.horario', array_filter(['vinculo' => $vinculo->id, 'remarcar' => $request->input('remarcar_consulta_id')]))
                ->withErrors(['especialidade_id' => 'Esse profissional não atende essa especialidade neste endereço.']);
        }

        $especialidade = Especialidade::findOrFail($dados['especialidade_id']);

        $paciente = $request->user()->paciente;

        // 29/09: o paciente já tem outra consulta nesse horário (com outro médico)?
        // Avisa já aqui, antes da tela de confirmação. salvar() confere de novo.
        $conflito = $paciente?->consultaNoHorario(
            $this->juntar($data, $dados['horario']),
            $this->duracaoDoBloco($vinculo, $data, $dados['horario']),
            (int) $request->input('remarcar_consulta_id') ?: null,
        );

        if ($conflito !== null) {
            return redirect()
                ->route('agendamento.horario', array_filter(['vinculo' => $vinculo->id, 'remarcar' => $request->input('remarcar_consulta_id')]))
                ->withErrors(['horario' => $this->mensagemDeConflito($conflito)]);
        }

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
        $vinculo  = Vinculo::with('medico.user', 'local.clinica.user', 'precos.especialidade')->findOrFail($dados['vinculo_id']);
        $paciente = $request->user()->paciente;

        abort_if($paciente === null, 403, 'Só paciente agenda consulta.');

        // --- Regras que o FormRequest nao tem contexto para checar ---

        if (! $vinculo->recebeAgendamento()) {
            return back()->withErrors(['vinculo_id' => 'Esse profissional não está disponível.']);
        }

        // A especialidade precisa ser uma das oferecidas NESTE vinculo (as mesmas que a
        // tela de horario lista, vindas de precos). Sem isso, por convenio (valor 0, sem
        // consulta de preco) dava para marcar Dermatologia com uma cardiologista.
        // 28/09: e o preço precisa estar ATIVO — antes bastava existir a linha.
        if (! $vinculo->ofereceEspecialidade((int) $dados['especialidade_id'])) {
            return back()->withErrors([
                'especialidade_id' => 'Esse profissional não atende essa especialidade neste endereço.',
            ])->withInput();
        }

        $data = Carbon::parse($dados['data_consulta']);

        if (! $this->calculadora->estaLivre($vinculo, $data, $dados['horario'])) {
            return back()->withErrors([
                'horario' => 'Esse horário não está mais disponível. Escolha outro.',
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
                'especialidade_id' => 'Esse profissional não tem preço definido para essa especialidade.',
            ])->withInput();
        }

        // Remarcacao: a antiga precisa ser deste paciente e ainda poder ser cancelada.
        $antiga = null;
        if (! empty($dados['remarcar_consulta_id'])) {
            $antiga = Consulta::find($dados['remarcar_consulta_id']);
            if (! $antiga || $request->user()->cannot('cancelar', $antiga) || ! $antiga->podeSerCancelada()) {
                return back()->withErrors(['horario' => 'Essa consulta não pode mais ser remarcada.'])->withInput();
            }
        }

        $duracao  = $this->duracaoDoBloco($vinculo, $data, $dados['horario']);
        $conflito = null;   // preenchido lá dentro da transação (o "&$conflito" do use)

        try {
            $consulta = DB::transaction(function () use ($antiga, $paciente, $vinculo, $dados, $data, $duracao, $planoId, $valor, $request, &$conflito) {
            /**
             * 29/09: o paciente não pode ter duas consultas no mesmo horário.
             *
             * lockForUpdate() = "SELECT ... FOR UPDATE": segura a linha deste
             * paciente até o fim da transação. Se ele mandar dois agendamentos
             * no mesmo segundo (duas abas, médicos diferentes), o segundo ESPERA
             * o primeiro gravar e só então confere — e aí enxerga a consulta nova.
             * Sem a trava, os dois conferiam juntos, não viam conflito e gravavam.
             * (O índice único do banco não ajuda aqui: ele é por médico.)
             */
            Paciente::whereKey($paciente->id)->lockForUpdate()->first();

            $conflito = $paciente->consultaNoHorario($this->juntar($data, $dados['horario']), $duracao, $antiga?->id);

            if ($conflito !== null) {
                return null;
            }

            $nova = Consulta::create([
                'paciente_id'       => $paciente->id,
                'medico_id'         => $vinculo->medico_id,
                'vinculo_id'        => $vinculo->id,
                'especialidade_id'  => (int) $dados['especialidade_id'],
                'data_consulta'     => $data->toDateString(),
                'horario'           => $dados['horario'],
                'duracao_minutos'   => $duracao,
                'forma_pagamento'   => $dados['forma_pagamento'],
                'paciente_plano_id' => $planoId,
                'valor'             => $valor,
                'status'            => 'agendada',
                'origem'            => $request->input('origem') === 'clinica' ? 'clinica' : 'medico',
                'observacoes'       => $dados['observacoes'] ?? null,
            ]);

            if ($antiga) {
                $antiga->cancelar($request->user()->id, 'Remarcada para ' . $data->format('d/m/Y') . ' às ' . $dados['horario'], remarcacao: true);
            }

            return $nova;
            });
        } catch (QueryException $e) {
            // 23000 = violacao de constraint. Aqui significa que
            // alguem gravou este mesmo horario entre a checagem e o
            // insert. Nao e bug: e a protecao funcionando.
            if ($e->getCode() === '23000') {
                return back()->withErrors([
                    'horario' => 'Esse horário acabou de ser preenchido. Escolha outro.',
                ])->withInput();
            }

            throw $e;
        }

        if ($consulta === null) {
            return back()->withErrors(['horario' => $this->mensagemDeConflito($conflito)])->withInput();
        }

        /**
         * E-mail de confirmacao: Notificador (24/09), logo abaixo. O lembrete
         * de 24h so sai se a consulta foi marcada com MAIS de 24h de
         * antecedencia (EnviarLembretes) - senao a pessoa recebe
         * confirmacao e lembrete quase juntos.
         */

        app(Notificador::class)->enviar($consulta, 'confirmacao', 'paciente');

        return redirect()
            ->route('paciente.consultas.show', $consulta)
            ->with('sucesso', $antiga
                ? 'Consulta remarcada! O horário antigo foi liberado.'
                : 'Consulta agendada! Você vai receber a confirmação por e-mail.');
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
     * Quatro perguntas, nessa ordem: e do paciente logado, esta ativa e
     * dentro da validade, o plano/convenio continuam ativos, e o medico
     * aceita esse convenio.
     *
     * A terceira e a que mais esquece. Sem ela, a pessoa marca com
     * uma carteirinha que o profissional nao atende e so descobre na
     * recepcao.
     */
    private function validarCarteirinha(int $pacienteId, Vinculo $vinculo, int $planoId): ?string
    {
        $carteirinha = PacientePlano::with('plano.convenio')->find($planoId);

        if ($carteirinha === null || $carteirinha->paciente_id !== $pacienteId) {
            return 'Essa carteirinha não é sua.';
        }

        if ($carteirinha->status !== 'ativa') {
            return 'Essa carteirinha não está ativa.';
        }

        if ($carteirinha->estaVencida()) {
            return 'Essa carteirinha está vencida.';
        }

        // 24/09: o admin agora pode desativar convenio e plano. Sem esta
        // checagem, a carteirinha de um plano desativado continuava
        // passando e a consulta era marcada normalmente.
        if (! $carteirinha->plano?->ativo || ! $carteirinha->plano?->convenio?->ativo) {
            return 'Esse plano não está mais disponível para agendamento.';
        }

        if (! $vinculo->aceita_convenio) {
            return 'Esse profissional não atende por convênio neste endereço.';
        }

        $convenioId = $carteirinha->plano?->convenio_id;

        $aceita = $vinculo->medico->convenios()
            ->where('convenios.id', $convenioId)
            ->exists();

        if (! $aceita) {
            return 'Esse profissional não atende esse convênio.';
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

    /** Data do dia + "HH:MM" num Carbon só. */
    private function juntar(Carbon $data, string $horario): Carbon
    {
        return Carbon::parse($data->toDateString() . ' ' . $horario);
    }

    /**
     * 29/09: diz QUAL consulta atrapalha, para o paciente decidir (trocar o
     * horário ou cancelar a outra). Tom do README §8: direto, frase curta.
     */
    private function mensagemDeConflito(Consulta $outra): string
    {
        return sprintf(
            'Você já tem uma consulta nesse horário: %s às %s, com %s, em %s. Escolha outro horário.',
            $outra->inicio->format('d/m'),
            $outra->inicio->format('H:i'),
            $outra->medico->user->name,
            $outra->vinculo->local->nome,
        );
    }
}
