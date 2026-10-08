<?php

namespace Database\Seeders;

use App\Models\Convenio;
use App\Models\Especialidade;
use App\Models\Local;
use App\Models\Medico;
use App\Models\Vinculo;
use Illuminate\Database\Seeder;

/**
 * MÉDICOS FICTÍCIOS. Dados em DadosFicticios::MEDICOS (8 desde 01/10/2026).
 *
 * Os CRMs existem e estão ATIVOS na base simulada (base_crms), então
 * todos entram 'verificado' — igual aconteceria se a clínica os
 * cadastrasse pela tela. verificado_por = NULL: quem conferiu foi a base.
 *
 * 01/10/2026: médico é PERFIL, sem conta (sem e-mail, sem senha). Cada um
 * atende em DOIS lugares. 05/10: sem preço por médico — a faixa ($ a $$$$)
 * é da unidade (ClinicaSeeder). Quem aceita convênio é o MÉDICO
 * (convenio_medico), não o endereço.
 */
class MedicoSeeder extends Seeder
{
    public function run(): void
    {
        $convenios = Convenio::pluck('id', 'nome');

        foreach (DadosFicticios::MEDICOS as $i => $d) {
            $medico = Medico::updateOrCreate(
                ['crm' => $d['crm'], 'uf' => $d['uf']],
                [
                    'nome'                  => $d['nome'],
                    'cpf'                   => $d['cpf'],
                    'status_verificacao'    => 'verificado',
                    'verificado_em'         => now(),
                    'bio'                   => $d['bio'],
                    'telefone_profissional' => sprintf('12390%05d', 10000 + $i),
                    'anos_atuacao'          => $d['anos'],
                    'foto'                  => $d['foto'] ?? null,
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

            foreach ($d['unidades'] as [$nomeLocal]) {
                $local = Local::where('nome', $nomeLocal)->first();
                if (! $local) {
                    throw new \RuntimeException("MedicoSeeder: unidade \"{$nomeLocal}\" não existe. Rode o ClinicaSeeder antes.");
                }

                Vinculo::updateOrCreate(
                    ['medico_id' => $medico->id, 'local_id' => $local->id],
                    ['aceita_particular' => true, 'aceita_convenio' => count($d['convenios']) > 0, 'ativo' => true]
                );
            }
        }

        $this->command->info(count(DadosFicticios::MEDICOS) . ' médicos fictícios (CRMs na base simulada, sem conta de acesso).');
    }
}
