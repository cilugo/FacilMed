<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@facilmed.test'],
            [
                'name'     => 'Administrador FacilMed',
                'password' => Hash::make('facilmed2026'),
                'tipo'     => User::TIPO_ADMIN,
                'telefone' => '(12) 3200-0000',
                'status'   => 'ativo',
            ]
        );

        $this->command->info('Admin: admin@facilmed.test / facilmed2026');
    }
}
