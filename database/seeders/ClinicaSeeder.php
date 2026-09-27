<?php

namespace Database\Seeders;

use App\Models\Clinica;
use App\Models\HorarioFuncionamento;
use App\Models\Local;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * 3 CLÍNICAS + 3 HOSPITAIS FICTÍCIOS (24/09/2026). Dados em
 * DadosFicticios::ESTABELECIMENTOS — os CNPJs existem na base simulada.
 *
 * Hospital e clínica usam a MESMA estrutura (conta de "clínica"); o que
 * muda é o tipo da unidade (locais.tipo = clinica | hospital).
 * A "Clínica SpSaúde" é a rede própria do convênio fictício SpSaúde.
 */
class ClinicaSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DadosFicticios::ESTABELECIMENTOS as [$email, $razao, $fantasia, $cnpj, $telefone, $descricao, $u]) {
            [$nome, $tipo, $cep, $endereco, $numero, $bairro, $cidade, $uf] = $u;

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name'     => $fantasia,
                    'password' => DadosFicticios::SENHA,
                    'tipo'     => User::TIPO_CLINICA,
                    'telefone' => $telefone,
                    'status'   => 'ativo',
                ]
            );

            $clinica = Clinica::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'cnpj'          => $cnpj,
                    'razao_social'  => $razao,
                    'nome_fantasia' => $fantasia,
                    'descricao'     => $descricao,
                    'telefone'      => $telefone,
                ]
            );

            $local = Local::updateOrCreate(
                ['clinica_id' => $clinica->id, 'nome' => $nome],
                compact('tipo', 'cep', 'endereco', 'numero', 'bairro', 'cidade', 'uf', 'telefone') + ['ativo' => true]
            );

            // Seg-sex 08-18, sábado 08-12 (hospital poderia ter mais; fica
            // simples para a demonstração).
            foreach (['segunda', 'terca', 'quarta', 'quinta', 'sexta'] as $dia) {
                HorarioFuncionamento::updateOrCreate(['local_id' => $local->id, 'dia_semana' => $dia], ['abre' => '08:00', 'fecha' => '18:00']);
            }
            HorarioFuncionamento::updateOrCreate(['local_id' => $local->id, 'dia_semana' => 'sabado'], ['abre' => '08:00', 'fecha' => '12:00']);
        }

        $this->command->info('3 clínicas e 3 hospitais fictícios. Senha: ' . DadosFicticios::SENHA);
    }
}
