<?php

namespace Database\Seeders;

use App\Models\Paciente;
use App\Models\PacienteAcessibilidade;
use App\Models\PacientePlano;
use App\Models\Plano;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PacienteSeeder extends Seeder
{
    private const PACIENTES = [
        ['Ana Beatriz Lima',  'ana@facilmed.test',   '1992-04-17', 'Feminino',  true],
        ['Marcos Vinicius Alves', 'marcos@facilmed.test', '1985-11-02', 'Masculino', false],
        ['Juliana Prado',     'juliana@facilmed.test', '1978-07-25', 'Feminino',  false],
        ['Renato Okamoto',    'renato@facilmed.test',  '2001-01-09', 'Masculino', false],
    ];

    public function run(): void
    {
        $plano = Plano::first();

        foreach (self::PACIENTES as $i => [$nome, $email, $nasc, $sexo, $comPlano]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name'     => $nome,
                    'password' => Hash::make('facilmed2026'),
                    'tipo'     => User::TIPO_PACIENTE,
                    'telefone' => '(12) 98' . rand(100, 999) . '-' . rand(1000, 9999),
                    'status'   => 'ativo',
                ]
            );

            $paciente = Paciente::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'cpf'             => sprintf('%03d.%03d.%03d-%02d', rand(100,999), rand(100,999), rand(100,999), rand(10,99)),
                    'data_nascimento' => $nasc,
                    'sexo'            => $sexo,
                ]
            );

            if ($comPlano && $plano) {
                PacientePlano::updateOrCreate(
                    ['plano_id' => $plano->id, 'numero_carteirinha' => '0000' . str_pad((string) ($i + 1), 8, '0', STR_PAD_LEFT)],
                    [
                        'paciente_id'  => $paciente->id,
                        'validade'     => now()->addYear()->toDateString(),
                        'titular_nome' => $nome,
                        // 'ativa' aqui so para a demonstracao. No fluxo real
                        // entra como 'pendente' ate alguem conferir o codigo
                        // do comprovante COMPROVA no site da ANS.
                        'status'       => 'ativa',
                        'conferido_em' => now(),
                    ]
                );
            }
        }

        /**
         * UM paciente com acessibilidade declarada, para demonstrar o
         * fluxo. DADO SENSIVEL (LGPD art. 11): so aparece para o
         * profissional que tem consulta com ele, via Policy.
         */
        $ana = Paciente::whereHas('user', fn ($q) => $q->where('email', 'ana@facilmed.test'))->first();
        if ($ana) {
            PacienteAcessibilidade::updateOrCreate(
                ['paciente_id' => $ana->id],
                [
                    'possui_deficiencia'   => true,
                    'descricao'            => 'Uso cadeira de rodas. Preciso de sala com acesso sem degraus.',
                    'consentimento_em'     => now(),
                    'consentimento_versao' => '1.0',
                ]
            );
        }

        $this->command->info('4 pacientes ficticios. Senha: facilmed2026');
    }
}
