<?php

namespace Database\Seeders;

use App\Models\Avaliacao;
use App\Models\Local;
use App\Models\Medico;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

/**
 * AVALIAÇÕES DE EXEMPLO (01/10/2026).
 *
 * Para a demonstração não começar com tudo "sem nota": os dois usuários
 * fictícios avaliam alguns locais e médicos. Passa pelo model Avaliacao
 * (updateOrCreate), então as médias dos locais e médicos são recalculadas
 * sozinhas. Uma por usuário em cada local/médico, como na tela.
 *
 * [e-mail do usuário, 'local'|'medico', nome do local ou CRM, estrelas, comentário]
 */
class AvaliacaoSeeder extends Seeder
{
    private const AVALIACOES = [
        ['ana@facilmed.test',    'local',  'Vida Plena - Centro',       5, 'Recepção atenciosa e sala de espera acessível.'],
        ['ana@facilmed.test',    'local',  'SpSaúde - Jardim Satélite', 4, 'Bom atendimento, mas a espera passou de meia hora.'],
        ['ana@facilmed.test',    'medico', '112233',                    5, 'Explicou tudo com calma.'],
        ['ana@facilmed.test',    'medico', '556688',                    5, null],
        ['ana@facilmed.test',    'medico', '223344',                    4, null],
        ['marcos@facilmed.test', 'local',  'Vida Plena - Centro',       4, null],
        ['marcos@facilmed.test', 'local',  'Santa Clara - Taubaté',     4, 'Estrutura boa, estacionamento cheio.'],
        ['marcos@facilmed.test', 'medico', '112233',                    4, null],
        ['marcos@facilmed.test', 'medico', '667788',                    5, 'Resolveu minha dor no joelho.'],
        ['marcos@facilmed.test', 'medico', '990011',                    4, null],
        ['marcos@facilmed.test', 'medico', '778899',                    5, null],
        ['marcos@facilmed.test', 'medico', '334455',                    5, 'Ótima com crianças.'],
    ];

    public function run(): void
    {
        foreach (self::AVALIACOES as [$email, $tipo, $alvo, $estrelas, $comentario]) {
            $usuario = Usuario::whereHas('user', fn ($q) => $q->where('email', $email))->first();
            $chave = $tipo === 'local'
                ? ['local_id' => Local::where('nome', $alvo)->value('id')]
                : ['medico_id' => Medico::where('crm', $alvo)->where('uf', 'SP')->value('id')];

            if (! $usuario || ! reset($chave)) {
                throw new \RuntimeException("AvaliacaoSeeder: não achei {$email} ou {$alvo}. Rode os outros seeders antes.");
            }

            Avaliacao::updateOrCreate(
                ['usuario_id' => $usuario->id] + $chave,
                ['estrelas' => $estrelas, 'comentario' => $comentario]
            );
        }

        $this->command->info(count(self::AVALIACOES) . ' avaliações de exemplo.');
    }
}
