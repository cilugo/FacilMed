<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DADO SENSIVEL DE SAUDE - LGPD art. 11.
 *
 * Toda leitura passa por PacienteAcessibilidadePolicy.
 * Nunca incluir em listagem, busca, export ou log.
 */
class PacienteAcessibilidade extends Model
{
    protected $table = 'paciente_acessibilidade';

    protected $fillable = [
        'paciente_id', 'possui_deficiencia', 'descricao',
        'consentimento_em', 'consentimento_versao',
    ];

    protected $casts = [
        'possui_deficiencia' => 'boolean',
        'consentimento_em'   => 'datetime',
    ];

    protected $hidden = ['descricao'];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function temConsentimento(): bool
    {
        return $this->consentimento_em !== null;
    }
}
