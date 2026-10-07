<?php

namespace Database\Seeders;

use App\Models\BaseCarteirinha;
use App\Models\Usuario;
use App\Models\UsuarioPlano;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * 2 USUARIOS FICTÍCIOS (24/09/2026). Dados em DadosFicticios::USUÁRIOS.
 *
 *  - Ana: já vem com a carteirinha SpSaúde Família cadastrada (copiada
 *    da base simulada, como o sistema faria).
 *  - Marcos: sem carteirinha — é com ele que se testa o cadastro de
 *    carteirinha pela tela (ver DadosFicticios::CARTEIRINHAS).
 */
class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DadosFicticios::USUARIOS as $d) {
            $user = User::updateOrCreate(
                ['email' => $d['email']],
                [
                    'name'     => $d['nome'],
                    'password' => DadosFicticios::SENHA,
                    'tipo'     => User::TIPO_USUARIO,
                    'telefone' => $d['telefone'],
                    'status'   => 'ativo',
                ]
            );

            $usuario = Usuario::updateOrCreate(
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

            UsuarioPlano::updateOrCreate(
                ['plano_id' => $base->plano_id, 'numero_carteirinha' => $base->numero_carteirinha],
                [
                    'usuario_id'  => $usuario->id,
                    'validade'     => $base->validade,
                    'titular_nome' => $base->beneficiario_nome,
                    'status'       => 'ativa',
                    'conferido_em' => now(),
                ]
            );
        }

        $this->command->info(count(DadosFicticios::USUARIOS) . ' usuários fictícios. Senha: ' . DadosFicticios::SENHA);
    }
}
