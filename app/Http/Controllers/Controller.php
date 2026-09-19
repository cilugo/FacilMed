<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

/**
 * SUBSTITUI o Controller.php que vem no Laravel 12 limpo.
 *
 * POR QUE ISSO EXISTE
 * -------------------
 * Do Laravel 11 em diante o Controller base vem VAZIO. Nas versoes
 * anteriores ele ja trazia os traits AuthorizesRequests e
 * ValidatesRequests embutidos, e muito tutorial na internet ainda
 * assume isso.
 *
 * Nove controllers do FacilMed chamam $this->authorize(...) - sao 14
 * chamadas no total. Sem o trait abaixo, TODAS estouram com
 * "Call to undefined method authorize()" no primeiro clique.
 *
 * ValidatesRequests entra junto porque sete controllers usam
 * $request->validate(...) direto. Isso e temporario: a regra do
 * AGENTS.md e que validacao de cadastro mora em FormRequest, nao no
 * controller. O trait existe para o codigo atual nao quebrar enquanto
 * os FormRequests nao ficam prontos.
 */
abstract class Controller
{
    use AuthorizesRequests;
    use ValidatesRequests;
}
