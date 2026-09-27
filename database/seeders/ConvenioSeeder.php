<?php

namespace Database\Seeders;

use App\Models\Convenio;
use App\Models\Plano;
use Illuminate\Database\Seeder;

class ConvenioSeeder extends Seeder
{
    /**
     * CONVÊNIOS E PLANOS 100% FICTÍCIOS (decisão do grupo, 24/09/2026).
     *
     * Nenhum destes nomes, telefones ou e-mails existe de verdade. CNPJs
     * so com digitos (convencao do projeto) e com digito verificador valido.
     * Os e-mails usam o domínio .test, que é reservado e nunca entrega
     * mensagem para ninguém.
     *
     * Hierarquia: Convênio → Planos. Cada convênio tem um plano de cada
     * tipo (individual, familiar, empresarial), que é o jeito como o
     * paciente reconhece o próprio plano: "SpSaúde Família".
     *
     * "Vale Saúde" entra DESATIVADO de propósito: serve para mostrar na
     * banca que convênio desativado some do agendamento, mas continua na
     * tela do admin (nada é apagado).
     *
     * Não depende mais do CSV da ANS: antes, sem o CSV, este seeder
     * pulava e o sistema ficava sem nenhum convênio.
     */
    public const CONVENIOS = [
        [
            'nome'      => 'SpSaúde',
            'cnpj'      => '44400001000107',
            'telefone'  => '(12) 4002-1000',
            'email'     => 'atendimento@spsaude.test',
            'descricao' => 'Convênio regional com rede própria (Clínica SpSaúde) e credenciada no Vale do Paraíba.',
            'ativo'     => true,
            'planos'    => [
                ['SpSaúde Individual',  'individual',  'estadual', 'Consultas e exames ambulatoriais para uma pessoa.'],
                ['SpSaúde Família',     'familiar',    'estadual', 'Titular e dependentes no mesmo contrato, rede estadual.'],
                ['SpSaúde Empresarial', 'empresarial', 'nacional', 'Contratado pela empresa para os funcionários, rede nacional.'],
            ],
        ],
        [
            'nome'      => 'Horizonte Med',
            'cnpj'      => '44400002000143',
            'telefone'  => '(12) 4002-2000',
            'email'     => 'relacionamento@horizontemed.test',
            'descricao' => 'Convênio com foco em planos de entrada e atendimento ambulatorial.',
            'ativo'     => true,
            'planos'    => [
                ['Horizonte Essencial',   'individual',  'municipal', 'Plano de entrada, rede no município de contratação.'],
                ['Horizonte Família',     'familiar',    'estadual',  'Titular e dependentes, rede estadual.'],
                ['Horizonte Empresarial', 'empresarial', 'estadual',  'Plano coletivo empresarial, rede estadual.'],
            ],
        ],
        [
            'nome'      => 'Bem Viver Saúde',
            'cnpj'      => '44400003000198',
            'telefone'  => '(12) 4002-3000',
            'email'     => 'contato@bemviversaude.test',
            'descricao' => 'Convênio com rede ampliada e cobertura nacional nos planos superiores.',
            'ativo'     => true,
            'planos'    => [
                ['Bem Viver Individual',   'individual',  'municipal', 'Rede credenciada no município.'],
                ['Bem Viver Família Plus', 'familiar',    'nacional',  'Titular e dependentes com rede nacional.'],
                ['Bem Viver Empresarial',  'empresarial', 'nacional',  'Plano coletivo empresarial com rede nacional.'],
            ],
        ],
        [
            'nome'      => 'Vale Saúde',
            'cnpj'      => null,
            'telefone'  => '(12) 4002-4000',
            'email'     => 'contato@valesaude.test',
            'descricao' => 'Convênio desativado: parou de atender pela plataforma (exemplo de demonstração).',
            'ativo'     => false,
            'planos'    => [
                ['Vale Saúde Básico', 'individual', 'municipal', 'Plano descontinuado.'],
            ],
        ],
    ];

    public function run(): void
    {
        $totalPlanos = 0;

        foreach (self::CONVENIOS as $dados) {
            $convenio = Convenio::updateOrCreate(
                ['nome' => $dados['nome']],
                [
                    'operadora_ans_id' => null,
                    'cnpj'             => $dados['cnpj'],
                    'telefone'         => $dados['telefone'],
                    'email'            => $dados['email'],
                    'descricao'        => $dados['descricao'],
                    'ativo'            => $dados['ativo'],
                ]
            );

            foreach ($dados['planos'] as [$nome, $tipo, $abrangencia, $descricao]) {
                Plano::updateOrCreate(
                    ['convenio_id' => $convenio->id, 'nome' => $nome],
                    [
                        'tipo'        => $tipo,
                        'abrangencia' => $abrangencia,
                        'descricao'   => $descricao,
                        'ativo'       => $dados['ativo'],
                    ]
                );
                $totalPlanos++;
            }
        }

        $this->command->info(count(self::CONVENIOS) . " convênios fictícios (1 desativado) com {$totalPlanos} planos.");
    }
}
