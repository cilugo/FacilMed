<?php

namespace App\Policies;

use App\Models\PacienteAcessibilidade;
use App\Models\User;

/**
 * A POLICY MAIS RESTRITIVA DO SISTEMA. Leia antes de afrouxar.
 *
 * O que ela protege: o texto em que o paciente descreve a deficiencia
 * ou a necessidade de adaptacao dele. E dado pessoal sensivel de saude
 * na categoria do art. 11 da LGPD - a mais forte que existe na lei.
 *
 * A finalidade declarada e UMA so: permitir que o profissional prepare
 * o atendimento (rampa, interprete, tempo maior, sala no terreo).
 * Fora dessa finalidade, ninguem le.
 *
 * TRES REGRAS QUE NAO MUDAM
 * -------------------------
 * 1. Nunca em listagem, busca, exportacao ou log. Nem "so o campo
 *    possui_deficiencia" numa tabela de pacientes - saber QUEM tem
 *    ja e o dado.
 * 2. O admin NAO le. Ele administra contas, nao atendimento, e nao
 *    tem finalidade legitima para esse texto. Por isso nao existe
 *    before() nem ehAdmin() concedendo nada aqui - a ausencia e
 *    proposital, nao esquecimento.
 * 3. O medico so le quando TEM consulta agendada com essa pessoa.
 *    Nao basta ser medico. Nao basta ja ter atendido um dia.
 *
 * A janela fecha sozinha: quando a consulta sai de 'agendada', o
 * acesso acaba junto.
 */
class PacienteAcessibilidadePolicy
{
    public function view(User $user, PacienteAcessibilidade $acessibilidade): bool
    {
        // O proprio paciente, sempre.
        if ($user->ehPaciente()) {
            return $user->paciente?->id === $acessibilidade->paciente_id;
        }

        // O medico, so com consulta agendada com essa pessoa.
        if ($user->ehMedico()) {
            return $this->temConsultaAgendadaCom($user, $acessibilidade->paciente_id);
        }

        // Clinica e admin: nao. Ver regra 2 no topo.
        return false;
    }

    /** So o titular edita o que escreveu sobre si. */
    public function update(User $user, PacienteAcessibilidade $acessibilidade): bool
    {
        return $user->ehPaciente()
            && $user->paciente?->id === $acessibilidade->paciente_id;
    }

    /**
     * Apagar.
     *
     * Direito de exclusao do titular (LGPD art. 18). E apagar de
     * verdade: a linha sai da tabela e o cadastro da pessoa continua
     * intacto. E exatamente por isso que esse dado mora numa tabela
     * separada de `pacientes`.
     */
    public function delete(User $user, PacienteAcessibilidade $acessibilidade): bool
    {
        return $this->update($user, $acessibilidade);
    }

    private function temConsultaAgendadaCom(User $user, int $pacienteId): bool
    {
        $medicoId = $user->medico?->id;

        if ($medicoId === null) {
            return false;
        }

        return \App\Models\Consulta::query()
            ->where('medico_id', $medicoId)
            ->where('paciente_id', $pacienteId)
            ->where('status', 'agendada')
            ->exists();
    }
}
