<?php

namespace App\Http\Controllers;

use App\Support\FotoDePerfil;
use Illuminate\Http\Request;

/**
 * Foto de perfil da PRÓPRIA conta (01/10/2026): usuário, clínica e admin.
 *
 * Um controller só para os três tipos: a regra é a mesma, e a pessoa só
 * mexe na foto da conta logada (auth()->user()), então não há "dono" a
 * conferir por Policy — não existe id na rota para trocar.
 *
 * A foto aparece no topo do painel, na tela Usuários do admin, para a
 * clínica nas avaliações e, no caso da clínica, na página pública dela.
 */
class FotoController extends Controller
{
    public function atualizar(Request $request)
    {
        $request->validate(
            ['foto' => array_merge(['required'], array_slice(FotoDePerfil::REGRAS, 1))],
            [
                'foto.required' => 'Escolha uma imagem.',
                'foto.image'    => 'O arquivo precisa ser uma imagem.',
                'foto.mimes'    => 'Use uma imagem JPG, PNG ou WEBP.',
                'foto.max'      => 'A imagem pode ter no máximo 2 MB.',
            ],
        );

        $user = $request->user();
        $user->update(['foto' => FotoDePerfil::salvar($request->file('foto'), $user->foto)]);

        return back()->with('sucesso', 'Foto de perfil atualizada.');
    }

    public function remover(Request $request)
    {
        $user = $request->user();
        FotoDePerfil::apagar($user->foto);
        $user->update(['foto' => null]);

        return back()->with('sucesso', 'Foto de perfil removida.');
    }
}
