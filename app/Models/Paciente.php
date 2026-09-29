<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Paciente extends Model
{
    protected $table = 'pacientes';

    protected $fillable = ['user_id', 'cpf', 'data_nascimento', 'sexo'];

    protected $casts = ['data_nascimento' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Dado sensivel de saude. Nunca carregue com `with()` em
     * listagem - so no contexto de uma consulta especifica,
     * e passando pela Policy.
     */
    public function acessibilidade(): HasOne
    {
        return $this->hasOne(PacienteAcessibilidade::class);
    }

    public function planos(): HasMany
    {
        return $this->hasMany(PacientePlano::class);
    }

    public function planosAtivos(): HasMany
    {
        return $this->planos()->where('status', 'ativa');
    }

    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class);
    }

    /**
     * 29/09/2026: o paciente não pode estar em dois lugares ao mesmo tempo.
     *
     * Devolve a consulta AGENDADA deste paciente que ocupa algum pedaço de
     * [$inicio, $inicio + $duracaoMinutos) — ou null se o horário está livre
     * para ele. O índice único do banco só olha o MÉDICO; esta regra olha o
     * PACIENTE (a Ana podia marcar 9h com a Dra. Helena e 9h com o Dr. Rafael).
     *
     * Mesma conta de sobreposição da CalculadoraDeHorarios: encostar não é
     * sobrepor (uma consulta que termina 9h30 não atrapalha outra às 9h30).
     *
     * $ignorarConsultaId: na remarcação, a consulta antiga é cancelada na mesma
     * operação — então ela não conta como conflito.
     */
    public function consultaNoHorario(CarbonInterface $inicio, int $duracaoMinutos, ?int $ignorarConsultaId = null): ?Consulta
    {
        $fim = $inicio->copy()->addMinutes($duracaoMinutos);

        return $this->consultas()
            ->agendadas()
            ->whereDate('data_consulta', $inicio->toDateString())
            ->when($ignorarConsultaId, fn ($q) => $q->whereKeyNot($ignorarConsultaId))
            ->with('medico.user', 'vinculo.local')
            ->get()
            ->first(function (Consulta $c) use ($inicio, $fim) {
                $cFim = $c->inicio->copy()->addMinutes((int) ($c->duracao_minutos ?: 30));

                return $c->inicio->lessThan($fim) && $cFim->greaterThan($inicio);
            });
    }

    public function getIdadeAttribute(): ?int
    {
        return $this->data_nascimento?->age;
    }
}
