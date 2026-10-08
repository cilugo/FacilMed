<?php

namespace App\Models;

use App\Support\Localizacao;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Local extends Model
{
    protected $table = 'locais';

    protected $fillable = [
        'clinica_id', 'nome', 'tipo', 'cep', 'endereco',
        'numero', 'complemento', 'bairro', 'cidade', 'uf', 'telefone', 'ativo',
        'latitude', 'longitude', 'faixa_preco',
    ];

    protected $casts = [
        'ativo' => 'boolean', 'latitude' => 'float', 'longitude' => 'float',
        'media_avaliacoes' => 'decimal:2',
        'faixa_preco' => 'integer',
    ];

    /**
     * 29/09/2026: todo local salvo sem coordenada ganha a APROXIMADA do bairro
     * ou do centro da cidade (config/localizacao.php) - e ganha de novo se o
     * endereço mudar. Assim a busca por distância funciona para o que vem do
     * seeder, do cadastro da clínica e das unidades,
     * sem cada tela ter que lembrar disso.
     *
     * static::saving = "antes de gravar" (evento do Eloquent). Quem passar a
     * coordenada na mão (latitude preenchida) não é sobrescrito.
     */
    protected static function booted(): void
    {
        static::saving(function (Local $local) {
            $mudouEndereco = $local->isDirty(['cidade', 'uf', 'bairro']);

            if (! $local->isDirty('latitude') && ($local->latitude === null || $mudouEndereco)) {
                [$local->latitude, $local->longitude] = Localizacao::coordenadas($local->cidade, $local->uf, $local->bairro)
                    ?? [null, null];
            }
        });
    }

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioFuncionamento::class);
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
     * Locais que aparecem para o usuário (busca por distância, página do
     * local): ativos e com pelo menos um médico público (Vinculo::publicos).
     * Com $slug, que tenham essa especialidade.
     */
    public function scopePublicos(Builder $q, ?string $slug = null): Builder
    {
        return $q->where('locais.ativo', true)
            ->whereHas('vinculos', fn ($v) => $v->publicos()->oferece($slug));
    }

    /**
     * A página pública do local pode abrir? Local ativo e a clínica dona
     * com a conta ativa. (01/10: todo local é de uma clínica/hospital —
     * o consultório próprio de médico saiu junto com a conta do médico.)
     */
    public function estaPublico(): bool
    {
        return $this->ativo && (bool) $this->clinica?->user?->estaAtivo();
    }

    /** Recalcula o cache de avaliações. Chamado pelo model Avaliacao. */
    public function recalcularAvaliacoes(): void
    {
        $this->forceFill([
            'media_avaliacoes' => (float) $this->avaliacoes()->avg('estrelas'),
            'total_avaliacoes' => $this->avaliacoes()->count(),
        ])->saveQuietly();
    }

    /** Distância em linha reta até um ponto, em km. Null se o local não tem coordenada. */
    public function distanciaAte(?float $lat, ?float $lng): ?float
    {
        if ($lat === null || $lng === null || $this->latitude === null || $this->longitude === null) {
            return null;
        }

        return Localizacao::distanciaKm($lat, $lng, $this->latitude, $this->longitude);
    }

    /** Quem pode editar preço e dados deste local: a clínica dona. */
    public function donoUserId(): ?int
    {
        return $this->clinica?->user_id;
    }

    public function getEnderecoCompletoAttribute(): string
    {
        return trim("{$this->endereco}, {$this->numero} - {$this->bairro}, {$this->cidade}/{$this->uf}");
    }

    /**
     * Troca o horário de funcionamento inteiro. $horarios = ['segunda' =>
     * ['abre' => '08:00', 'fecha' => '18:00'], ...]; dia ausente = fechado.
     * Vazio = seg-sex 08:00-18:00 (padrão para não nascer local fechado).
     */
    public function definirHorarios(array $horarios): void
    {
        if ($horarios === []) {
            $horarios = collect(['segunda', 'terca', 'quarta', 'quinta', 'sexta'])
                ->mapWithKeys(fn ($d) => [$d => ['abre' => '08:00', 'fecha' => '18:00']])->all();
        }

        $this->horarios()->delete();
        foreach ($horarios as $dia => $h) {
            $this->horarios()->create(['dia_semana' => $dia, 'abre' => $h['abre'], 'fecha' => $h['fecha']]);
        }
    }
}
