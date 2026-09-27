<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * "Receita Federal simulada". Só leitura para o sistema — quem preenche
 * é o BaseSimuladaSeeder. Consultada por App\Services\BaseSimulada.
 */
class BaseCnpj extends Model
{
    protected $table = 'base_cnpjs';

    protected $fillable = ['cnpj', 'razao_social', 'nome_fantasia', 'situacao'];
}
