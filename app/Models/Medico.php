<?php

namespace App\Models;

use App\Support\FotoDePerfil;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Médico = PERFIL, não conta (01/10/2026).
 *
 * Quem cadastra e mantém o perfil é a clínica/hospital onde ele atende
 * (Clinica\MedicoController). O médico não tem login: o usuário vê o
 * perfil (nome, foto, especialidades, anos de carreira, onde atende e
 * nota), mas ninguém "entra" como médico.
 */
class Medico extends Model
{
    protected $table = 'medicos';

    protected $fillable = [
        'nome', 'cpf', 'crm', 'uf', 'status_verificacao',
        'verificado_por', 'verificado_em', 'motivo_rejeicao',
        'bio', 'telefone_profissional', 'anos_atuacao', 'foto',
    ];

    protected $casts = [
        'verificado_em'    => 'datetime',
        'media_avaliacoes' => 'decimal:2',
    ];

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

    public function avaliacoes(): HasMany
    {
        return $this->hasMany(Avaliacao::class);
    }

    /**
     * Pode aparecer em busca, listagem e perfil público? (AGENTS.md §3)
     * Só com o CRM conferido na base simulada (status 'verificado').
     */
    public function scopeVisivel(Builder $q): Builder
    {
        return $q->where('status_verificacao', 'verificado');
    }

    /**
     * A clínica pode editar este perfil? Pode, se ele atende (vínculo
     * ativo) em alguma unidade dela. Usado pela MedicoPolicy.
     */
    public function atendeNaClinica(?int $clinicaId): bool
    {
        return $clinicaId !== null && $this->vinculos()
            ->where('ativo', true)
            ->whereHas('local', fn ($l) => $l->where('clinica_id', $clinicaId))
            ->exists();
    }

    /** Recalcula o cache de avaliações. Chamado pelo model Avaliacao. */
    public function recalcularAvaliacoes(): void
    {
        $this->forceFill([
            'media_avaliacoes' => (float) $this->avaliacoes()->avg('estrelas'),
            'total_avaliacoes' => $this->avaliacoes()->count(),
        ])->save();
    }

    public function getFotoUrlAttribute(): ?string
    {
        return FotoDePerfil::url($this->foto);
    }
}
