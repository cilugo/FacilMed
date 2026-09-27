<?php

namespace Database\Seeders;

use App\Models\BaseCarteirinha;
use App\Models\BaseCnpj;
use App\Models\BaseCrm;
use App\Models\Plano;
use Illuminate\Database\Seeder;

/**
 * Preenche as BASES SIMULADAS (CFM, Receita e operadoras de mentira).
 * Os dados vêm de DadosFicticios. Roda depois do ConvenioSeeder,
 * porque carteirinha aponta para plano.
 *
 * É o ÚNICO lugar que escreve nessas tabelas: nenhuma tela escreve
 * nelas, senão qualquer um "validaria" o próprio dado.
 */
class BaseSimuladaSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DadosFicticios::CRMS as [$crm, $uf, $nome, $situacao]) {
            BaseCrm::updateOrCreate(['crm' => $crm, 'uf' => $uf], ['nome' => $nome, 'situacao' => $situacao]);
        }

        // CNPJ de todos os estabelecimentos do seed + os extras de teste.
        foreach (DadosFicticios::ESTABELECIMENTOS as [, $razao, $fantasia, $cnpj]) {
            BaseCnpj::updateOrCreate(['cnpj' => $cnpj], ['razao_social' => $razao, 'nome_fantasia' => $fantasia, 'situacao' => 'ativa']);
        }
        foreach (DadosFicticios::CNPJS_EXTRAS as [$cnpj, $razao, $fantasia, $situacao]) {
            BaseCnpj::updateOrCreate(['cnpj' => $cnpj], ['razao_social' => $razao, 'nome_fantasia' => $fantasia, 'situacao' => $situacao]);
        }

        $planos = Plano::pluck('id', 'nome');

        foreach (DadosFicticios::CARTEIRINHAS as [$plano, $numero, $nome, $cpf, $meses, $situacao]) {
            if (! isset($planos[$plano])) {
                throw new \RuntimeException("BaseSimuladaSeeder: plano \"{$plano}\" não existe. Rode o ConvenioSeeder antes.");
            }

            BaseCarteirinha::updateOrCreate(
                ['plano_id' => $planos[$plano], 'numero_carteirinha' => $numero],
                [
                    'beneficiario_nome' => $nome,
                    'beneficiario_cpf'  => $cpf,
                    'validade'          => now()->addMonths($meses)->toDateString(),
                    'situacao'          => $situacao,
                ]
            );
        }

        $this->command->info(sprintf(
            'Bases simuladas: %d CRMs, %d CNPJs, %d carteirinhas (inclui registros de recusa para teste).',
            count(DadosFicticios::CRMS),
            count(DadosFicticios::ESTABELECIMENTOS) + count(DadosFicticios::CNPJS_EXTRAS),
            count(DadosFicticios::CARTEIRINHAS),
        ));
    }
}
