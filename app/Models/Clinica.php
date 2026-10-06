<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Clinica extends Model
{
    protected $table = 'clinicas';

    protected $fillable = [
        'user_id', 'cnpj', 'razao_social', 'nome_fantasia',
        'descricao', 'telefone', 'logo',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function locais(): HasMany
    {
        return $this->hasMany(Local::class);
    }

    public function vinculos(): HasManyThrough
    {
        return $this->hasManyThrough(Vinculo::class, Local::class);
    }

    /**
     * As especialidades da clinica sao DERIVADAS dos medicos
     * vinculados a ela - nao existe tabela propria para isso,
     * de proposito: seria mais uma coisa para manter em sincronia.
     */
    public function especialidades()
    {
        // O que a clínica OFERECE: especialidades ativas de médico visível com
        // vínculo ativo numa unidade ativa desta clínica (05/10: sem preço).
        return Especialidade::where('ativo', true)
            ->whereHas('medicos', fn ($m) => $m->visivel()
                ->whereHas('vinculos', fn ($v) => $v->where('vinculos.ativo', true)
                    ->whereHas('local', fn ($l) => $l->where('clinica_id', $this->id)->where('ativo', true))));
    }
}
