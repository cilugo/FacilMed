<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Avaliação de um LOCAL ou de um MÉDICO (01/10/2026).
 *
 * Antes era por consulta realizada; sem agendamento, o usuário logado
 * avalia direto. Regras (garantidas no banco, não só na tela):
 *  - um alvo só: local OU médico (CHECK chk_avaliacao_um_alvo);
 *  - uma avaliação por usuário em cada local e em cada médico (UNIQUE) —
 *    avaliar de novo EDITA a anterior;
 *  - 1 a 5 estrelas (CHECK chk_avaliacao_estrelas).
 *
 * 05/10/2026 (decisão do grupo): o comentário é PÚBLICO, com o nome de quem
 * escreveu encurtado ("Ana B.", Formatador::nomeCurto) e sem foto.
 */
class Avaliacao extends Model
{
    protected $table = 'avaliacoes';

    protected $fillable = ['usuario_id', 'local_id', 'medico_id', 'estrelas', 'comentario'];

    protected $casts = ['estrelas' => 'integer'];

    protected static function booted(): void
    {
        // Mantém o cache media_avaliacoes/total_avaliacoes do alvo.
        $recalcular = function (Avaliacao $a) {
            $a->local?->recalcularAvaliacoes();
            $a->medico?->recalcularAvaliacoes();
        };

        static::saved($recalcular);
        static::deleted($recalcular);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class);
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class);
    }

    /**
     * Avaliações que a clínica pode LER (com comentário): as das unidades
     * dela e as dos médicos que atendem (vínculo ativo) nelas.
     */
    public function scopeDaClinica(Builder $q, int $clinicaId): Builder
    {
        $locais = Local::where('clinica_id', $clinicaId)->select('id');

        return $q->where(fn ($w) => $w
            ->whereIn('local_id', $locais)
            ->orWhereIn('medico_id', Vinculo::where('ativo', true)->whereIn('local_id', $locais)->select('medico_id')));
    }

    /** Nome do que foi avaliado, para listas (perfil do usuário, admin). */
    public function getAlvoNomeAttribute(): string
    {
        return $this->local?->nome ?? $this->medico?->nome ?? '—';
    }
}
