<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Liga medico a local. Tudo que depende de "onde o medico atende"
 * pendura aqui: se ele atende particular e convênio naquele lugar.
 * (A faixa de preço é da UNIDADE desde 05/10: locais.faixa_preco.)
 */
class Vinculo extends Model
{
    protected $table = 'vinculos';

    protected $fillable = [
        'medico_id', 'local_id', 'aceita_particular', 'aceita_convenio', 'ativo',
    ];

    protected $casts = [
        'aceita_particular' => 'boolean',
        'aceita_convenio'   => 'boolean',
        'ativo'             => 'boolean',
    ];

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class);
    }

    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class);
    }

    /**
     * Este vínculo aparece para o usuário? Vínculo ativo, local ativo,
     * médico com CRM verificado e a conta da clínica dona ativa.
     * (Antes de 01/10 chamava recebeAgendamento.) A versão em consulta ao
     * banco é scopePublicos(): se mudar uma, mude a outra.
     */
    public function estaPublico(): bool
    {
        $medico = $this->medico;
        $local  = $this->local;

        return $this->ativo
            && $medico !== null && $medico->status_verificacao === 'verificado'
            && $local !== null && $local->estaPublico();
    }

    /**
     * estaPublico() em forma de CONSULTA AO BANCO, para a busca e os perfis
     * públicos filtrarem antes de mostrar.
     *
     *   Vinculo::publicos()->get()   // só os vínculos que o usuário pode ver
     *
     * (Método "scope": o Laravel tira o "scope" do nome na hora de chamar.)
     */
    public function scopePublicos(Builder $q): Builder
    {
        return $q->where('vinculos.ativo', true)
            ->whereHas('medico', fn ($m) => $m->visivel())
            ->whereHas('local', fn ($l) => $l->where('ativo', true)
                ->whereHas('clinica.user', fn ($u) => $u->where('status', 'ativo')));
    }

    /**
     * Oferece alguma especialidade aqui? Com $slug, oferece ESSA?
     * 05/10/2026: sem a Tabela de preços, a especialidade oferecida num
     * lugar é a especialidade ATIVA do médico que atende nele.
     */
    public function scopeOferece(Builder $q, ?string $slug = null): Builder
    {
        return $q->whereHas('medico.especialidades', fn ($e) => $e->where('especialidades.ativo', true)
            ->when($slug, fn ($e2) => $e2->where('especialidades.slug', $slug)));
    }

    /** As especialidades oferecidas aqui (mesma regra de scopeOferece). */
    public function especialidadesOferecidas(): \Illuminate\Support\Collection
    {
        return $this->medico->especialidades
            ->where('ativo', true)
            ->sortBy('nome')
            ->values();
    }
}
