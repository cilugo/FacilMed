<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ferias, feriado, imprevisto. */
class Bloqueio extends Model
{
    protected $table = 'bloqueios';

    protected $fillable = ['medico_id', 'vinculo_id', 'inicio', 'fim', 'motivo'];

    protected $casts = ['inicio' => 'datetime', 'fim' => 'datetime'];

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class);
    }

    public function vinculo(): BelongsTo
    {
        return $this->belongsTo(Vinculo::class);
    }
}
