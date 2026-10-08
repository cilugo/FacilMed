<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorarioFuncionamento extends Model
{
    protected $table = 'horarios_funcionamento';

    public $timestamps = false;

    protected $fillable = ['local_id', 'dia_semana', 'abre', 'fecha'];

    /**
     * Índice = Carbon::dayOfWeek (0 = domingo). Morava em Disponibilidade::DIAS
     * até 01/10/2026, quando os horários do médico saíram com o agendamento.
     */
    public const DIAS = [
        0 => 'domingo', 1 => 'segunda', 2 => 'terca', 3 => 'quarta',
        4 => 'quinta', 5 => 'sexta', 6 => 'sabado',
    ];

    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class);
    }
}
