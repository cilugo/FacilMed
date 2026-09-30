<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Paciente extends Model
{
    protected $table = 'pacientes';

    protected $fillable = ['user_id', 'cpf', 'data_nascimento', 'sexo'];

    protected $casts = ['data_nascimento' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Dado sensivel de saude. Nunca carregue com `with()` em
     * listagem - so no contexto de uma consulta especifica,
     * e passando pela Policy.
     */
    public function acessibilidade(): HasOne
    {
        return $this->hasOne(PacienteAcessibilidade::class);
    }

    public function planos(): HasMany
    {
        return $this->hasMany(PacientePlano::class);
    }

    public function planosAtivos(): HasMany
    {
        return $this->planos()->where('status', 'ativa');
    }

    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class);
    }

    /**
     * 29/09/2026: o paciente não pode estar em dois lugares ao mesmo tempo.
     *
     * Devolve a consulta AGENDADA deste paciente que ocupa algum pedaço de
     * [$inicio, $inicio + $duracaoMinutos) — ou null se o horário está livre
     * para ele. O índice único do banco só olha o MÉDICO; esta regra olha o
     * PACIENTE (a Ana podia marcar 9h com a Dra. Helena e 9h com o Dr. Rafael).
     *
     * Mesma conta de sobreposição da CalculadoraDeHorarios: encostar não é
     * sobrepor (uma consulta que termina 9h30 não atrapalha outra às 9h30).
     *
     * $ignorarConsultaId: na remarcação, a consulta antiga é cancelada na mesma
     * operação — então ela não conta como conflito.
     */
    public function consultaNoHorario(CarbonInterface $inicio, int $duracaoMinutos, ?int $ignorarConsultaId = null): ?Consulta
    {
        $fim = $inicio->copy()->addMinutes($duracaoMinutos);

        return $this->consultas()
            ->agendadas()
            ->whereDate('data_consulta', $inicio->toDateString())
            ->when($ignorarConsultaId, fn ($q) => $q->whereKeyNot($ignorarConsultaId))
            ->with('medico.user', 'vinculo.local')
            ->get()
            ->first(function (Consulta $c) use ($inicio, $fim) {
                $cFim = $c->inicio->copy()->addMinutes((int) ($c->duracao_minutos ?: 30));

                return $c->inicio->lessThan($fim) && $cFim->greaterThan($inicio);
            });
    }

    public function getIdadeAttribute(): ?int
    {
        return $this->data_nascimento?->age;
    }

    public function avaliacoes(): HasMany
    {
        return $this->hasMany(Avaliacao::class);
    }

    /**
     * 30/09/2026 — o paciente exclui a própria conta (LGPD, plano do app).
     *
     * POR QUE ANONIMIZAR E NÃO APAGAR: consultas.paciente_id é restrictOnDelete —
     * o banco não deixa apagar um paciente que tenha consulta. E nem deveria: a
     * agenda do médico, a nota dele e os números da clínica dependem dessas
     * consultas. A LGPD (art. 16) aceita guardar o dado ANONIMIZADO: a consulta
     * fica, mas ninguém consegue mais dizer de quem ela era.
     *
     * O que acontece, tudo numa transação (ou vai tudo, ou nada):
     *  1. consultas futuras → canceladas por Consulta::cancelar(), o único jeito
     *     de cancelar (o médico recebe o aviso de sempre, depois do commit);
     *     o texto livre que o paciente escreveu nas consultas (observações e
     *     motivo de cancelamento) é apagado;
     *  2. acessibilidade (dado de saúde) → apagada de vez; carteirinhas → saem,
     *     menos as já usadas em consulta, que ficam sem número nem titular;
     *  3. avaliações → a NOTA fica (a média do médico não muda), o COMENTÁRIO sai
     *     (é texto livre escrito pela pessoa);
     *  4. e-mail antigo → sai do registro de avisos enviados e dos pedidos de
     *     troca de senha; as sessões abertas (guardam IP e navegador) são apagadas;
     *  5. nome, e-mail, telefone, CPF, nascimento e sexo → apagados ou trocados
     *     por um marcador; senha → uma aleatória que ninguém sabe; status 'inativo'
     *     (o GarantirContaAtiva já barra conta que não está ativa, e um CHECK no
     *     banco impede que conta excluída volte a 'ativo').
     *
     * O e-mail vira excluida-{id}@facilmed.invalid: a coluna é obrigatória e única,
     * e o domínio .invalid é reservado — nunca entrega e-mail para ninguém.
     *
     * Devolve quantas consultas foram canceladas, para a mensagem da tela.
     */
    public function excluirConta(): int
    {
        return DB::transaction(function () {
            // Trava a linha do paciente. O agendamento trava a mesma linha e, depois
            // da trava, confere de novo se a conta está ativa: se ele agendar em
            // outra aba no mesmo segundo, ou o agendamento grava antes (e a consulta
            // é cancelada aqui), ou espera e é recusado.
            $paciente = self::whereKey($this->id)->lockForUpdate()->firstOrFail();
            $user = $paciente->user;
            $emailAntigo = $user->email;

            $futuras = $user->consultasFuturasAfetadas()->get();

            // Texto livre que o PACIENTE escreveu nas consultas (pode ter nome,
            // sintoma...): as observações do agendamento e o motivo quando ele
            // mesmo cancelou. ANTES de cancelar as futuras, para o motivo novo
            // ("excluiu a conta") não ser apagado junto.
            $paciente->consultas()->update(['observacoes' => null]);
            $paciente->consultas()->where('cancelada_por', $user->id)->update(['motivo_cancelamento' => null]);

            $futuras->each->cancelar($user->id, 'O paciente excluiu a conta no FacilMed');

            $paciente->acessibilidade()->delete();

            // Carteirinhas: a que já foi usada em consulta FICA, sem o número e o
            // nome do titular — é ela que diz por qual convênio a consulta foi, e
            // os números da clínica ("por convênio") dependem disso. É a mesma
            // regra do PlanoController::remover. As que nunca foram usadas saem.
            // CONCAT('excluida-', id): o número é obrigatório e único por plano;
            // o id da própria linha garante que dois marcadores nunca se repetem.
            $usadas = Consulta::whereIn('paciente_plano_id', $paciente->planos()->select('id'))
                ->distinct()->pluck('paciente_plano_id');
            $paciente->planos()->whereNotIn('id', $usadas)->delete();
            $paciente->planos()->update([
                'numero_carteirinha'  => DB::raw("CONCAT('excluida-', id)"),
                'titular_nome'        => null,
                'validade'            => null,
                'codigo_comprova_ans' => null,
                'comprova_emitido_em' => null,
            ]);

            // update() direto no banco, sem passar pelo model: só o comentário
            // muda, então não precisa recalcular a média do médico.
            $paciente->avaliacoes()->update(['comentario' => null]);

            $emailNovo = "excluida-{$user->id}@facilmed.invalid";

            NotificacaoEnviada::whereIn('consulta_id', $paciente->consultas()->select('id'))
                ->where('destinatario', $emailAntigo)
                ->update(['destinatario' => $emailNovo]);
            DB::table('password_reset_tokens')->where('email', $emailAntigo)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();

            $paciente->update(['cpf' => null, 'data_nascimento' => null, 'sexo' => null]);

            // forceFill: excluida_em e remember_token ficam fora do $fillable
            // de propósito (nenhum formulário deve preenchê-los).
            $user->forceFill([
                'name'              => 'Conta excluída',
                'email'             => $emailNovo,
                'email_verified_at' => null,
                'telefone'          => null,
                'password'          => Str::random(64),   // o cast 'hashed' do User guarda o hash
                'remember_token'    => null,
                'status'            => 'inativo',
                'excluida_em'       => now(),
            ])->save();

            return $futuras->count();
        });
    }
}
