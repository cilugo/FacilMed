<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // remova se não usar Sanctum para autenticação de API

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * Atributos que podem ser preenchidos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'cpf',
        'telefone',
        'data_nascimento',
        'password',
        'tipo_usuario',
        'ativo',
    ];

    /**
     * Atributos que devem ficar ocultos na serialização (ex: JSON).
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Conversão automática de tipos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'data_nascimento' => 'date',
            'ativo' => 'boolean',
        ];
    }

    /**
     * Relacionamento: um usuário pode ter um perfil de paciente.
     */
    public function paciente()
    {
        return $this->hasOne(Paciente::class);
    }

    /**
     * Relacionamento: um usuário pode ter um perfil de médico.
     */
    public function medico()
    {
        return $this->hasOne(Medico::class);
    }

    /**
     * Escopo para retornar apenas usuários ativos.
     */
    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }
}
