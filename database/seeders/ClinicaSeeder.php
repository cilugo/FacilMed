<?php

namespace Database\Seeders;

use App\Models\Clinica;
use App\Models\HorarioFuncionamento;
use App\Models\Local;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClinicaSeeder extends Seeder
{
    /**
     * CLINICAS FICTICIAS - nenhuma destas existe.
     * Enderecos e CNPJs sao inventados para demonstracao.
     *
     * Cada uma tem sua propria tabela de precos, definida no
     * MedicoSeeder: a mesma especialidade custa diferente em
     * cada clinica, que e o comportamento que queremos mostrar.
     */
    public const CLINICAS = [
        [
            'email'         => 'contato@vidaplena.test',
            'razao_social'  => 'Vida Plena Servicos Medicos LTDA',
            'nome_fantasia' => 'Clinica Vida Plena',
            'cnpj'          => '11.222.333/0001-44',
            'telefone'      => '(12) 3921-1000',
            'descricao'     => 'Clinica de bairro com foco em atendimento de rotina e acompanhamento.',
            'unidades'      => [
                ['Vida Plena - Centro', 'clinica', '12210-100', 'Rua Quinze de Novembro', '480', 'Centro', 'Sao Jose dos Campos', 'SP'],
                ['Vida Plena - Aquarius', 'clinica', '12246-000', 'Av. Cassiano Ricardo', '1200', 'Jardim Aquarius', 'Sao Jose dos Campos', 'SP'],
            ],
        ],
        [
            'email'         => 'contato@aurora.test',
            'razao_social'  => 'Centro Medico Aurora S/A',
            'nome_fantasia' => 'Centro Medico Aurora',
            'cnpj'          => '22.333.444/0001-55',
            'telefone'      => '(12) 3945-2000',
            'descricao'     => 'Centro medico com corpo clinico multidisciplinar e agenda estendida.',
            'unidades'      => [
                ['Aurora - Vila Ema', 'clinica', '12243-700', 'Rua Republica do Iraque', '90', 'Vila Ema', 'Sao Jose dos Campos', 'SP'],
            ],
        ],
        [
            'email'         => 'contato@santaclara.test',
            'razao_social'  => 'Hospital Santa Clara de Taubate LTDA',
            'nome_fantasia' => 'Hospital Santa Clara',
            'cnpj'          => '33.444.555/0001-66',
            'telefone'      => '(12) 3632-3000',
            'descricao'     => 'Hospital geral com ambulatorio de especialidades.',
            'unidades'      => [
                ['Santa Clara - Taubate', 'hospital', '12020-270', 'Av. Tiradentes', '1500', 'Centro', 'Taubate', 'SP'],
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::CLINICAS as $dados) {
            $user = User::updateOrCreate(
                ['email' => $dados['email']],
                [
                    'name'     => $dados['nome_fantasia'],
                    'password' => Hash::make('facilmed2026'),
                    'tipo'     => User::TIPO_CLINICA,
                    'telefone' => $dados['telefone'],
                    'status'   => 'ativo',
                ]
            );

            $clinica = Clinica::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'cnpj'          => $dados['cnpj'],
                    'razao_social'  => $dados['razao_social'],
                    'nome_fantasia' => $dados['nome_fantasia'],
                    'descricao'     => $dados['descricao'],
                    'telefone'      => $dados['telefone'],
                ]
            );

            foreach ($dados['unidades'] as [$nome, $tipo, $cep, $end, $num, $bairro, $cidade, $uf]) {
                $local = Local::updateOrCreate(
                    ['clinica_id' => $clinica->id, 'nome' => $nome],
                    [
                        'tipo'     => $tipo,
                        'cep'      => $cep,
                        'endereco' => $end,
                        'numero'   => $num,
                        'bairro'   => $bairro,
                        'cidade'   => $cidade,
                        'uf'       => $uf,
                        'telefone' => $dados['telefone'],
                        'ativo'    => true,
                    ]
                );

                // Segunda a sexta 08:00-18:00, sabado 08:00-12:00.
                foreach (['segunda','terca','quarta','quinta','sexta'] as $dia) {
                    HorarioFuncionamento::updateOrCreate(
                        ['local_id' => $local->id, 'dia_semana' => $dia],
                        ['abre' => '08:00', 'fecha' => '18:00']
                    );
                }
                HorarioFuncionamento::updateOrCreate(
                    ['local_id' => $local->id, 'dia_semana' => 'sabado'],
                    ['abre' => '08:00', 'fecha' => '12:00']
                );
            }
        }

        $this->command->info('3 clinicas ficticias com 4 unidades. Senha de todas: facilmed2026');
    }
}
