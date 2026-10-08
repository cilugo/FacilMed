<?php

namespace App\Models;

use App\Support\FotoDePerfil;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Usuario extends Model
{
    protected $table = 'usuarios';

    protected $fillable = ['user_id', 'cpf', 'data_nascimento', 'sexo'];

    protected $casts = ['data_nascimento' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function planos(): HasMany
    {
        return $this->hasMany(UsuarioPlano::class);
    }

    public function planosAtivos(): HasMany
    {
        return $this->hasMany(UsuarioPlano::class)->where('status', 'ativa');
    }

    public function avaliacoes(): HasMany
    {
        return $this->hasMany(Avaliacao::class);
    }

    public function getIdadeAttribute(): ?int
    {
        return $this->data_nascimento?->age;
    }

    /**
     * 30/09/2026 — o usuário exclui a própria conta (LGPD, plano do app).
     * Revisto em 01/10/2026, quando as consultas saíram do PointMed.
     *
     * POR QUE ANONIMIZAR E NÃO APAGAR: as notas que o usuário deu entram na
     * média dos locais e médicos. A LGPD (art. 16) aceita guardar o dado
     * ANONIMIZADO: a nota fica, mas ninguém consegue mais dizer de quem era.
     *
     * O que acontece, tudo numa transação (ou vai tudo, ou nada):
     *  1. carteirinhas → apagadas (sem consulta, nada depende delas);
     *  2. avaliações → a NOTA fica (a média não muda), o COMENTÁRIO sai
     *     (é texto livre escrito pela pessoa);
     *  3. foto de perfil → o arquivo é apagado;
     *  4. pedidos de troca de senha e sessões abertas (guardam IP e
     *     navegador) → apagados;
     *  5. nome, e-mail, telefone, CPF, nascimento e sexo → apagados ou trocados
     *     por um marcador; senha → uma aleatória que ninguém sabe; status 'inativo'
     *     (o GarantirContaAtiva já barra conta que não está ativa, e um CHECK no
     *     banco impede que conta excluída volte a 'ativo').
     *
     * O e-mail vira excluida-{id}@facilmed.invalid: a coluna é obrigatória e única,
     * e o domínio .invalid é reservado — nunca entrega e-mail para ninguém.
     */
    public function excluirConta(): void
    {
        DB::transaction(function () {
            // Trava a linha do usuário: duas abas excluindo ao mesmo tempo
            // fazem o trabalho uma vez só.
            $usuario = self::whereKey($this->id)->lockForUpdate()->firstOrFail();
            $user = $usuario->user;
            $emailAntigo = $user->email;

            // 07/10: a foto está no banco, então sai na mesma transação —
            // se algo falhar, ela continua junto com o resto da conta.
            FotoDePerfil::apagar($user->foto);

            $usuario->planos()->delete();

            // update() direto no banco, sem passar pelo model: só o comentário
            // muda, então não precisa recalcular as médias.
            $usuario->avaliacoes()->update(['comentario' => null]);

            DB::table('password_reset_tokens')->where('email', $emailAntigo)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();

            $usuario->update(['cpf' => null, 'data_nascimento' => null, 'sexo' => null]);

            // forceFill: excluida_em e remember_token ficam fora do $fillable
            // de propósito (nenhum formulário deve preenchê-los).
            $user->forceFill([
                'name'              => 'Conta excluída',
                'email'             => "excluida-{$user->id}@facilmed.invalid",
                'email_verified_at' => null,
                'telefone'          => null,
                'foto'              => null,
                'password'          => Str::random(64),   // o cast 'hashed' do User guarda o hash
                'remember_token'    => null,
                'status'            => 'inativo',
                'excluida_em'       => now(),
            ])->save();
        });
    }
}
