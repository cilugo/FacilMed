<?php

namespace Database\Seeders;

use App\Models\Avaliacao;
use App\Models\AvaliacaoLocal;
use App\Models\Local;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Avaliações de LOCAL para a demonstração (01/10/2026). Roda depois do
 * ConsultaSeeder.
 *
 *  1. cada avaliação de consulta vira a nota do local daquela consulta (a
 *     mesma regra da migration site_e_avaliacoes_dos_locais);
 *  2. a Ana e o Marcos avaliam mais alguns lugares, para todas as unidades
 *     terem nota na busca. Qualquer paciente logado pode avaliar um local.
 *
 * Comentários fictícios, como os das consultas: só a clínica e o admin leem.
 */
class AvaliacaoLocalSeeder extends Seeder
{
    /** [e-mail do paciente, nome da unidade, estrelas, comentário] */
    private const EXTRAS = [
        ['ana@facilmed.test', 'Santa Clara - Taubaté', 5, 'Recepção organizada e sala de espera confortável.'],
        ['ana@facilmed.test', 'Aurora - Vila Ema', 4, 'Fácil de chegar; estacionamento cheio no fim da tarde.'],
        ['marcos@facilmed.test', 'São Lucas - Jacareí', 4, 'Atendimento rápido na recepção.'],
        ['marcos@facilmed.test', 'Esperança - Caçapava', 5, 'Ambiente limpo e equipe atenciosa.'],
        ['marcos@facilmed.test', 'SpSaúde - Jardim Satélite', 3, 'Espera um pouco longa, mas bem atendido.'],
        ['ana@facilmed.test', 'Vida Plena - Centro', 4, 'Clínica pequena e tranquila.'],
    ];

    public function run(): void
    {
        Avaliacao::with('consulta.vinculo')->orderBy('created_at')->get()->each(function (Avaliacao $a) {
            AvaliacaoLocal::firstOrCreate(
                ['paciente_id' => $a->paciente_id, 'local_id' => $a->consulta->vinculo->local_id],
                ['estrelas' => $a->estrelas],
            );
        });

        foreach (self::EXTRAS as [$email, $unidade, $estrelas, $comentario]) {
            $paciente = User::where('email', $email)->first()?->paciente;
            $local = Local::where('nome', $unidade)->first();
            if ($paciente && $local) {
                AvaliacaoLocal::firstOrCreate(
                    ['paciente_id' => $paciente->id, 'local_id' => $local->id],
                    ['estrelas' => $estrelas, 'comentario' => $comentario],
                );
            }
        }
    }
}
