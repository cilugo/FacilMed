<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Feriado extends Model
{
    protected $table = 'feriados';

    protected $fillable = ['data', 'nome', 'abrangencia', 'cidade', 'uf', 'ativo'];

    protected $casts = [
        'data'  => 'date',
        'ativo' => 'boolean',
    ];

    /** Locais que escolheram seguir este feriado. */
    public function locais(): BelongsToMany
    {
        return $this->belongsToMany(Local::class, 'feriado_local');
    }

    /**
     * Este local esta fechado nesta data por causa de feriado?
     *
     * Duas situacoes fecham:
     *   1. feriado NACIONAL na data - vale para todo mundo;
     *   2. feriado municipal ou facultativo na data QUE ESTE LOCAL
     *      marcou que segue.
     *
     * Feriado municipal de outra cidade que o local nao segue nao
     * fecha nada - e por isso que a verificacao passa pelo pivot.
     */
    public static function fecha(Local $local, \DateTimeInterface $data): bool
    {
        $dia = $data->format('Y-m-d');

        return static::query()
            ->where('ativo', true)
            ->whereDate('data', $dia)
            ->where(function (Builder $q) use ($local) {
                $q->where('abrangencia', 'nacional')
                  ->orWhereHas('locais', fn (Builder $l) => $l->where('locais.id', $local->id));
            })
            ->exists();
    }

    /**
     * Feriados que fazem sentido oferecer a este local para ele
     * escolher se segue: os facultativos (valem em qualquer lugar)
     * e os municipais da cidade dele.
     *
     * Os nacionais nao aparecem - nao ha o que escolher.
     */
    public function scopeOferecidosPara(Builder $q, Local $local): Builder
    {
        return $q->where('ativo', true)
                 ->where('data', '>=', now()->toDateString())
                 ->where(function (Builder $s) use ($local) {
                     $s->where('abrangencia', 'facultativo')
                       ->orWhere(fn (Builder $m) => $m->where('abrangencia', 'municipal')
                                                      ->where('cidade', $local->cidade)
                                                      ->where('uf', $local->uf));
                 })
                 ->orderBy('data');
    }
}
