<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ferias, feriado, imprevisto. */
class Bloqueio extends Model
{
    protected $table = 'bloqueios';

    protected $fillable = ['medico_id', 'vinculo_id', 'inicio', 'fim', 'motivo'];

    protected $casts = ['inicio' => 'datetime', 'fim' => 'datetime'];

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class);
    }

    public function vinculo(): BelongsTo
    {
        return $this->belongsTo(Vinculo::class);
    }

    /**
     * Consultas AGENDADAS que caem dentro desta ausência (todas as do
     * médico, ou só as do vínculo, se a ausência for de um lugar só).
     */
    public function consultasAgendadasNoPeriodo(): Builder
    {
        return Consulta::query()
            ->where('medico_id', $this->medico_id)
            ->when($this->vinculo_id, fn ($q) => $q->where('vinculo_id', $this->vinculo_id))
            ->where('status', 'agendada')
            ->whereDate('data_consulta', '>=', $this->inicio->toDateString())
            ->whereDate('data_consulta', '<=', $this->fim->toDateString())
            ->get(['id', 'data_consulta', 'horario', 'duracao_minutos'])
            ->filter(function (Consulta $c) {
                $fimConsulta = $c->inicio->copy()->addMinutes((int) ($c->duracao_minutos ?: 30));

                return $c->inicio->lessThan($this->fim) && $fimConsulta->greaterThan($this->inicio);
            })
            ->pipe(fn ($col) => Consulta::query()->whereIn('id', $col->pluck('id')));
    }
}
