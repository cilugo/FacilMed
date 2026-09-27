<?php

namespace App\Console\Commands;

use App\Models\Consulta;
use App\Services\Notificador;
use Illuminate\Console\Command;

/**
 * Lembrete de consulta ~24h antes (24/09/2026).
 *
 * Roda de hora em hora (routes/console.php). Pode rodar quantas vezes
 * quiser: o UNIQUE (consulta_id, tipo) de notificacoes_enviadas garante
 * UM lembrete por consulta.
 *
 * Só lembra consulta marcada com MAIS de 24h de antecedência — quem
 * marcou para amanhã acabou de receber a confirmação.
 *
 * Localmente: `php artisan schedule:work` (deixa rodando) ou
 * `php artisan facilmed:enviar-lembretes` (uma vez).
 */
class EnviarLembretes extends Command
{
    protected $signature = 'facilmed:enviar-lembretes';

    protected $description = 'Envia o lembrete de 24h das consultas de amanhã (sem repetir).';

    public function handle(Notificador $notificador): int
    {
        $agora  = now();
        $limite = now()->addHours(24);

        $consultas = Consulta::query()
            ->where('status', 'agendada')
            ->whereDate('data_consulta', '>=', $agora->toDateString())
            ->whereDate('data_consulta', '<=', $limite->toDateString())
            ->whereDoesntHave('notificacoes', fn ($q) => $q->where('tipo', 'lembrete_24h'))
            ->get()
            ->filter(fn (Consulta $c) => $c->inicio->between($agora, $limite)
                && $c->created_at !== null
                && $c->created_at->lessThanOrEqualTo($c->inicio->copy()->subHours(24)));

        $enviados = 0;
        foreach ($consultas as $consulta) {
            if ($notificador->enviar($consulta, 'lembrete_24h', 'paciente')) {
                $enviados++;
            }
        }

        $this->info("Lembretes enviados: {$enviados}.");

        return self::SUCCESS;
    }
}
