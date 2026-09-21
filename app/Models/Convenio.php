<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Convenio extends Model
{
    protected $table = 'convenios';

    protected $fillable = ['operadora_ans_id', 'nome', 'descricao', 'logo', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function operadora(): BelongsTo
    {
        return $this->belongsTo(OperadoraAns::class, 'operadora_ans_id');
    }

    public function planos(): HasMany
    {
        return $this->hasMany(Plano::class);
    }

    public function medicos(): BelongsToMany
    {
        return $this->belongsToMany(Medico::class, 'convenio_medico');
    }
}
