<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Consulta extends Model
{
    protected $table = 'consultas';

    protected $fillable = [
        'paciente_id', 'medico_id', 'vinculo_id', 'especialidade_id',
        'data_consulta', 'horario', 'duracao_minutos',
        'forma_pagamento', 'paciente_plano_id', 'valor',
        'status', 'origem', 'observacoes',
        'cancelada_por', 'cancelada_em', 'motivo_cancelamento', 'cancelamento_tardio',
    ];

    protected $casts = [
        'data_consulta'       => 'date',
        'cancelada_em'        => 'datetime',
        'valor'               => 'decimal:2',
        'cancelamento_tardio' => 'boolean',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class);
    }

    public function vinculo(): BelongsTo
    {
        return $this->belongsTo(Vinculo::class);
    }

    public function especialidade(): BelongsTo
    {
        return $this->belongsTo(Especialidade::class);
    }

    public function pacientePlano(): BelongsTo
    {
        return $this->belongsTo(PacientePlano::class);
    }

    public function avaliacao(): HasOne
    {
        return $this->hasOne(Avaliacao::class);
    }

    public function notificacoes(): HasMany
    {
        return $this->hasMany(NotificacaoEnviada::class);
    }

    public function scopeAgendadas(Builder $q): Builder
    {
        return $q->where('status', 'agendada');
    }

    /** Momento exato da consulta, juntando data + horario. */
    public function getInicioAttribute(): \Carbon\Carbon
    {
        return \Carbon\Carbon::parse(
            $this->data_consulta->toDateString() . ' ' . $this->horario
        );
    }

    public function podeSerCancelada(): bool
    {
        return $this->status === 'agendada' && $this->inicio->isFuture();
    }

    /**
     * Cancelamento abaixo de 24h e permitido, mas marcado.
     * Bloquear nao faz a pessoa comparecer - faz ela faltar,
     * e falta perde o horario enquanto cancelamento devolve.
     */
    public function ehCancelamentoTardio(): bool
    {
        // now() -> inicio (e nao o contrario): no Carbon 3 diffInHours tem SINAL.
        // Do jeito antigo dava sempre negativo, e TODO cancelamento virava tardio.
        return now()->diffInHours($this->inicio) < 24;
    }

    /**
     * Cancela a consulta (24/09). UM lugar só para a regra - paciente,
     * médico, ausência do médico, remarcação e bloqueio de conta passam
     * por aqui. Cancelamento nunca é bloqueado (AGENTS.md §6); abaixo de
     * 24h fica marcado como tardio.
     *
     * Aviso por e-mail (App\Services\Notificador), só depois do commit:
     * - paciente cancelou → avisa o médico;
     * - qualquer outro (médico, clínica, admin, ausência) → avisa o paciente;
     * - remarcação → avisa o médico que o horário antigo foi liberado.
     */
    public function cancelar(?int $porUserId, ?string $motivo = null, bool $remarcacao = false): void
    {
        $this->update([
            'status'              => 'cancelada',
            'cancelada_por'       => $porUserId,
            'cancelada_em'        => now(),
            'motivo_cancelamento' => $motivo !== null ? mb_substr($motivo, 0, 255) : null,
            'cancelamento_tardio' => $this->ehCancelamentoTardio(),
        ]);

        $pacienteCancelou = $porUserId !== null && $porUserId === $this->paciente?->user_id;

        \Illuminate\Support\Facades\DB::afterCommit(fn () => app(\App\Services\Notificador::class)->enviar(
            $this,
            $remarcacao ? 'remarcacao' : 'cancelamento',
            ($remarcacao || $pacienteCancelou) ? 'medico' : 'paciente',
        ));
    }

    /** Só consulta realizada pode ser avaliada. */
    public function podeSerAvaliada(): bool
    {
        return $this->status === 'realizada' && $this->avaliacao === null;
    }
}
