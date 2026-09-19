<?php

namespace Database\Seeders;

use App\Models\Especialidade;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EspecialidadeSeeder extends Seeder
{
    /**
     * `destaque` alimenta os cards da home. A view faz foreach em
     * Especialidade::emDestaque() - nunca com a lista escrita no Blade.
     */
    private const ESPECIALIDADES = [
        ['Clinica Geral',   'stethoscope', true],
        ['Cardiologia',     'heart',       true],
        ['Pediatria',       'baby',        true],
        ['Dermatologia',    'skin',        true],
        ['Ginecologia',     'female',      true],
        ['Ortopedia',       'bone',        true],
        ['Neurologia',      'brain',       false],
        ['Oftalmologia',    'eye',         false],
        ['Psiquiatria',     'mind',        false],
        ['Endocrinologia',  'gland',       false],
        ['Otorrinolaringologia', 'ear',    false],
        ['Urologia',        'kidney',      false],
    ];

    public function run(): void
    {
        foreach (self::ESPECIALIDADES as [$nome, $icone, $destaque]) {
            Especialidade::updateOrCreate(
                ['nome' => $nome],
                [
                    'slug'     => Str::slug($nome),
                    'icone'    => $icone,
                    'destaque' => $destaque,
                    'ativo'    => true,
                ]
            );
        }
    }
}
