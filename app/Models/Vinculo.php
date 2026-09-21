<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Liga medico a local. Tudo que depende de "onde o medico atende"
 * pendura aqui: preco, disponibilidade e a propria consulta.
 */
class Vinculo extends Model
{
    protected $table = 'vinculos';

    protected $fillable = [
        'medico_id', 'local_id', 'aceita_particular', 'aceita_convenio', 'ativo',
    ];

    protected $casts = [
        'aceita_particular' => 'boolean',
        'aceita_convenio'   => 'boolean',
        'ativo'             => 'boolean',
    ];

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class);
    }

    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class);
    }

    public function precos(): HasMany
    {
        return $this->hasMany(Preco::class);
    }

    public function disponibilidades(): HasMany
    {
        return $this->hasMany(Disponibilidade::class);
    }

    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class);
    }

    public function precoDe(int $especialidadeId): ?float
    {
        $p = $this->precos->firstWhere('especialidade_id', $especialidadeId);

        return $p && $p->ativo ? (float) $p->valor : null;
    }
}
