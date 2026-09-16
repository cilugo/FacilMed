<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Convenio extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Atributos que podem ser preenchidos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nome',
        'cnpj',
        'telefone',
        'email',
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
     * Relacionamento: médicos que aceitam este convênio.
     * Um médico pode aceitar vários convênios, e um convênio pode ser
     * aceito por vários médicos (ex: Unimed + Amil + Bradesco Saúde).
     * Pressupõe uma tabela pivô 'convenio_medico' (criada junto ao CRUD de Médicos).
     */
    public function medicos()
    {
        return $this->belongsToMany(Medico::class, 'convenio_medico');
    }

    /**
     * Escopo para retornar apenas convênios ativos.
     */
    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }
}
