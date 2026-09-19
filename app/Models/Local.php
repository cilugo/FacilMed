<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Local extends Model
{
    protected $table = 'locais';

    protected $fillable = [
        'clinica_id', 'medico_id', 'nome', 'tipo', 'cep', 'endereco',
        'numero', 'complemento', 'bairro', 'cidade', 'uf', 'telefone', 'ativo',
    ];

    protected $casts = ['ativo' => 'boolean'];

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    /** Preenchido apenas quando e consultorio proprio de autonomo. */
    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class);
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioFuncionamento::class);
    }

    public function vinculos(): HasMany
    {
        return $this->hasMany(Vinculo::class);
    }

    public function ehConsultorioProprio(): bool
    {
        return $this->clinica_id === null;
    }

    /**
     * Quem pode editar preco e dados deste local:
     * a clinica dona, ou o medico autonomo dono.
     */
    public function donoUserId(): ?int
    {
        return $this->ehConsultorioProprio()
            ? $this->medico?->user_id
            : $this->clinica?->user_id;
    }

    public function getEnderecoCompletoAttribute(): string
    {
        return trim("{$this->endereco}, {$this->numero} - {$this->bairro}, {$this->cidade}/{$this->uf}");
    }
}
