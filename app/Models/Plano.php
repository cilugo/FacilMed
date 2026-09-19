<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Planos sao ficticios; a operadora acima e que e real. */
class Plano extends Model
{
    protected $table = 'planos';

    protected $fillable = ['convenio_id', 'nome', 'descricao', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function convenio(): BelongsTo
    {
        return $this->belongsTo(Convenio::class);
    }

    public function pacientePlanos(): HasMany
    {
        return $this->hasMany(PacientePlano::class);
    }
}
