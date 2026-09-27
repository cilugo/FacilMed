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
