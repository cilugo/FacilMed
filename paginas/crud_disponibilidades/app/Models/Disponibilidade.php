<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Disponibilidade extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Atributos que podem ser preenchidos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'medico_id',
        'estabelecimento_id',
        'dia_semana',
        'data',
        'hora_inicio',
        'hora_fim',
        'duracao_consulta_minutos',
        'tipo_atendimento',
        'convenio_id',
        'bloqueado',
        'motivo_bloqueio',
        'ativo',
    ];

    /**
     * Conversão automática de tipos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'date',
        'bloqueado' => 'boolean',
        'ativo' => 'boolean',
    ];

    /**
     * Relacionamento: médico dono desta disponibilidade.
     */
    public function medico()
    {
        return $this->belongsTo(Medico::class);
    }

    /**
     * Relacionamento: local de atendimento (estabelecimento) desta disponibilidade.
     */
    public function estabelecimento()
    {
        return $this->belongsTo(Estabelecimento::class);
    }

    /**
     * Relacionamento: convênio associado, quando tipo_atendimento = 'convenio'.
     */
    public function convenio()
    {
        return $this->belongsTo(Convenio::class);
    }

    /**
     * Escopo para retornar apenas disponibilidades ativas e não bloqueadas
     * — ou seja, realmente disponíveis para agendamento.
     */
    public function scopeDisponiveis($query)
    {
        return $query->where('ativo', true)->where('bloqueado', false);
    }
}
