<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Liga medico a local. Tudo que depende de "onde o medico atende"
 * pendura aqui: preco, disponibilidade e a propria consulta.
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

    public function precos(): HasMany
    {
        return $this->hasMany(Preco::class);
    }

    public function disponibilidades(): HasMany
    {
        return $this->hasMany(Disponibilidade::class);
    }

    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class);
    }

    public function precoDe(int $especialidadeId): ?float
    {
        $p = $this->precos->firstWhere('especialidade_id', $especialidadeId);

        return $p && $p->ativo ? (float) $p->valor : null;
    }

    /**
     * Este vínculo pode receber agendamento AGORA? (28/09/2026)
     *
     * UM lugar só para a pergunta — a CalculadoraDeHorarios, a tela de
     * horário e a gravação chamam isto. Antes cada um conferia um pedaço
     * e o médico com a CONTA BLOQUEADA continuava recebendo consulta
     * (só se olhava o CRM verificado, não o status da conta).
     *
     * Precisa de tudo: vínculo ativo, local ativo, médico verificado e com
     * conta ativa e, se o local é de clínica, a conta da clínica ativa.
     */
    public function recebeAgendamento(): bool
    {
        $medico = $this->medico;
        $local  = $this->local;

        return $this->ativo
            && $medico !== null && $medico->status_verificacao === 'verificado'
            && $medico->user?->estaAtivo()
            && $local !== null && $local->ativo
            && ($local->clinica_id === null || (bool) $local->clinica?->user?->estaAtivo());
    }

    /**
     * Esta especialidade é oferecida AQUI? (28/09/2026)
     *
     * Oferecida = tem preço ATIVO neste vínculo e a especialidade está
     * ativa na plataforma. Vale para particular E convênio: antes, por
     * convênio, bastava existir uma linha de preço — mesmo desativada
     * (especialidade tirada pelo médico ou deixada em branco pela
     * clínica) — e a consulta era marcada.
     */
    public function ofereceEspecialidade(int $especialidadeId): bool
    {
        $preco = $this->precos->first(fn ($p) => (int) $p->especialidade_id === $especialidadeId && $p->ativo);

        return $preco !== null && (bool) $preco->especialidade?->ativo;
    }

    /**
     * Os preços que o paciente pode ver e marcar aqui: preço ATIVO de
     * especialidade ATIVA (a mesma regra de ofereceEspecialidade). 28/09,
     * 3ª revisão: o perfil público e a busca mostravam preço de
     * especialidade desativada pelo admin.
     */
    public function precosOferecidos(): \Illuminate\Support\Collection
    {
        return $this->precos
            ->filter(fn ($p) => $p->ativo && $p->especialidade?->ativo)
            ->sortBy(fn ($p) => $p->especialidade->nome)
            ->values();
    }

    /**
     * recebeAgendamento() em forma de CONSULTA AO BANCO (28/09, 3ª revisão),
     * para a busca e o perfil público filtrarem antes de mostrar. As duas
     * precisam dizer a mesma coisa: se mudar uma, mude a outra.
     *
     *   Vinculo::agendaveis()->get()   // só os vínculos que dá para agendar
     *
     * (Método "scope": o Laravel tira o "scope" do nome na hora de chamar.
     * Tem outro nome porque recebeAgendamento() já é a versão de UM vínculo.)
     */
    public function scopeAgendaveis(Builder $q): Builder
    {
        return $q->where('vinculos.ativo', true)
            ->whereHas('medico', fn ($m) => $m->visivel())
            ->whereHas('local', fn ($l) => $l->where('ativo', true)
                ->where(fn ($dono) => $dono->whereNull('clinica_id')
                    ->orWhereHas('clinica.user', fn ($u) => $u->where('status', 'ativo'))));
    }

    /**
     * Oferece alguma especialidade aqui (preço ativo + especialidade ativa)?
     * Com $slug, oferece ESSA especialidade? Mesma regra de ofereceEspecialidade().
     */
    public function scopeOferece(Builder $q, ?string $slug = null): Builder
    {
        return $q->whereHas('precos', fn ($p) => $p->where('ativo', true)
            ->whereHas('especialidade', fn ($e) => $e->where('ativo', true)
                ->when($slug, fn ($e2) => $e2->where('slug', $slug))));
    }

    /** As especialidades oferecidas aqui (mesma regra de ofereceEspecialidade). */
    public function especialidadesOferecidas(): \Illuminate\Support\Collection
    {
        return $this->precos
            ->filter(fn ($p) => $p->ativo && $p->especialidade?->ativo)
            ->map(fn ($p) => $p->especialidade)
            ->unique('id')
            ->sortBy('nome')
            ->values();
    }
}
