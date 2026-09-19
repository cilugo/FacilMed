<?php

namespace Database\Seeders;

use App\Models\Convenio;
use App\Models\OperadoraAns;
use App\Models\Plano;
use Illuminate\Database\Seeder;

class ConvenioSeeder extends Seeder
{
    /**
     * AS OPERADORAS SAO REAIS, OS PLANOS SAO FICTICIOS.
     *
     * Este seeder NAO inventa operadora nenhuma: ele pega as que ja
     * foram importadas dos dados abertos da ANS e cria planos de
     * demonstracao em cima delas. Registro ANS inventado seria
     * exatamente o tipo de dado falso que o AGENTS.md secao 6 proibe.
     *
     * Rode antes:  php artisan facilmed:importar-operadoras
     */
    private const PLANOS_FICTICIOS = [
        ['Essencial',  'Cobertura ambulatorial na regiao metropolitana'],
        ['Pleno',      'Cobertura ambulatorial e hospitalar no estado'],
        ['Nacional',   'Cobertura ampliada em todo o territorio nacional'],
    ];

    public function run(): void
    {
        $operadoras = OperadoraAns::query()->orderBy('id')->limit(6)->get();

        if ($operadoras->isEmpty()) {
            $this->command->warn(
                'Nenhuma operadora encontrada. Rode "php artisan facilmed:importar-operadoras" '
                . 'e depois "php artisan db:seed --class=ConvenioSeeder". '
                . 'Sem isso, agendamento por convenio fica sem dado - o resto do sistema funciona.'
            );
            return;
        }

        foreach ($operadoras as $operadora) {
            $convenio = Convenio::updateOrCreate(
                ['operadora_ans_id' => $operadora->id],
                [
                    'nome'      => $operadora->nome_fantasia ?: $operadora->razao_social,
                    'descricao' => "Operadora registrada na ANS sob o numero {$operadora->registro_ans}.",
                    'ativo'     => true,
                ]
            );

            foreach (self::PLANOS_FICTICIOS as [$nome, $descricao]) {
                Plano::updateOrCreate(
                    ['convenio_id' => $convenio->id, 'nome' => "{$convenio->nome} {$nome}"],
                    ['descricao' => $descricao . ' (plano ficticio, para demonstracao)', 'ativo' => true]
                );
            }
        }

        $this->command->info("Criados {$operadoras->count()} convenios com 3 planos ficticios cada.");
    }
}
