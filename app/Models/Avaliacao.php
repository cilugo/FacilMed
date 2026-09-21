<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * O COMENTARIO e privado: so o medico avaliado, a clinica dele e
 * o admin leem. O publico ve apenas as estrelas. Isso e regra de
 * autorizacao (AvaliacaoPolicy), nao coluna.
 */
class Avaliacao extends Model
{
    protected $table = 'avaliacoes';

    protected $fillable = [
        'consulta_id', 'paciente_id', 'medico_id', 'estrelas', 'comentario',
    ];

    protected $casts = ['estrelas' => 'integer'];

    protected $hidden = ['comentario'];

    protected static function booted(): void
    {
        // Mantem o cache media_avaliacoes/total_avaliacoes do medico.
        static::saved(fn (Avaliacao $a) => $a->medico?->recalcularAvaliacoes());
        static::deleted(fn (Avaliacao $a) => $a->medico?->recalcularAvaliacoes());
    }

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class);
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class);
    }
}
