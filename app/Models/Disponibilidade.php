<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Services\EstatisticasDeConsultas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bloco recorrente semanal. Almoco = dois blocos no mesmo dia.
 */
class Disponibilidade extends Model
{
    protected $table = 'disponibilidades';

    protected $fillable = [
        'vinculo_id', 'dia_semana', 'hora_inicio', 'hora_fim',
        'duracao_consulta_minutos', 'ativo',
    ];

    protected $casts = ['ativo' => 'boolean'];

    public const DIAS = [
        0 => 'domingo', 1 => 'segunda', 2 => 'terca', 3 => 'quarta',
        4 => 'quinta', 5 => 'sexta', 6 => 'sabado',
    ];

    public function vinculo(): BelongsTo
    {
        return $this->belongsTo(Vinculo::class);
    }

    /**
     * Consultas AGENDADAS, daqui para frente, que caem dentro deste bloco
     * (mesmo vínculo, mesmo dia da semana, horário entre início e fim).
     */
    public function consultasFuturasDentro(): Builder
    {
        $diaMysql = array_search($this->dia_semana, self::DIAS, true) + 1; // DAYOFWEEK: 1 = domingo

        return EstatisticasDeConsultas::aPartirDeAgora(
            Consulta::query()
                ->where('vinculo_id', $this->vinculo_id)
                ->where('status', 'agendada')
                ->whereRaw('DAYOFWEEK(data_consulta) = ?', [$diaMysql])
                ->where('horario', '>=', $this->hora_inicio)
                ->where('horario', '<', $this->hora_fim)
        );
    }
}
