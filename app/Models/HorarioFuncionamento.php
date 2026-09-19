<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorarioFuncionamento extends Model
{
    protected $table = 'horarios_funcionamento';

    public $timestamps = false;

    protected $fillable = ['local_id', 'dia_semana', 'abre', 'fecha'];

    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class);
    }
}
