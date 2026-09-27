<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const TIPO_PACIENTE = 'paciente';
    public const TIPO_MEDICO   = 'medico';
    public const TIPO_CLINICA  = 'clinica';
    public const TIPO_ADMIN    = 'admin';

    protected $fillable = [
        'name', 'email', 'password', 'tipo', 'telefone', 'status',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'bloqueado_em'      => 'datetime',
        ];
    }

    // ---------------------------------------------------------------
    // Perfis (1:1). Apenas um deles existe, conforme o tipo.
    // ---------------------------------------------------------------

    public function paciente(): HasOne
    {
        return $this->hasOne(Paciente::class);
    }

    public function medico(): HasOne
    {
        return $this->hasOne(Medico::class);
    }

    public function clinica(): HasOne
    {
        return $this->hasOne(Clinica::class);
    }

    /** Retorna o perfil correspondente ao tipo, seja ele qual for. */
    public function perfil(): ?object
    {
        return match ($this->tipo) {
            self::TIPO_PACIENTE => $this->paciente,
            self::TIPO_MEDICO   => $this->medico,
            self::TIPO_CLINICA  => $this->clinica,
            default             => null,
        };
    }

    // ---------------------------------------------------------------
    // Papéis
    // ---------------------------------------------------------------

    public function ehPaciente(): bool { return $this->tipo === self::TIPO_PACIENTE; }
    public function ehMedico(): bool   { return $this->tipo === self::TIPO_MEDICO; }
    public function ehClinica(): bool  { return $this->tipo === self::TIPO_CLINICA; }
    public function ehAdmin(): bool    { return $this->tipo === self::TIPO_ADMIN; }

    // ---------------------------------------------------------------
    // Estado da conta
    // ---------------------------------------------------------------

    public function estaAtivo(): bool
    {
        return $this->status === 'ativo';
    }

    /**
     * Conta bloqueada é barrada no middleware de autenticação,
     * não escondida na interface (AGENTS.md §6). A mensagem ao
     * usuário diz que está bloqueada, sem detalhar o motivo.
     */
    public function estaBloqueado(): bool
    {
        return $this->status === 'bloqueado';
    }

    /**
     * Consultas AGENDADAS daqui para frente que dependem desta conta (24/09):
     * do paciente, do médico, ou nas unidades da clínica. Usado ao bloquear
     * a conta ou rejeitar o CRM.
     */
    public function consultasFuturasAfetadas(): \Illuminate\Database\Eloquent\Builder
    {
        $q = Consulta::query()->where('status', 'agendada');

        $q = match ($this->tipo) {
            self::TIPO_PACIENTE => $q->where('paciente_id', $this->paciente?->id ?? 0),
            self::TIPO_MEDICO   => $q->where('medico_id', $this->medico?->id ?? 0),
            self::TIPO_CLINICA  => $q->whereIn('vinculo_id', Vinculo::whereIn('local_id',
                Local::where('clinica_id', $this->clinica?->id ?? 0)->select('id'))->select('id')),
            default             => $q->whereRaw('1 = 0'),
        };

        return \App\Services\EstatisticasDeConsultas::aPartirDeAgora($q);
    }
}
