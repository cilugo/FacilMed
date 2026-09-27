<?php

namespace Database\Seeders;

use App\Models\Convenio;
use App\Models\Disponibilidade;
use App\Models\Especialidade;
use App\Models\Local;
use App\Models\Medico;
use App\Models\Preco;
use App\Models\User;
use App\Models\Vinculo;
use Illuminate\Database\Seeder;

/**
 * 3 MÉDICOS FICTÍCIOS (24/09/2026). Dados em DadosFicticios::MEDICOS.
 *
 * Os CRMs existem e estão ATIVOS na base simulada (base_crms), então
 * os três entram 'verificado' — igual aconteceria se se cadastrassem
 * pela tela. verificado_por = NULL: quem conferiu foi a base.
 *
 * Cada médico atende em DOIS lugares (manhã num, tarde no outro), com
 * preço diferente por unidade e por especialidade — o caso que
 * justifica a tabela `precos`. Quem aceita convênio é o MÉDICO
 * (convenio_medico), não o endereço.
 */
class MedicoSeeder extends Seeder
{
    public function run(): void
    {
        $convenios = Convenio::pluck('id', 'nome');

        foreach (DadosFicticios::MEDICOS as $i => $d) {
            $user = User::updateOrCreate(
                ['email' => $d['email']],
                [
                    'name'     => $d['nome'],
                    'password' => DadosFicticios::SENHA,
                    'tipo'     => User::TIPO_MEDICO,
                    'telefone' => sprintf('129910%05d', 10000 + $i),
                    'status'   => 'ativo',
                ]
            );

            $medico = Medico::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'cpf'                   => $d['cpf'],
                    'crm'                   => $d['crm'],
                    'uf'                    => $d['uf'],
                    'status_verificacao'    => 'verificado',
                    'verificado_em'         => now(),
                    'bio'                   => $d['bio'],
                    'telefone_profissional' => sprintf('12390%05d', 10000 + $i),
                    'anos_atuacao'          => $d['anos'],
                ]
            );

            $ids = Especialidade::whereIn('nome', $d['especialidades'])->pluck('id', 'nome');
            $faltando = array_diff($d['especialidades'], $ids->keys()->all());
            if ($faltando) {
                throw new \RuntimeException('MedicoSeeder: especialidade não encontrada: ' . implode(', ', $faltando));
            }

            $medico->especialidades()->sync(
                $ids->mapWithKeys(fn ($id, $nome) => [$id => ['principal' => $nome === $d['especialidades'][0]]])->all()
            );

            $medico->convenios()->sync(
                collect($d['convenios'])->map(fn ($nome) => $convenios[$nome] ?? null)->filter()->values()->all()
            );

            foreach ($d['unidades'] as [$nomeLocal, $turno, $tabelaPrecos]) {
                $local = Local::where('nome', $nomeLocal)->first();
                if (! $local) {
                    throw new \RuntimeException("MedicoSeeder: unidade \"{$nomeLocal}\" não existe. Rode o ClinicaSeeder antes.");
                }

                $vinculo = Vinculo::updateOrCreate(
                    ['medico_id' => $medico->id, 'local_id' => $local->id],
                    ['aceita_particular' => true, 'aceita_convenio' => count($d['convenios']) > 0, 'ativo' => true]
                );

                foreach ($tabelaPrecos as $esp => $valor) {
                    Preco::updateOrCreate(
                        ['vinculo_id' => $vinculo->id, 'especialidade_id' => $ids[$esp]],
                        ['valor' => $valor, 'ativo' => true]
                    );
                }

                [$inicio, $fim] = DadosFicticios::TURNOS[$turno];
                foreach (['segunda', 'terca', 'quarta', 'quinta', 'sexta'] as $dia) {
                    Disponibilidade::updateOrCreate(
                        ['vinculo_id' => $vinculo->id, 'dia_semana' => $dia, 'hora_inicio' => $inicio],
                        ['hora_fim' => $fim, 'duracao_consulta_minutos' => 30, 'ativo' => true]
                    );
                }
            }
        }

        $this->command->info(count(DadosFicticios::MEDICOS) . ' médicos fictícios (CRMs na base simulada). Senha: ' . DadosFicticios::SENHA);
    }
}
