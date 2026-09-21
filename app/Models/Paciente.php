<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Paciente extends Model
{
    protected $table = 'pacientes';

    protected $fillable = ['user_id', 'cpf', 'data_nascimento', 'sexo'];

    protected $casts = ['data_nascimento' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Dado sensivel de saude. Nunca carregue com `with()` em
     * listagem - so no contexto de uma consulta especifica,
     * e passando pela Policy.
     */
    public function acessibilidade(): HasOne
    {
        return $this->hasOne(PacienteAcessibilidade::class);
    }

    public function planos(): HasMany
    {
        return $this->hasMany(PacientePlano::class);
    }

    public function planosAtivos(): HasMany
    {
        return $this->planos()->where('status', 'ativa');
    }

    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class);
    }

    public function getIdadeAttribute(): ?int
    {
        return $this->data_nascimento?->age;
    }
}
