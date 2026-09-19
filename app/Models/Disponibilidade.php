<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bloco recorrente semanal. Almoco = dois blocos no mesmo dia.
 */
class Disponibilidade extends Model
{
    protected $table = 'disponibilidades';

    protected $fillable = [
        'vinculo_id', 'dia_semana', 'hora_inicio', 'hora_fim',
        'duracao_consulta_minutos', 'ativo',
    ];

    protected $casts = ['ativo' => 'boolean'];

    public const DIAS = [
        0 => 'domingo', 1 => 'segunda', 2 => 'terca', 3 => 'quarta',
        4 => 'quinta', 5 => 'sexta', 6 => 'sabado',
    ];

    public function vinculo(): BelongsTo
    {
        return $this->belongsTo(Vinculo::class);
    }
}
