<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Paciente extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Atributos que podem ser preenchidos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'numero_carteirinha',
    ];

    /**
     * Relacionamento: dados de conta do paciente (nome, e-mail, CPF, etc.)
     * ficam centralizados na tabela 'users'.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento: consultas/agendamentos do paciente.
     * Pressupõe a tabela 'consultas' (criada no módulo de Consultas/Agendamentos).
     */
    public function consultas()
    {
        return $this->hasMany(Consulta::class);
    }
}
