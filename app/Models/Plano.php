<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Plano de um convênio fictício (ex.: "SpSaúde Família").
 *
 * `tipo` e `abrangencia` entraram na migration de 24/09. As listas abaixo
 * são a fonte única para os <select> das telas e para a validação — não
 * escreva as opções direto no Blade.
 */
class Plano extends Model
{
    public const TIPOS = [
        'individual'  => 'Individual',
        'familiar'    => 'Familiar',
        'empresarial' => 'Empresarial',
    ];

    public const ABRANGENCIAS = [
        'municipal' => 'Municipal',
        'estadual'  => 'Estadual',
        'nacional'  => 'Nacional',
    ];

    protected $table = 'planos';

    protected $fillable = ['convenio_id', 'nome', 'tipo', 'abrangencia', 'descricao', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function convenio(): BelongsTo
    {
        return $this->belongsTo(Convenio::class);
    }

    public function pacientePlanos(): HasMany
    {
        return $this->hasMany(PacientePlano::class);
    }

    public function scopeAtivos(Builder $q): Builder
    {
        return $q->where('ativo', true);
    }

    public function getTipoRotuloAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? ucfirst((string) $this->tipo);
    }

    public function getAbrangenciaRotuloAttribute(): string
    {
        return self::ABRANGENCIAS[$this->abrangencia] ?? ucfirst((string) $this->abrangencia);
    }
}
