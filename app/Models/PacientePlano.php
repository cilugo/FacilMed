<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A carteirinha. Nao existe validacao automatica - nem API da ANS,
 * nem TISS. O status so vira 'ativa' por conferencia humana do
 * codigo do comprovante COMPROVA.
 */
class PacientePlano extends Model
{
    protected $table = 'paciente_planos';

    protected $fillable = [
        'paciente_id', 'plano_id', 'numero_carteirinha', 'validade',
        'titular_nome', 'codigo_comprova_ans', 'comprova_emitido_em',
        'status', 'motivo_recusa', 'conferido_por', 'conferido_em',
    ];

    protected $casts = [
        'validade'            => 'date',
        'comprova_emitido_em' => 'date',
        'conferido_em'        => 'datetime',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function plano(): BelongsTo
    {
        return $this->belongsTo(Plano::class);
    }

    public function scopeUtilizavel(Builder $q): Builder
    {
        return $q->where('status', 'ativa')
                 ->where(fn ($s) => $s->whereNull('validade')
                                      ->orWhere('validade', '>=', now()->toDateString()));
    }

    public function estaVencida(): bool
    {
        return $this->validade !== null && $this->validade->isPast();
    }
}
