<?php

namespace App\Models;

use App\Support\Localizacao;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Local extends Model
{
    protected $table = 'locais';

    protected $fillable = [
        'clinica_id', 'medico_id', 'nome', 'tipo', 'cep', 'endereco',
        'numero', 'complemento', 'bairro', 'cidade', 'uf', 'telefone', 'ativo',
        'latitude', 'longitude',
    ];

    protected $casts = ['ativo' => 'boolean', 'latitude' => 'float', 'longitude' => 'float'];

    /**
     * 29/09/2026: todo local salvo sem coordenada ganha a APROXIMADA do bairro
     * ou do centro da cidade (config/localizacao.php) - e ganha de novo se o
     * endereço mudar. Assim a busca por distância funciona para o que vem do
     * seeder, do cadastro da clínica, das unidades e do consultório do médico,
     * sem cada tela ter que lembrar disso.
     *
     * static::saving = "antes de gravar" (evento do Eloquent). Quem passar a
     * coordenada na mão (latitude preenchida) não é sobrescrito.
     */
    protected static function booted(): void
    {
        static::saving(function (Local $local) {
            $mudouEndereco = $local->isDirty(['cidade', 'uf', 'bairro']);

            if (! $local->isDirty('latitude') && ($local->latitude === null || $mudouEndereco)) {
                [$local->latitude, $local->longitude] = Localizacao::coordenadas($local->cidade, $local->uf, $local->bairro)
                    ?? [null, null];
            }
        });
    }

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    /** Preenchido apenas quando e consultorio proprio de autonomo. */
    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class);
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioFuncionamento::class);
    }

    public function vinculos(): HasMany
    {
        return $this->hasMany(Vinculo::class);
    }

    /** 01/10/2026: galeria do local (no banco - ver Foto), na ordem. Sem a imagem pesada. */
    public function fotos(): HasMany
    {
        return $this->hasMany(Foto::class)->select(Foto::COLUNAS_LEVES)->orderBy('ordem')->orderBy('id');
    }

    public function ehConsultorioProprio(): bool
    {
        return $this->clinica_id === null;
    }

    /**
     * 29/09: locais que aparecem na busca por distância: ativos e com pelo
     * menos um vínculo que recebe agendamento (a mesma regra da busca de
     * médicos: Vinculo::agendaveis). Com $slug, que ofereça essa especialidade.
     */
    public function scopeAgendaveis(Builder $q, ?string $slug = null): Builder
    {
        return $q->where('locais.ativo', true)
            ->whereHas('vinculos', fn ($v) => $v->agendaveis()->oferece($slug));
    }

    /**
     * A página pública do local pode abrir? Local ativo, e o dono no ar: a
     * clínica com a conta ativa, ou - no consultório próprio - o médico
     * visível (CRM verificado e conta ativa).
     */
    public function estaPublico(): bool
    {
        if (! $this->ativo) {
            return false;
        }

        return $this->ehConsultorioProprio()
            ? Medico::visivel()->whereKey($this->medico_id)->exists()
            : (bool) $this->clinica?->user?->estaAtivo();
    }

    /**
     * Nota média e total de avaliações de cada local (29/09).
     *
     * A avaliação é por CONSULTA realizada (AGENTS §3), a consulta sabe o
     * vínculo e o vínculo sabe o local: a nota do local sai daí, sem tabela
     * nova. Só números - o comentário é privado e nunca sai desta consulta.
     *
     * @return Collection<int, object{local_id: int, media: float, total: int}>  indexada pelo id do local
     */
    public static function notas(iterable $ids): Collection
    {
        return Avaliacao::query()
            ->join('consultas', 'consultas.id', '=', 'avaliacoes.consulta_id')
            ->join('vinculos', 'vinculos.id', '=', 'consultas.vinculo_id')
            ->whereIn('vinculos.local_id', collect($ids)->all())
            ->groupBy('vinculos.local_id')
            ->selectRaw('vinculos.local_id, AVG(avaliacoes.estrelas) AS media, COUNT(*) AS total')
            ->toBase()
            ->get()
            ->keyBy('local_id');
    }

    /** Distância em linha reta até um ponto, em km. Null se o local não tem coordenada. */
    public function distanciaAte(?float $lat, ?float $lng): ?float
    {
        if ($lat === null || $lng === null || $this->latitude === null || $this->longitude === null) {
            return null;
        }

        return Localizacao::distanciaKm($lat, $lng, $this->latitude, $this->longitude);
    }

    /**
     * Quem pode editar preco e dados deste local:
     * a clinica dona, ou o medico autonomo dono.
     */
    public function donoUserId(): ?int
    {
        return $this->ehConsultorioProprio()
            ? $this->medico?->user_id
            : $this->clinica?->user_id;
    }

    public function getEnderecoCompletoAttribute(): string
    {
        return trim("{$this->endereco}, {$this->numero} - {$this->bairro}, {$this->cidade}/{$this->uf}");
    }

    /**
     * Troca o horário de funcionamento inteiro. $horarios = ['segunda' =>
     * ['abre' => '08:00', 'fecha' => '18:00'], ...]; dia ausente = fechado.
     * Vazio = seg-sex 08:00-18:00 (padrão para não nascer local fechado).
     */
    public function definirHorarios(array $horarios): void
    {
        if ($horarios === []) {
            $horarios = collect(['segunda', 'terca', 'quarta', 'quinta', 'sexta'])
                ->mapWithKeys(fn ($d) => [$d => ['abre' => '08:00', 'fecha' => '18:00']])->all();
        }

        $this->horarios()->delete();
        foreach ($horarios as $dia => $h) {
            $this->horarios()->create(['dia_semana' => $dia, 'abre' => $h['abre'], 'fecha' => $h['fecha']]);
        }
    }
}
