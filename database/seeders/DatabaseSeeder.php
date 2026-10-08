<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * A ORDEM IMPORTA - cada seeder depende do anterior.
     *
     * Desde 24/09 os convenios sao ficticios e o ConvenioSeeder NAO
     * depende mais do CSV da ANS: `php artisan db:seed` sozinho ja
     * monta tudo (admin, 3 clinicas + 3 hospitais, 3 convenios ativos,
     * bases simuladas, 8 medicos, 2 usuários e avaliacoes de exemplo).
     * 01/10/2026: sem consultas e sem feriados (sairam com o agendamento).
     *
     * Ordem: Convenio antes de Medico (medico aceita convenio) e de
     * Usuário (carteirinha aponta para plano).
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            EspecialidadeSeeder::class,
            ConvenioSeeder::class,
            BaseSimuladaSeeder::class,   // depois do Convenio: carteirinha aponta para plano
            ClinicaSeeder::class,
            MedicoSeeder::class,
            UsuarioSeeder::class,
            AvaliacaoSeeder::class,      // depois de Usuário e Medico
        ]);
    }
}
