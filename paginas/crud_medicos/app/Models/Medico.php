<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medico extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Atributos que podem ser preenchidos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'crm',
        'uf_crm',
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
     * Relacionamento: dados de conta do médico (nome, e-mail, CPF, etc.)
     * ficam centralizados na tabela 'users'.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento: especialidades do médico (ex: Cardiologia + Clínica Geral).
     */
    public function especialidades()
    {
        return $this->belongsToMany(Especialidade::class, 'especialidade_medico');
    }

    /**
     * Relacionamento: locais (clínicas/hospitais) onde o médico atende.
     */
    public function estabelecimentos()
    {
        return $this->belongsToMany(Estabelecimento::class, 'estabelecimento_medico');
    }

    /**
     * Relacionamento: convênios aceitos pelo médico.
     */
    public function convenios()
    {
        return $this->belongsToMany(Convenio::class, 'convenio_medico');
    }

    /**
     * Relacionamento: horários de disponibilidade do médico.
     * Pressupõe a tabela 'disponibilidades' (criada no módulo de Horários).
     */
    public function disponibilidades()
    {
        return $this->hasMany(Disponibilidade::class);
    }

    /**
     * Relacionamento: consultas/agendamentos do médico.
     * Pressupõe a tabela 'consultas' (criada no módulo de Consultas/Agendamentos).
     */
    public function consultas()
    {
        return $this->hasMany(Consulta::class);
    }

    /**
     * Escopo para retornar apenas médicos ativos.
     */
    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }
}
