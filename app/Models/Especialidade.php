<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Especialidade extends Model
{
    protected $table = 'especialidades';

    protected $fillable = ['nome', 'slug', 'icone', 'destaque', 'ativo'];

    protected $casts = ['destaque' => 'boolean', 'ativo' => 'boolean'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function medicos(): BelongsToMany
    {
        return $this->belongsToMany(Medico::class, 'medico_especialidade')
                    ->withPivot('principal');
    }

    /**
     * Cria uma especialidade (admin ou, desde 01/10/2026, a clínica).
     *
     * Nomes diferentes podem dar o MESMO slug ("Clínica-Geral" e "Clínica
     * Geral" viram clinica-geral) e o UNIQUE do banco dava erro 500 (28/09).
     * Aqui o repetido vira erro de validação no campo "nome".
     */
    public static function criar(string $nome, array $extra = []): self
    {
        $nome = trim(preg_replace('/\s+/', ' ', $nome));
        $slug = Str::slug($nome);

        if ($slug === '' || self::where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['nome' => 'Já existe uma especialidade com esse nome.']);
        }

        return self::create(['nome' => $nome, 'slug' => $slug, 'ativo' => true] + $extra);
    }

    /** Alimenta os cards da home. A view faz foreach nisto. */
    public function scopeEmDestaque(Builder $q): Builder
    {
        return $q->where('destaque', true)->where('ativo', true)->orderBy('nome');
    }
}
