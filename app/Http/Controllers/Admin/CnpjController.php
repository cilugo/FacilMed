<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BaseCnpj;
use App\Models\Clinica;
use Illuminate\Http\Request;

/**
 * Admin → Verificar CNPJ (01/10/2026, substitui "Verificar CRM").
 *
 * Desde 01/10 é a clínica/hospital que cadastra os médicos (e o CRM de
 * cada um é conferido na base simulada nesse momento). O que o admin
 * acompanha é o CNPJ das clínicas: conferido na base simulada no cadastro
 * e mostrado aqui com a situação ATUAL na base — se o CNPJ foi baixado
 * depois do cadastro, a tela aponta, e o admin bloqueia a conta em Usuários.
 *
 * A tela NUNCA diz "validado na Receita": diz "conferido na base simulada
 * do PointMed" (AGENTS.md §3). Só leitura: ninguém escreve na base simulada
 * por tela (só o BaseSimuladaSeeder).
 */
class CnpjController extends Controller
{
    public function index(Request $request)
    {
        $clinicas = Clinica::with('user')->orderBy('nome_fantasia')->get();

        // Uma consulta só para a base inteira dessas clínicas (sem N+1).
        $base = BaseCnpj::whereIn('cnpj', $clinicas->pluck('cnpj'))->get()->keyBy('cnpj');

        $linhas = $clinicas->map(fn (Clinica $c) => (object) [
            'clinica'  => $c,
            'registro' => $base->get($c->cnpj),
            'ok'       => $base->get($c->cnpj)?->situacao === 'ativa',
        ]);

        if ($request->query('situacao') === 'problema') {
            $linhas = $linhas->reject->ok->values();
        }

        return view('admin.cnpjs', [
            'linhas'    => $linhas,
            'problemas' => $clinicas->count() - $base->where('situacao', 'ativa')->count(),
        ]);
    }
}
