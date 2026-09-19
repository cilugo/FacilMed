<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Importada dos dados abertos da ANS por comando Artisan.
 * Nunca cadastrada a mao - e a unica validacao externa real
 * do projeto.
 */
class OperadoraAns extends Model
{
    protected $table = 'operadoras_ans';

    protected $fillable = [
        'registro_ans', 'cnpj', 'razao_social', 'nome_fantasia', 'modalidade', 'uf',
    ];

    public function convenio(): HasOne
    {
        return $this->hasOne(Convenio::class);
    }
}
