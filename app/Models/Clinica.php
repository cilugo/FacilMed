<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Clinica extends Model
{
    protected $table = 'clinicas';

    protected $fillable = [
        'user_id', 'cnpj', 'razao_social', 'nome_fantasia',
        'descricao', 'telefone', 'logo',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function locais(): HasMany
    {
        return $this->hasMany(Local::class);
    }

    public function vinculos(): HasManyThrough
    {
        return $this->hasManyThrough(Vinculo::class, Local::class);
    }

    /**
     * As especialidades da clinica sao DERIVADAS dos medicos
     * vinculados a ela - nao existe tabela propria para isso,
     * de proposito: seria mais uma coisa para manter em sincronia.
     */
    public function especialidades()
    {
        // 28/09: só o que dá para AGENDAR aqui — mesmas condições do
        // AlocadorDeMedico: médico visível, vínculo ativo numa unidade
        // ativa desta clínica e preço ATIVO para a especialidade. Antes
        // bastava algum médico da clínica ter a especialidade no perfil,
        // e o paciente escolhia e caía em "sem vaga".
        return Especialidade::where('ativo', true)
            ->whereHas('medicos', fn ($m) => $m->visivel()
                ->whereHas('vinculos', fn ($v) => $v->where('vinculos.ativo', true)
                    ->whereHas('local', fn ($l) => $l->where('clinica_id', $this->id)->where('ativo', true))
                    ->whereHas('precos', fn ($p) => $p->where('precos.ativo', true)
                        ->whereColumn('precos.especialidade_id', 'especialidades.id'))));
    }
}
