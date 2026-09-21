<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * A ORDEM IMPORTA - cada seeder depende do anterior.
     *
     * ConvenioSeeder precisa de `operadoras_ans` populada. Rode antes:
     *   php artisan facilmed:importar-operadoras
     * Se a tabela estiver vazia, o ConvenioSeeder avisa e pula, e os
     * agendamentos por convenio ficam sem dado - o resto funciona.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            EspecialidadeSeeder::class,
            ConvenioSeeder::class,
            ClinicaSeeder::class,
            MedicoSeeder::class,
            PacienteSeeder::class,

            // Entra ANTES das consultas: o ConsultaSeeder gera
            // agendamentos em datas futuras, e nao faz sentido cair
            // num feriado que o sistema ja conhece.
            FeriadoSeeder::class,

            ConsultaSeeder::class,
        ]);
    }
}
