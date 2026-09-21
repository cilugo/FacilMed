<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Especialidade extends Model
{
    protected $table = 'especialidades';

    protected $fillable = ['nome', 'slug', 'icone', 'destaque', 'ativo'];

    protected $casts = ['destaque' => 'boolean', 'ativo' => 'boolean'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function medicos(): BelongsToMany
    {
        return $this->belongsToMany(Medico::class, 'medico_especialidade')
                    ->withPivot('principal');
    }

    /** Alimenta os cards da home. A view faz foreach nisto. */
    public function scopeEmDestaque(Builder $q): Builder
    {
        return $q->where('destaque', true)->where('ativo', true)->orderBy('nome');
    }
}
