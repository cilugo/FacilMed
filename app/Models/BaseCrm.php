<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * "CFM simulado". Só leitura para o sistema — quem preenche é o
 * BaseSimuladaSeeder. Consultada por App\Services\BaseSimulada.
 */
class BaseCrm extends Model
{
    protected $table = 'base_crms';

    protected $fillable = ['crm', 'uf', 'nome', 'situacao'];
}
