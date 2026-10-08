<?php

namespace App\Models;

use App\Support\FotoDePerfil;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Conta de acesso. Desde 01/10/2026 só três tipos têm conta: usuário,
 * clínica/hospital e admin. O médico virou perfil cadastrado pela clínica
 * (tabela medicos, sem login) — o ENUM de users.tipo nem aceita mais 'medico'.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const TIPO_USUARIO = 'usuario';
    public const TIPO_CLINICA  = 'clinica';
    public const TIPO_ADMIN    = 'admin';

    protected $fillable = [
        'name', 'email', 'password', 'tipo', 'telefone', 'status', 'foto',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'bloqueado_em'      => 'datetime',
            'excluida_em'       => 'datetime',   // 30/09: o usuário excluiu a conta (Usuario::excluirConta)
        ];
    }

    // ---------------------------------------------------------------
    // Perfis (1:1). Apenas um deles existe, conforme o tipo.
    // ---------------------------------------------------------------

    public function usuario(): HasOne
    {
        return $this->hasOne(Usuario::class);
    }

    public function clinica(): HasOne
    {
        return $this->hasOne(Clinica::class);
    }

    public function perfil(): ?object
    {
        return match ($this->tipo) {
            self::TIPO_USUARIO => $this->usuario,
            self::TIPO_CLINICA  => $this->clinica,
            default             => null,
        };
    }

    // ---------------------------------------------------------------
    // Papéis
    // ---------------------------------------------------------------

    public function ehUsuario(): bool { return $this->tipo === self::TIPO_USUARIO; }
    public function ehClinica(): bool  { return $this->tipo === self::TIPO_CLINICA; }
    public function ehAdmin(): bool    { return $this->tipo === self::TIPO_ADMIN; }

    // ---------------------------------------------------------------
    // Estado da conta
    // ---------------------------------------------------------------

    public function estaAtivo(): bool
    {
        return $this->status === 'ativo';
    }

    public function estaBloqueado(): bool
    {
        return $this->status === 'bloqueado';
    }

    public function foiExcluida(): bool
    {
        return $this->excluida_em !== null;
    }

    /**
     * Endereço da foto de perfil, ou null (a tela mostra as iniciais).
     * 01/10: usuário, clínica e admin têm foto (documento de modificações).
     */
    public function getFotoUrlAttribute(): ?string
    {
        return FotoDePerfil::url($this->foto);
    }
}
