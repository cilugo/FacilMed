<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Convênio FICTÍCIO (decisão do grupo, 24/09/2026).
 *
 * Nenhum convênio do FacilMed existe de verdade: SpSaúde, Horizonte Med
 * e Bem Viver Saúde foram inventados para a demonstração. Isso precisa
 * estar visível na tela e no texto do TCC — a plataforma não tem
 * contrato com nenhuma operadora.
 *
 * Hierarquia: Convênio (SpSaúde) → Planos (SpSaúde Família, SpSaúde
 * Empresarial...). O vínculo "este médico aceita este convênio" fica em
 * convenio_medico — é o MÉDICO que aceita, não o endereço.
 *
 * Nunca apague um convênio: desative (ativo = false). Apagar levaria em
 * cascata os planos e as carteirinhas dos pacientes.
 */
class Convenio extends Model
{
    protected $table = 'convenios';

    protected $fillable = [
        'operadora_ans_id', 'nome', 'cnpj', 'telefone', 'email',
        'descricao', 'logo', 'ativo',
    ];

    protected $casts = ['ativo' => 'boolean'];

    /** Opcional desde 24/09. Fica para quem quiser ligar a uma operadora real. */
    public function operadora(): BelongsTo
    {
        return $this->belongsTo(OperadoraAns::class, 'operadora_ans_id');
    }

    public function planos(): HasMany
    {
        return $this->hasMany(Plano::class)->orderBy('nome');
    }

    public function medicos(): BelongsToMany
    {
        return $this->belongsToMany(Medico::class, 'convenio_medico');
    }

    public function scopeAtivos(Builder $q): Builder
    {
        return $q->where('ativo', true);
    }
}
