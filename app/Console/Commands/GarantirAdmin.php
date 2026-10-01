<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Rules\SenhaPadrao;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Conta de administrador com e-mail DE VERDADE (01/10/2026, plano do grupo:
 * "criar e-mails reais para cada função e testar o Relembrar senha").
 *
 * Paciente e clínica com e-mail real o grupo cria pelo próprio site
 * (Cadastrar). Administrador não tem cadastro pelo site - então o servidor
 * cria esta conta ao ligar, a partir de duas variáveis do Render:
 *   ADMIN_EMAIL     o e-mail (de integrante do grupo)
 *   ADMIN_PASSWORD  a senha inicial (mínimo 8 caracteres)
 * O docker/entrypoint.sh chama este comando a cada boot.
 *
 * Seguro de rodar várias vezes: se a conta já existe, NÃO mexe (nem na senha -
 * quem trocou a senha pelo "Esqueci minha senha" não perde a troca).
 * Nunca derruba o servidor: qualquer problema vira só um aviso no log.
 *
 * Os e-mails reais ficam só nas variáveis do Render, nunca no Git nem no
 * seeder (AGENTS.md §3: "nunca usar e-mail real de pessoa real nos seeders").
 */
class GarantirAdmin extends Command
{
    protected $signature = 'facilmed:garantir-admin';

    protected $description = 'Cria a conta de administrador de ADMIN_EMAIL/ADMIN_PASSWORD, se ainda não existir.';

    public function handle(): int
    {
        // getenv() e não env(): com o config:cache do servidor, env() fora de
        // config/ devolve null. As duas variáveis vêm direto do Render.
        $email = trim((string) getenv('ADMIN_EMAIL'));
        $senha = (string) getenv('ADMIN_PASSWORD');

        if ($email === '') {
            $this->line('FacilMed: ADMIN_EMAIL vazio, nenhuma conta de administrador extra.');

            return self::SUCCESS;
        }

        $existente = User::where('email', $email)->first();
        if ($existente) {
            $this->line($existente->ehAdmin()
                ? "FacilMed: administrador {$email} já existe (nada mudou)."
                : "FacilMed: AVISO - {$email} já é uma conta de outro tipo ({$existente->tipo}); nenhum administrador criado.");

            return self::SUCCESS;
        }

        $validacao = Validator::make(['email' => $email, 'password' => $senha], [
            'email'    => ['required', 'email'],
            'password' => ['required', SenhaPadrao::regra()],
        ]);
        if ($validacao->fails()) {
            $this->warn('FacilMed: AVISO - administrador não criado: ' . implode(' ', $validacao->errors()->all()));

            return self::SUCCESS;
        }

        $user = User::create([
            'name'     => 'Administração FacilMed',
            'email'    => $email,
            'password' => $senha,                 // o cast 'hashed' do User guarda só o hash
            'tipo'     => User::TIPO_ADMIN,
            'status'   => 'ativo',
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->info("FacilMed: administrador {$email} criado.");

        return self::SUCCESS;
    }
}
