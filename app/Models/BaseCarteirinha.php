<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "Operadora simulada": as carteirinhas que os convênios fictícios
 * emitiram. Só leitura para o sistema — quem preenche é o
 * BaseSimuladaSeeder. Consultada por App\Services\BaseSimulada.
 */
class BaseCarteirinha extends Model
{
    protected $table = 'base_carteirinhas';

    protected $fillable = [
        'plano_id', 'numero_carteirinha', 'beneficiario_nome',
        'beneficiario_cpf', 'validade', 'situacao',
    ];

    protected $casts = ['validade' => 'date'];

    public function plano(): BelongsTo
    {
        return $this->belongsTo(Plano::class);
    }
}
