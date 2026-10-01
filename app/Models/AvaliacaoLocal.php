<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Avaliação do LOCAL (clínica, hospital) - 01/10/2026, plano novo do grupo.
 *
 * Qualquer paciente logado avalia, uma vez por local (UNIQUE no banco), e pode
 * editar ou excluir. Diferente da avaliação do MÉDICO (Avaliacao), que só sai
 * de consulta realizada.
 *
 * O COMENTÁRIO é privado: lê a clínica dona do local, o admin e o próprio
 * autor (no histórico do perfil). Na página do local aparecem só as estrelas.
 * Por isso ele está em $hidden.
 */
class AvaliacaoLocal extends Model
{
    protected $table = 'avaliacoes_locais';

    protected $fillable = ['local_id', 'paciente_id', 'estrelas', 'comentario'];

    protected $casts = ['estrelas' => 'integer'];

    protected $hidden = ['comentario'];

    /** Nome de cada nota, como no desenho da tela ("Ruim" a "Excelente"). */
    public const ROTULOS = [1 => 'Ruim', 2 => 'Regular', 3 => 'Bom', 4 => 'Muito bom', 5 => 'Excelente'];

    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class);
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }
}
