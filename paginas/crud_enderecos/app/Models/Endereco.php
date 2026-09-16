<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Endereco extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Atributos que podem ser preenchidos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'enderecavel_type',
        'enderecavel_id',
    ];

    /**
     * Relacionamento polimórfico: retorna o Estabelecimento, Médico ou
     * outra entidade dona deste endereço.
     */
    public function enderecavel()
    {
        return $this->morphTo();
    }

    /**
     * Retorna o endereço formatado como uma única linha,
     * útil para exibição em telas e relatórios.
     */
    public function getEnderecoCompletoAttribute(): string
    {
        $partes = array_filter([
            $this->logradouro,
            $this->numero,
            $this->complemento,
            $this->bairro,
            $this->cidade . '/' . $this->estado,
            $this->cep,
        ]);

        return implode(', ', $partes);
    }
}
