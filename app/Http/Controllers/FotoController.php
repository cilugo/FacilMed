<?php

namespace App\Http\Controllers;

use App\Models\Foto;
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
    /**
     * 07/10/2026: devolve a imagem guardada no banco (rota pública /foto/{chave}).
     *
     * Pública como era o arquivo em public/uploads: a foto aparece na página da
     * clínica e do médico para qualquer visitante. Quem não tem a chave
     * sorteada (40 letras) não acha a foto de ninguém.
     *
     * Cache longo: cada foto nova ganha chave nova, então a imagem de uma
     * chave nunca muda e o navegador pode guardar à vontade.
     */
    public function mostrar(string $chave)
    {
        $foto = Foto::where('chave', $chave)->firstOrFail();

        return response(base64_decode($foto->conteudo), 200, [
            'Content-Type'           => $foto->mime,
            'Cache-Control'          => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',   // o navegador não "adivinha" outro tipo
        ]);
    }

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
