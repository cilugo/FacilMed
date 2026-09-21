<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificacaoEnviada extends Model
{
    protected $table = 'notificacoes_enviadas';

    protected $fillable = [
        'consulta_id', 'tipo', 'destinatario', 'enviada_em', 'sucesso', 'erro',
    ];

    protected $casts = ['enviada_em' => 'datetime', 'sucesso' => 'boolean'];

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class);
    }
}
