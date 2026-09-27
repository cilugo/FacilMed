<?php

namespace Database\Seeders;

use App\Models\BaseCarteirinha;
use App\Models\Paciente;
use App\Models\PacienteAcessibilidade;
use App\Models\PacientePlano;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * 2 PACIENTES FICTÍCIOS (24/09/2026). Dados em DadosFicticios::PACIENTES.
 *
 *  - Ana: já vem com a carteirinha SpSaúde Família cadastrada (copiada
 *    da base simulada, como o sistema faria) e com acessibilidade.
 *  - Marcos: sem carteirinha — é com ele que se testa o cadastro de
 *    carteirinha pela tela (ver DadosFicticios::CARTEIRINHAS).
 */
class PacienteSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DadosFicticios::PACIENTES as $d) {
            $user = User::updateOrCreate(
                ['email' => $d['email']],
                [
                    'name'     => $d['nome'],
                    'password' => DadosFicticios::SENHA,
                    'tipo'     => User::TIPO_PACIENTE,
                    'telefone' => $d['telefone'],
                    'status'   => 'ativo',
                ]
            );

            $paciente = Paciente::updateOrCreate(
                ['user_id' => $user->id],
                ['cpf' => $d['cpf'], 'data_nascimento' => $d['nascimento'], 'sexo' => $d['sexo']]
            );

            if (! $d['carteirinha']) {
                continue;
            }

            // Mesma regra da tela: os dados vêm da BASE, não de digitação.
            $base = BaseCarteirinha::where('numero_carteirinha', $d['carteirinha'])
                ->where('beneficiario_cpf', $d['cpf'])
                ->firstOrFail();

            PacientePlano::updateOrCreate(
                ['plano_id' => $base->plano_id, 'numero_carteirinha' => $base->numero_carteirinha],
                [
                    'paciente_id'  => $paciente->id,
                    'validade'     => $base->validade,
                    'titular_nome' => $base->beneficiario_nome,
                    'status'       => 'ativa',
                    'conferido_em' => now(),
                ]
            );
        }

        // Acessibilidade declarada (dado sensível, LGPD art. 11): só a Ana.
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

        $this->command->info(count(DadosFicticios::PACIENTES) . ' pacientes fictícios. Senha: ' . DadosFicticios::SENHA);
    }
}
