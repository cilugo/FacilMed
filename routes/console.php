<?php

use Illuminate\Support\Facades\Schedule;

/*
 * Tarefas agendadas do FacilMed.
 * Para funcionar localmente, deixe rodando: php artisan schedule:work
 */

// Lembrete 24h antes. De hora em hora; o UNIQUE em notificacoes_enviadas
// impede repetir o mesmo lembrete.
Schedule::command('facilmed:enviar-lembretes')->hourly()->withoutOverlapping();
