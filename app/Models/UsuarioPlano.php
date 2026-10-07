<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A carteirinha do usuário.
 *
 * Desde 24/09/2026 e conferida AUTOMATICAMENTE na base simulada
 * (base_carteirinhas) no momento do cadastro - ver
 * CadastroCarteirinhaRequest. Por isso so entra 'ativa'; o status
 * 'pendente' e os campos do COMPROVA ficaram para carteirinhas antigas.
 * conferido_por NULL + conferido_em preenchido = conferida pela base.
 */
class UsuarioPlano extends Model
{
    protected $table = 'usuario_planos';

    protected $fillable = [
        'usuario_id', 'plano_id', 'numero_carteirinha', 'validade',
        'titular_nome', 'codigo_comprova_ans', 'comprova_emitido_em',
        'status', 'motivo_recusa', 'conferido_por', 'conferido_em',
    ];

    protected $casts = [
        'validade'            => 'date',
        'comprova_emitido_em' => 'date',
        'conferido_em'        => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
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
