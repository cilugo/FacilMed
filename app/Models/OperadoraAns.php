<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Operadoras dos dados abertos da ANS (comando facilmed:importar-operadoras).
 *
 * OPCIONAL desde 24/09/2026: os convênios do PointMed passaram a ser
 * fictícios e não dependem mais desta tabela. Ela continua existindo
 * para quem quiser, no futuro, ligar um convênio a uma operadora real.
 * Nunca cadastre operadora à mão — ou vem do CSV da ANS, ou não existe.
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
