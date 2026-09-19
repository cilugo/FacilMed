<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Preco extends Model
{
    protected $table = 'precos';

    protected $fillable = ['vinculo_id', 'especialidade_id', 'valor', 'ativo'];

    protected $casts = ['valor' => 'decimal:2', 'ativo' => 'boolean'];

    public function vinculo(): BelongsTo
    {
        return $this->belongsTo(Vinculo::class);
    }

    public function especialidade(): BelongsTo
    {
        return $this->belongsTo(Especialidade::class);
    }
}
