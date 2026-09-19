<?php

namespace App\Policies;

use App\Models\Consulta;
use App\Models\User;

/**
 * Quem pode fazer o que com uma consulta.
 *
 * O middleware 'tipo:medico' responde "essa pessoa e um medico".
 * Esta Policy responde "essa consulta e DESTE medico". Sao perguntas
 * diferentes, e e a segunda que impede o medico A de abrir a agenda
 * do medico B.
 *
 * O Laravel 12 descobre esta classe sozinho pela convencao de nome
 * (App\Models\Consulta -> App\Policies\ConsultaPolicy). Nao precisa
 * registrar em lugar nenhum.
 *
 * NAO existe metodo before() concedendo tudo ao admin. O admin ve a
 * listagem de consultas na area dele, que e outra tela e outra
 * consulta ao banco; dar passe livre aqui abriria por tabela tambem
 * o dado de acessibilidade, que e o mais sensivel do sistema.
 */
class ConsultaPolicy
{
    /**
     * Ver os detalhes de uma consulta.
     *
     * Quatro pessoas legitimas: o paciente dela, o medico dela, a
     * clinica dona do local onde ela acontece, e o admin.
     */
    public function view(User $user, Consulta $consulta): bool
    {
        if ($user->ehAdmin()) {
            return true;
        }

        if ($user->ehPaciente()) {
            return $user->paciente?->id === $consulta->paciente_id;
        }

        if ($user->ehMedico()) {
            return $user->medico?->id === $consulta->medico_id;
        }

        if ($user->ehClinica()) {
            return $this->ehDaClinica($user, $consulta);
        }

        return false;
    }

    /**
     * Cancelar.
     *
     * Cancelar e SEMPRE permitido enquanto a consulta esta agendada e
     * no futuro - inclusive faltando menos de 24h. Nesse caso ela e
     * marcada com cancelamento_tardio, nao bloqueada: bloquear nao faz
     * a pessoa comparecer, faz ela faltar, e falta perde o horario
     * enquanto cancelamento devolve para outro paciente.
     *
     * O "faltando menos de 24h" e registro, nao permissao - por isso
     * nao aparece aqui.
     */
    public function cancelar(User $user, Consulta $consulta): bool
    {
        if (! $consulta->podeSerCancelada()) {
            return false;
        }

        if ($user->ehPaciente()) {
            return $user->paciente?->id === $consulta->paciente_id;
        }

        // O medico e a clinica tambem podem cancelar (imprevisto,
        // ausencia). O paciente e avisado por e-mail.
        if ($user->ehMedico()) {
            return $user->medico?->id === $consulta->medico_id;
        }

        if ($user->ehClinica()) {
            return $this->ehDaClinica($user, $consulta);
        }

        return false;
    }

    /**
     * Marcar como realizada ou como falta.
     *
     * So o medico da consulta. A clinica nao marca presenca por ele -
     * quem sabe se a pessoa apareceu e quem atendeu.
     */
    public function atender(User $user, Consulta $consulta): bool
    {
        return $user->ehMedico()
            && $user->medico?->id === $consulta->medico_id
            && $consulta->status === 'agendada';
    }

    /**
     * Avaliar.
     *
     * So o paciente da consulta, so se ela estiver 'realizada', e so
     * uma vez (o UNIQUE em avaliacoes.consulta_id e a garantia real;
     * isto aqui e para a tela nao oferecer o botao a toa).
     *
     * Consulta com status 'nao_compareceu' nao pode ser avaliada -
     * e justamente para isso que esse status existe.
     */
    public function avaliar(User $user, Consulta $consulta): bool
    {
        return $user->ehPaciente()
            && $user->paciente?->id === $consulta->paciente_id
            && $consulta->podeSerAvaliada();
    }

    /**
     * A consulta acontece num local desta clinica?
     *
     * O caminho e consulta -> vinculo -> local -> clinica_id. Se o
     * local for consultorio proprio de autonomo, clinica_id e nulo e
     * isto retorna false, que e o certo.
     */
    private function ehDaClinica(User $user, Consulta $consulta): bool
    {
        $clinicaId = $user->clinica?->id;

        if ($clinicaId === null) {
            return false;
        }

        return $consulta->vinculo?->local?->clinica_id === $clinicaId;
    }
}
