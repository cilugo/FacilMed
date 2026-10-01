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
     * bases simuladas, 3 medicos, 2 pacientes, consultas).
     *
     * Ordem: Convenio antes de Medico (medico aceita convenio) e de
     * Paciente (carteirinha aponta para plano).
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
            PacienteSeeder::class,

            // Entra ANTES das consultas: o ConsultaSeeder gera
            // agendamentos em datas futuras, e nao faz sentido cair
            // num feriado que o sistema ja conhece.
            FeriadoSeeder::class,

            ConsultaSeeder::class,

            // 01/10/2026: fotos das unidades e avaliações dos locais.
            FotosDemonstracaoSeeder::class,
            AvaliacaoLocalSeeder::class,
        ]);
    }
}
