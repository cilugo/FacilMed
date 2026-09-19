<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medico extends Model
{
    protected $table = 'medicos';

    protected $fillable = [
        'user_id', 'cpf', 'crm', 'uf', 'status_verificacao',
        'verificado_por', 'verificado_em', 'motivo_rejeicao',
        'bio', 'telefone_profissional', 'anos_atuacao', 'foto',
        'senha_temporaria',
    ];

    protected $casts = [
        'verificado_em'     => 'datetime',
        'senha_temporaria'  => 'boolean',
        'media_avaliacoes'  => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function especialidades(): BelongsToMany
    {
        return $this->belongsToMany(Especialidade::class, 'medico_especialidade')
                    ->withPivot('principal');
    }

    public function convenios(): BelongsToMany
    {
        return $this->belongsToMany(Convenio::class, 'convenio_medico');
    }

    public function vinculos(): HasMany
    {
        return $this->hasMany(Vinculo::class);
    }

    public function bloqueios(): HasMany
    {
        return $this->hasMany(Bloqueio::class);
    }

    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class);
    }

    public function avaliacoes(): HasMany
    {
        return $this->hasMany(Avaliacao::class);
    }

    /**
     * REGRA INVIOLAVEL (AGENTS.md secao 6): medico nao verificado
     * nunca aparece em busca ou listagem publica. Use este scope
     * em TODA consulta que alimenta tela de paciente.
     */
    public function scopeVisivel(Builder $q): Builder
    {
        return $q->where('status_verificacao', 'verificado')
                 ->whereHas('user', fn ($u) => $u->where('status', 'ativo'));
    }

    /** Recalcula o cache de avaliacoes. Chamar ao salvar avaliacao. */
    public function recalcularAvaliacoes(): void
    {
        $this->forceFill([
            'media_avaliacoes' => (float) $this->avaliacoes()->avg('estrelas'),
            'total_avaliacoes' => $this->avaliacoes()->count(),
        ])->save();
    }
}
