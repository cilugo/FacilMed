<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Administrador extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Atributos que podem ser preenchidos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'cargo',
        'ativo',
    ];

    /**
     * Conversão automática de tipos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ativo' => 'boolean',
    ];

    /**
     * Relacionamento: dados de conta do administrador (nome, e-mail, CPF, etc.)
     * ficam centralizados na tabela 'users'.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Escopo para retornar apenas administradores ativos.
     */
    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }
}
